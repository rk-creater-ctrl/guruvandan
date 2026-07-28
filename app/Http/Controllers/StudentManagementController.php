<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.students', [
            'students' => $this->query($request)->paginate(15)->withQueryString(),
            'classes' => User::query()->where('role', Role::Student)->join('student_profiles', 'users.id', '=', 'student_profiles.user_id')->distinct()->orderBy('class_name')->pluck('class_name'),
        ]);
    }

    public function edit(User $student): View
    {
        $this->assertStudent($student);
        $student->load('studentProfile')->loadCount([
            'tributes',
            'tributes as approved_tributes_count' => fn ($q) => $q->where('status', 'approved'),
            'tributes as pending_tributes_count' => fn ($q) => $q->where('status', 'pending'),
            'tributes as rejected_tributes_count' => fn ($q) => $q->where('status', 'rejected'),
        ]);

        return view('admin.student-edit', [
            'student' => $student,
            'tributes' => $student->tributes()->with('teacher.user')->latest()->paginate(10),
        ]);
    }

    public function store(StudentRequest $request, ActivityLogService $logs): RedirectResponse
    {
        $student = $this->persist($request);
        $logs->log($request->user(), 'student_created', $student);

        return redirect()->route('admin.students.edit', $student)->with('status', 'Student account created.');
    }

    public function update(StudentRequest $request, User $student, ActivityLogService $logs): RedirectResponse
    {
        $this->assertStudent($student);
        $student = $this->persist($request, $student);
        $logs->log($request->user(), 'student_updated', $student);

        return back()->with('status', 'Student details updated.');
    }

    public function toggle(Request $request, User $student, ActivityLogService $logs): RedirectResponse
    {
        $this->assertStudent($student);
        $student->update(['is_active' => ! $student->is_active]);
        $logs->log($request->user(), $student->is_active ? 'student_activated' : 'student_archived', $student);

        return back()->with('status', 'Student status updated.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $student, ActivityLogService $logs): RedirectResponse
    {
        $this->assertStudent($student);
        $student->update(['password' => $request->string('password')->toString()]);
        $logs->log($request->user(), 'student_password_reset', $student);

        return back()->with('status', 'Student password reset.');
    }

    public function destroy(Request $request, User $student, ActivityLogService $logs): RedirectResponse
    {
        $this->assertStudent($student);
        if ($student->tributes()->exists()) {
            $student->update(['is_active' => false]);
            $logs->log($request->user(), 'student_archived', $student, ['reason' => 'related_tributes']);

            return back()->with('status', 'Student archived to preserve tribute history.');
        }
        $logs->log($request->user(), 'student_deleted', $student, ['email' => $student->email]);
        $student->delete();

        return redirect()->route('admin.students')->with('status', 'Unused student account permanently deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $students = $this->query($request)->get();

        return response()->streamDownload(function () use ($students): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Name', 'Username', 'Class', 'Section', 'Roll Number', 'Email', 'Phone', 'Status', 'Tributes', 'Approved', 'Pending', 'Rejected']);
            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->name,
                    $student->username,
                    $student->studentProfile?->class_name,
                    $student->studentProfile?->section,
                    $student->studentProfile?->roll_number,
                    $student->email,
                    $student->phone,
                    $student->is_active ? 'Active' : 'Inactive',
                    $student->tributes_count,
                    $student->approved_tributes_count,
                    $student->pending_tributes_count,
                    $student->rejected_tributes_count,
                ]);
            }
            fclose($handle);
        }, 'guruvandan-students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request): Builder
    {
        return User::query()
            ->where('role', Role::Student)
            ->with('studentProfile')
            ->withCount([
                'tributes',
                'tributes as approved_tributes_count' => fn ($q) => $q->where('status', 'approved'),
                'tributes as pending_tributes_count' => fn ($q) => $q->where('status', 'pending'),
                'tributes as rejected_tributes_count' => fn ($q) => $q->where('status', 'rejected'),
            ])
            ->when($request->filled('q'), fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where('name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('studentProfile', fn (Builder $profile) => $profile->where('class_name', 'like', $term));
            }))
            ->when($request->filled('class'), fn (Builder $query) => $query->whereHas('studentProfile', fn (Builder $profile) => $profile->where('class_name', $request->input('class'))))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->input('status') === 'active'))
            ->latest();
    }

    private function persist(StudentRequest $request, ?User $student = null): User
    {
        return DB::transaction(function () use ($request, $student): User {
            $student ??= new User;
            $student->fill([
                'name' => $request->string('name')->toString(),
                'username' => strtolower($request->string('username')->toString()),
                'email' => $request->filled('email') ? $request->string('email')->toString() : null,
                'phone' => $request->input('phone'),
                'role' => Role::Student,
                'is_active' => $request->boolean('is_active', true),
                'must_change_password' => $request->boolean('must_change_password', false),
            ]);
            if ($request->filled('password')) {
                $student->password = $request->string('password')->toString();
            }
            $student->save();
            $student->studentProfile()->updateOrCreate(['user_id' => $student->id], $request->safe()->only(['class_name', 'section', 'roll_number']));

            return $student->fresh('studentProfile');
        });
    }

    private function assertStudent(User $student): void
    {
        abort_unless($student->isStudent(), 404);
    }
}
