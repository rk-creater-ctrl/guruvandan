<?php

namespace App\Http\Controllers;

use App\Enums\TributeType;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Services\QrCodeService;
use App\Services\RevealService;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function __construct(
        private readonly RevealService $revealService,
        private readonly SettingsService $settings,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public function home(): View
    {
        $event = Event::query()->where('is_active', true)->with('schedules')->latest('event_date')->first();
        $teachers = Teacher::query()->where('is_active', true)->where('is_public', true)->with('user')->withCount(['tributes as approved_tributes_count' => fn (Builder $query) => $query->where('status', 'approved')])->take(4)->get();
        $featuredTributes = $this->revealService->isRevealed()
            ? Tribute::query()->where('status', 'approved')->latest()->take(3)->with('student', 'teacher.user')->get()
            : collect();

        return view('public.home', [
            'event' => $event,
            'teachers' => $teachers,
            'featuredTributes' => $featuredTributes,
            'isRevealed' => $this->revealService->isRevealed(),
        ]);
    }

    public function gallery(Request $request): View
    {
        $teachers = Teacher::query()
            ->with('user')
            ->withCount(['tributes as approved_tributes_count' => fn (Builder $query) => $query->where('status', 'approved')])
            ->when($request->filled('q'), fn (Builder $query) => $query->whereHas('user', fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('designation'), fn (Builder $query) => $query->where('designation', $request->input('designation')))
            ->when($request->filled('location'), fn (Builder $query) => $query->where('location', $request->input('location')))
            ->where('is_active', true)
            ->where('is_public', true)
            ->paginate(6)
            ->withQueryString();

        return view('public.gallery', [
            'teachers' => $teachers,
            'designations' => Teacher::query()->where('is_active', true)->whereNotNull('designation')->distinct()->orderBy('designation')->pluck('designation'),
            'locations' => Teacher::query()->where('is_active', true)->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function teacher(Request $request, Teacher $teacher): View
    {
        abort_unless(($teacher->is_active && $teacher->is_public) || ($request->user()?->isAdmin() ?? false), 404);
        $teacher->load('user', 'reply.user', 'certificate');
        $showContent = $this->revealService->isRevealed() || ($request->user()?->isAdmin() ?? false);

        $tributes = Tribute::query()
            ->whereBelongsTo($teacher)
            ->where('status', 'approved')
            ->with(['student.studentProfile', 'media'])
            ->latest()
            ->get();

        $counts = collect(TributeType::cases())->mapWithKeys(fn (TributeType $type) => [
            $type->value => $tributes->where('tribute_type', $type)->count(),
        ]);

        $qrSvg = $this->qrCodeService->svg(route('teachers.show', $teacher));

        return view('public.teacher', compact('teacher', 'tributes', 'counts', 'showContent', 'qrSvg'));
    }

    public function wall(Request $request): View
    {
        $query = Tribute::query()
            ->where('status', 'approved')
            ->with(['student.studentProfile', 'teacher.user'])
            ->withCount('likes');
        if (! $this->revealService->isRevealed() && ! ($request->user()?->isAdmin() ?? false)) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('teacher')) {
            $query->whereHas('teacher', fn (Builder $builder) => $builder->where('slug', $request->string('teacher')));
        }

        if ($request->filled('class')) {
            $query->whereHas('student.studentProfile', fn (Builder $builder) => $builder->where('class_name', $request->string('class')));
        }

        if ($request->filled('type')) {
            $query->where('tribute_type', $request->string('type'));
        }

        if ($request->filled('language')) {
            $query->where('language', $request->string('language'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->string('sort')->toString() === 'likes') {
            $query->orderByDesc('likes_count');
        } else {
            $query->latest();
        }

        return view('public.wall', [
            'tributes' => $query->paginate(9)->withQueryString(),
            'teachers' => Teacher::query()->where('is_active', true)->with('user')->get(),
        ]);
    }

    public function event(): View
    {
        $event = Event::query()->where('is_active', true)
            ->with(['schedules' => fn ($query) => $query->where('is_enabled', true)])
            ->latest('event_date')->firstOrFail();

        return view('public.event', compact('event'));
    }

    public function verifyCertificate(string $token): View
    {
        $certificate = Certificate::query()
            ->with('teacher.user')
            ->where(fn (Builder $query) => $query
                ->where('verification_token', $token)
                ->orWhere('certificate_number', $token))
            ->firstOrFail();

        return view('public.certificate-verify', [
            'certificate' => $certificate,
            'event' => Event::query()->where('is_active', true)->latest('event_date')->first(),
            'verifiedAt' => now(),
        ]);
    }
}
