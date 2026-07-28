<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\BulkModerationRequest;
use App\Http\Requests\Admin\ModerateTributeRequest;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MediaUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        $tributes = Tribute::query()
            ->with(['student.studentProfile', 'teacher.user', 'media'])
            ->when($request->filled('q'), fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where('title', 'like', $term)->orWhere('message', 'like', $term);
            }))
            ->when($request->filled('teacher'), fn (Builder $query) => $query->where('teacher_id', $request->integer('teacher')))
            ->when($request->filled('student'), fn (Builder $query) => $query->where('student_id', $request->integer('student')))
            ->when($request->filled('class'), fn (Builder $query) => $query->whereHas('student.studentProfile', fn (Builder $profile) => $profile->where('class_name', $request->input('class'))))
            ->when($request->filled('type'), fn (Builder $query) => $query->where('tribute_type', $request->input('type')))
            ->when($request->filled('language'), fn (Builder $query) => $query->where('language', $request->input('language')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->filled('featured'), fn (Builder $query) => $query->where('is_featured', $request->input('featured') === 'yes'))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 ELSE 2 END")
            ->orderBy('created_at', $request->input('sort') === 'oldest' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tributes', [
            'tributes' => $tributes,
            'teachers' => Teacher::query()->with('user')->orderBy('id')->get(),
            'students' => User::query()->where('role', Role::Student)->orderBy('name')->get(),
            'classes' => User::query()->where('role', Role::Student)->join('student_profiles', 'users.id', '=', 'student_profiles.user_id')->distinct()->orderBy('class_name')->pluck('class_name'),
        ]);
    }

    public function show(Tribute $tribute): View
    {
        return view('admin.tribute-show', ['tribute' => $tribute->load('student.studentProfile', 'teacher.user', 'media', 'approver')]);
    }

    public function update(ModerateTributeRequest $request, Tribute $tribute, ActivityLogService $logs): RedirectResponse
    {
        $before = $tribute->only(['status', 'title', 'message', 'is_featured', 'rejection_reason']);
        $title = $request->filled('title') ? $request->string('title')->toString() : $tribute->title;
        $message = $request->has('message') ? $request->input('message') : $tribute->message;
        $edited = $title !== $tribute->title || $message !== $tribute->message;

        $tribute->update([
            'title' => $title,
            'message' => $message,
            'original_message' => $edited ? ($tribute->original_message ?? $tribute->message) : $tribute->original_message,
            'moderator_edited_at' => $edited ? now() : $tribute->moderator_edited_at,
            'status' => $request->input('status'),
            'rejection_reason' => $request->input('status') === 'rejected' ? $request->input('rejection_reason') : null,
            'approved_by' => $request->user()->id,
            'approved_at' => $request->input('status') === 'approved' ? now() : null,
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $logs->log($request->user(), 'tribute_moderated', $tribute, [
            'previous_status' => $before['status'] instanceof \BackedEnum ? $before['status']->value : $before['status'],
            'new_status' => $tribute->status->value,
            'rejection_reason' => $tribute->rejection_reason,
            'text_edited' => $edited,
        ]);

        return back()->with('status', 'Tribute moderation saved.');
    }

    public function bulk(BulkModerationRequest $request, ActivityLogService $logs): RedirectResponse
    {
        DB::transaction(function () use ($request, $logs): void {
            Tribute::query()->whereKey($request->input('tribute_ids'))->each(function (Tribute $tribute) use ($request, $logs): void {
                $previous = $tribute->status->value;
                $status = $request->input('action') === 'approve' ? 'approved' : 'rejected';
                $tribute->update([
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? $request->input('rejection_reason') : null,
                    'approved_by' => $request->user()->id,
                    'approved_at' => $status === 'approved' ? now() : null,
                ]);
                $logs->log($request->user(), 'tribute_bulk_'.$status, $tribute, [
                    'previous_status' => $previous,
                    'new_status' => $status,
                    'rejection_reason' => $tribute->rejection_reason,
                ]);
            });
        });

        return back()->with('status', 'Selected tributes moderated.');
    }

    public function destroy(Request $request, Tribute $tribute, MediaUploadService $media, ActivityLogService $logs): RedirectResponse
    {
        $logs->log($request->user(), 'tribute_deleted', $tribute, ['title' => $tribute->title, 'status' => $tribute->status->value]);
        $media->deleteAll($tribute);
        $tribute->delete();

        return redirect()->route('admin.tributes')->with('status', 'Unsafe tribute and its media were deleted.');
    }
}
