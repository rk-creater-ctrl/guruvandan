<?php

namespace App\Http\Controllers;

use App\Enums\TributeType;
use App\Http\Requests\StoreTeacherReplyRequest;
use App\Http\Requests\TeacherPasswordRequest;
use App\Http\Requests\TeacherProfileRequest;
use App\Models\Event;
use App\Services\CertificateService;
use App\Services\QrCodeService;
use App\Services\RevealService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function dashboard(Request $request, RevealService $revealService, QrCodeService $qrCodeService): View
    {
        $teacher = $request->user()->teacherProfile()->with('reply', 'certificate', 'user')->firstOrFail();
        $tributes = $teacher->tributes()->where('status', 'approved')->with('student.studentProfile', 'media')->latest()->get();
        $showContent = $revealService->isRevealed();
        $qrSvg = $qrCodeService->svg(route('teachers.show', $teacher));
        $counts = collect(TributeType::cases())->mapWithKeys(fn (TributeType $type) => [
            $type->value => $tributes->where('tribute_type', $type)->count(),
        ]);
        $featured = $tributes->where('is_featured', true);
        $event = Event::query()->where('is_active', true)->with(['schedules' => fn ($query) => $query->where('is_enabled', true)])->latest('event_date')->first();

        return view('teacher.dashboard', compact('teacher', 'tributes', 'showContent', 'qrSvg', 'counts', 'featured', 'event'));
    }

    public function saveReply(StoreTeacherReplyRequest $request): RedirectResponse
    {
        $teacher = $request->user()->teacherProfile()->firstOrFail();
        $teacher->reply()->updateOrCreate(
            ['teacher_id' => $teacher->id],
            ['user_id' => $request->user()->id, 'message' => $request->string('message')->toString()]
        );

        return back()->with('status', 'Thank-you reply saved.');
    }

    public function editProfile(Request $request): View
    {
        $teacher = $request->user()->teacherProfile()->with('user')->firstOrFail();

        return view('teacher.profile-edit', compact('teacher'));
    }

    public function updateProfile(TeacherProfileRequest $request): RedirectResponse
    {
        $teacher = $request->user()->teacherProfile()->with('user')->firstOrFail();

        DB::transaction(function () use ($request, $teacher): void {
            $teacher->user->update([
                'name' => $request->string('name')->toString(),
                'phone' => $request->input('phone'),
            ]);
            $teacher->fill($request->safe()->only([
                'location',
                'bio',
                'quote',
            ]));
            $this->replaceProfileImage($request, $teacher, 'photo', 'photo_path');
            $this->replaceProfileImage($request, $teacher, 'cover_image', 'cover_image_path');
            $teacher->save();
        });

        return back()->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(TeacherPasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
            'must_change_password' => false,
        ]);

        return back()->with('status', 'Your password has been changed securely.');
    }

    public function downloadCertificate(Request $request, CertificateService $certificateService, QrCodeService $qrCodeService, SettingsService $settings)
    {
        $teacher = $request->user()->teacherProfile()->with('user')->firstOrFail();
        $certificate = $certificateService->forTeacher($teacher);
        abort_if($certificate->revoked_at, 403, 'This certificate has been revoked.');
        $verificationUrl = route('certificates.verify', $certificate->verification_token);
        $qrSvg = $qrCodeService->svg($verificationUrl);

        return $certificateService->download($teacher, $verificationUrl, $qrSvg, $settings->all()->toArray());
    }

    private function replaceProfileImage(TeacherProfileRequest $request, $teacher, string $input, string $column): void
    {
        if (($input === 'cover_image' && $request->boolean('remove_cover_image')) || $request->hasFile($input)) {
            Storage::disk('public')->delete($teacher->{$column});
            $teacher->{$column} = null;
        }

        if ($request->hasFile($input)) {
            $teacher->{$column} = $request->file($input)->store('teachers', 'public');
        }
    }
}
