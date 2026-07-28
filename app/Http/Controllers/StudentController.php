<?php

namespace App\Http\Controllers;

use App\Enums\TributeLanguage;
use App\Enums\TributeType;
use App\Http\Requests\GenerateAiMessageRequest;
use App\Http\Requests\StoreTributeRequest;
use App\Http\Requests\UpdateTributeRequest;
use App\Models\Event;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Services\ActivityLogService;
use App\Services\AI\AiMessageGenerator;
use App\Services\MediaUploadService;
use App\Services\RevealService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function dashboard(Request $request, RevealService $reveal): View
    {
        $tributes = Tribute::query()
            ->where('student_id', $request->user()->id)
            ->with('teacher.user', 'media')
            ->latest()
            ->paginate(8);

        return view('student.dashboard', [
            'tributes' => $tributes,
            'teachers' => Teacher::query()->where('is_active', true)->where('is_public', true)->with('user')->orderBy('designation')->orderBy('id')->get(),
            'tributeTypes' => TributeType::cases(),
            'languages' => TributeLanguage::cases(),
            'stats' => [
                'total' => Tribute::query()->where('student_id', $request->user()->id)->count(),
                'pending' => Tribute::query()->where('student_id', $request->user()->id)->where('status', 'pending')->count(),
                'approved' => Tribute::query()->where('student_id', $request->user()->id)->where('status', 'approved')->count(),
                'rejected' => Tribute::query()->where('student_id', $request->user()->id)->where('status', 'rejected')->count(),
            ],
            'event' => Event::query()->where('is_active', true)->latest('event_date')->first(),
            'isRevealed' => $reveal->isRevealed(),
        ]);
    }

    public function store(StoreTributeRequest $request, MediaUploadService $uploadService, ActivityLogService $logs): RedirectResponse
    {
        $tribute = DB::transaction(function () use ($request, $uploadService): Tribute {
            $tribute = Tribute::query()->create([
                ...$request->safe()->except('media'),
                'student_id' => $request->user()->id,
            ]);

            if ($request->hasFile('media')) {
                $mediaType = $tribute->tribute_type->mediaKind() ?? 'document';
                $uploadService->store($tribute, $request->file('media'), $mediaType);
            }

            return $tribute;
        });
        $logs->log($request->user(), 'tribute_submitted', $tribute);

        return back()->with('status', 'Tribute submitted for approval.');
    }

    public function update(UpdateTributeRequest $request, Tribute $tribute, MediaUploadService $uploadService, ActivityLogService $logs): RedirectResponse
    {
        abort_unless($tribute->student_id === $request->user()->id && $tribute->status->value === 'pending', 403);

        DB::transaction(function () use ($request, $tribute, $uploadService): void {
            $tribute->update($request->safe()->except('media'));

            if ($request->hasFile('media')) {
                $uploadService->deleteAll($tribute);
                $mediaType = $tribute->tribute_type->mediaKind() ?? 'document';
                $uploadService->store($tribute, $request->file('media'), $mediaType);
            }
        });
        $logs->log($request->user(), 'tribute_edited', $tribute);

        return back()->with('status', 'Pending tribute updated.');
    }

    public function destroy(Request $request, Tribute $tribute, MediaUploadService $media, ActivityLogService $logs): RedirectResponse
    {
        abort_unless($tribute->student_id === $request->user()->id && $tribute->status->value === 'pending', 403);
        $logs->log($request->user(), 'tribute_deleted', $tribute, ['title' => $tribute->title]);
        $media->deleteAll($tribute);
        $tribute->delete();

        return back()->with('status', 'Pending tribute deleted.');
    }

    public function resubmit(UpdateTributeRequest $request, Tribute $tribute, MediaUploadService $uploadService, ActivityLogService $logs): RedirectResponse
    {
        abort_unless($tribute->student_id === $request->user()->id && $tribute->status->value === 'rejected', 403);
        $previousReason = $tribute->rejection_reason;

        DB::transaction(function () use ($request, $tribute, $uploadService): void {
            $tribute->update([
                ...$request->safe()->except('media'),
                'status' => 'pending',
                'resubmitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'is_featured' => false,
            ]);
            if ($request->hasFile('media')) {
                $uploadService->deleteAll($tribute);
                $uploadService->store($tribute, $request->file('media'), $tribute->tribute_type->mediaKind() ?? 'document');
            }
        });
        $logs->log($request->user(), 'tribute_resubmitted', $tribute, ['previous_rejection_reason' => $previousReason]);

        return back()->with('status', 'Corrected tribute resubmitted for review.');
    }

    public function like(Request $request, Tribute $tribute): RedirectResponse
    {
        abort_unless($tribute->status->value === 'approved', 404);
        $tribute->likes()->firstOrCreate(['user_id' => $request->user()->id]);

        return back()->with('status', 'Tribute appreciated.');
    }

    public function generateAi(GenerateAiMessageRequest $request, AiMessageGenerator $generator, SettingsService $settings): JsonResponse
    {
        abort_unless((bool) $settings->get('ai_enabled', true), 503, 'The AI assistant is currently disabled.');
        $key = 'ai:'.$request->user()->id;
        $limit = (int) $settings->get('ai_rate_limit', 10);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json(['message' => 'AI request limit reached. Please try again shortly.'], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validated();
        $teacher = Teacher::query()->with('user')->findOrFail($validated['teacher_id']);
        abort_unless($teacher->is_active, 422);
        $validated['teacher_name'] = $teacher->user->name;
        $language = TributeLanguage::from($validated['language']);

        $labels = [
            'thank_you_message' => 'Thank-you message',
            'poem' => 'Poem',
            'letter' => 'Letter',
            'short_speech' => 'Short speech',
            'guru_purnima_wish' => 'Guru Purnima wish',
        ];

        try {
            $content = $generator->generate([
                ...$validated,
                'language_label' => $language->label(),
                'content_type_label' => $labels[$validated['content_type']],
            ]);
            $content = Str::limit(trim($content), 6000, '');
        } catch (Throwable $exception) {
            Log::warning('AI generation failed', ['user_id' => $request->user()->id, 'exception' => $exception::class]);

            return response()->json(['message' => 'The writing assistant is temporarily unavailable. Your form is safe; please retry or continue writing manually.'], 503);
        }

        return response()->json(['content' => $content]);
    }
}
