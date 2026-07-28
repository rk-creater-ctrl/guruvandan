<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\EventRequest;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Event;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\CertificateService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'teachers' => Teacher::query()->count(),
            'students' => User::query()->where('role', Role::Student)->count(),
            'tributes' => Tribute::query()->count(),
            'pending' => Tribute::query()->where('status', 'pending')->count(),
            'approved' => Tribute::query()->where('status', 'approved')->count(),
            'rejected' => Tribute::query()->where('status', 'rejected')->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'teacherTotals' => Teacher::query()->with('user')->withCount('tributes')->orderByDesc('tributes_count')->take(6)->get(),
            'recent' => Tribute::query()->with('teacher.user', 'student')->latest()->take(8)->get(),
        ]);
    }

    public function event(): View
    {
        return view('admin.event', [
            'event' => Event::query()->where('is_active', true)->with('schedules')->latest('event_date')->first(),
        ]);
    }

    public function saveEvent(EventRequest $request, ActivityLogService $logs): RedirectResponse
    {
        $event = DB::transaction(fn () => Event::query()->updateOrCreate(
            ['is_active' => true],
            $request->safe()->except('schedule')
        ));
        $logs->log($request->user(), 'event_updated', $event);

        return back()->with('status', 'Event details updated.');
    }

    public function settings(SettingsService $settings): View
    {
        return view('admin.settings', ['settings' => $settings->all()]);
    }

    public function saveSettings(SettingsRequest $request, SettingsService $settings, ActivityLogService $logs): RedirectResponse
    {
        $previousReveal = (bool) $settings->get('reveal_enabled', false);
        foreach ($request->safe()->except(['school_logo', 'principal_signature', 'reveal_enabled', 'ai_enabled', 'ai_fallback_enabled']) as $key => $value) {
            $settings->put($key, $value);
        }
        $settings->put('reveal_enabled', $request->boolean('reveal_enabled'));
        $settings->put('ai_enabled', $request->boolean('ai_enabled'));
        $settings->put('ai_fallback_enabled', $request->boolean('ai_fallback_enabled'));

        foreach (['school_logo', 'principal_signature'] as $upload) {
            if ($request->hasFile($upload)) {
                Storage::disk('public')->delete($settings->get($upload.'_path'));
                $settings->put($upload.'_path', $request->file($upload)->store('settings', 'public'));
            }
        }

        Event::query()->where('is_active', true)->latest('event_date')->first()?->update([
            'title' => $request->string('event_title')->toString(),
            'description' => $request->input('celebration_message'),
            'event_date' => $request->input('event_date'),
            'event_time' => $request->input('event_time'),
            'venue' => $request->input('event_venue'),
            'chief_guest' => $request->input('chief_guest'),
        ]);

        $logs->log($request->user(), 'platform_settings_updated', null, [
            'keys' => array_keys($request->safe()->except(['school_logo', 'principal_signature'])),
            'reveal_changed' => $previousReveal !== $request->boolean('reveal_enabled'),
            'reveal_enabled' => $request->boolean('reveal_enabled'),
        ]);

        return back()->with('status', 'Platform settings saved.');
    }

    public function generateCertificates(CertificateService $certificates, ActivityLogService $logs): RedirectResponse
    {
        Teacher::query()->with('user')->get()->each(function (Teacher $teacher) use ($certificates, $logs): void {
            $certificate = $certificates->forTeacher($teacher);
            $logs->log(request()->user(), 'certificate_generated', $certificate, ['teacher_id' => $teacher->id]);
        });

        return back()->with('status', 'Certificates generated for all teachers.');
    }
}
