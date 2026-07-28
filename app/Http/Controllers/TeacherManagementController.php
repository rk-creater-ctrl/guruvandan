<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherManagementController extends Controller
{
    public function index(Request $request): View
    {
        $teachers = Teacher::query()
            ->with(['user', 'certificate'])
            ->withCount('tributes')
            ->when($request->filled('q'), fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where('designation', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('email', 'like', $term));
            }))
            ->when($request->filled('designation'), fn (Builder $query) => $query->where('designation', $request->input('designation')))
            ->when($request->filled('location'), fn (Builder $query) => $query->where('location', $request->input('location')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->input('status') === 'active'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.teachers', [
            'teachers' => $teachers,
            'designations' => Teacher::query()->whereNotNull('designation')->distinct()->orderBy('designation')->pluck('designation'),
            'locations' => Teacher::query()->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $teacher->load('user', 'certificate')->loadCount('tributes');

        return view('admin.teacher-edit', compact('teacher'));
    }

    public function store(TeacherRequest $request, ActivityLogService $logs): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $teacher = $this->persist($request);
        $logs->log($request->user(), 'teacher_created', $teacher);

        return redirect()->route('admin.teachers.edit', $teacher)->with('status', 'Teacher account created.');
    }

    public function update(TeacherRequest $request, Teacher $teacher, ActivityLogService $logs): RedirectResponse
    {
        $teacher = $this->persist($request, $teacher);
        $logs->log($request->user(), 'teacher_updated', $teacher);

        return back()->with('status', 'Teacher details updated.');
    }

    public function toggle(Request $request, Teacher $teacher, ActivityLogService $logs): RedirectResponse
    {
        $teacher->update(['is_active' => ! $teacher->is_active]);
        $teacher->user?->update(['is_active' => $teacher->is_active]);
        $logs->log($request->user(), $teacher->is_active ? 'teacher_activated' : 'teacher_archived', $teacher);

        return back()->with('status', 'Teacher status updated.');
    }

    public function resetPassword(ResetPasswordRequest $request, Teacher $teacher, ActivityLogService $logs): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_unless($teacher->user, 422, 'This teacher has no linked login account.');
        $teacher->user->update(['password' => $request->string('password')->toString(), 'must_change_password' => true]);
        $logs->log($request->user(), 'teacher_password_reset', $teacher);

        return back()->with('status', 'Teacher password reset.');
    }

    public function destroy(Request $request, Teacher $teacher, ActivityLogService $logs): RedirectResponse
    {
        if ($teacher->tributes()->exists() || $teacher->certificate()->exists()) {
            $teacher->update(['is_active' => false]);
            $teacher->user?->update(['is_active' => false]);
            $logs->log($request->user(), 'teacher_archived', $teacher, ['reason' => 'related_records']);

            return back()->with('status', 'Teacher archived because historical tributes or a certificate exist.');
        }

        $user = $teacher->user;
        Storage::disk('public')->delete(array_filter([$teacher->photo_path, $teacher->cover_image_path]));
        $logs->log($request->user(), 'teacher_deleted', $teacher, ['name' => $user?->name]);
        $teacher->delete();
        $user?->delete();

        return redirect()->route('admin.teachers')->with('status', 'Unused teacher account permanently deleted.');
    }

    private function persist(TeacherRequest $request, ?Teacher $teacher = null): Teacher
    {
        return DB::transaction(function () use ($request, $teacher): Teacher {
            $user = $teacher?->user ?: new User;
            $userData = [
                'name' => $request->string('name')->toString(),
                'email' => $request->filled('email') ? $request->string('email')->toString() : null,
                'phone' => $request->input('phone'),
                'role' => Role::Teacher,
                'is_active' => $request->boolean('is_active', true),
            ];
            if ($request->user()?->isSuperAdmin()) {
                $userData['username'] = strtolower($request->string('username')->toString());
                $userData['must_change_password'] = $request->boolean('must_change_password', true);
            }
            $user->fill($userData);
            if ($request->filled('password')) {
                $user->password = $request->string('password')->toString();
            }
            $user->save();

            $teacher ??= new Teacher;
            $teacher->fill([
                'user_id' => $user->id,
                'slug' => $request->string('slug')->toString(),
                'designation' => $request->input('designation'),
                'short_intro' => $request->input('short_intro'),
                'qualification' => $request->input('qualification'),
                'years_experience' => $request->filled('years_experience') ? $request->integer('years_experience') : null,
                'joining_year' => $request->filled('joining_year') ? $request->integer('joining_year') : null,
                'location' => $request->input('location'),
                'banner_title' => $request->input('banner_title'),
                'bio' => $request->input('bio'),
                'quote' => $request->input('quote'),
                'is_active' => $request->boolean('is_active', true),
                'is_public' => $request->boolean('is_public', true),
            ]);

            $this->replaceImage($request, $teacher, 'photo', 'photo_path');
            $this->replaceImage($request, $teacher, 'cover_image', 'cover_image_path');
            $teacher->save();

            return $teacher->fresh('user');
        });
    }

    private function replaceImage(TeacherRequest $request, Teacher $teacher, string $input, string $column): void
    {
        if ($request->boolean('remove_'.$input) || $request->hasFile($input)) {
            Storage::disk('public')->delete($teacher->{$column});
            $teacher->{$column} = null;
        }
        if ($request->hasFile($input)) {
            $teacher->{$column} = $request->file($input)->store('teachers', 'public');
        }
    }
}
