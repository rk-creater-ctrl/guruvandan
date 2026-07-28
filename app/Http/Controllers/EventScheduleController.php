<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\EventScheduleRequest;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventScheduleController extends Controller
{
    public function store(EventScheduleRequest $request, Event $event, ActivityLogService $logs): RedirectResponse
    {
        $schedule = $event->schedules()->create([
            ...$request->validated(),
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);
        $logs->log($request->user(), 'event_schedule_created', $schedule);

        return back()->with('status', 'Schedule item added.');
    }

    public function update(EventScheduleRequest $request, EventSchedule $schedule, ActivityLogService $logs): RedirectResponse
    {
        $schedule->update([
            ...$request->validated(),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);
        $logs->log($request->user(), 'event_schedule_updated', $schedule);

        return back()->with('status', 'Schedule item updated.');
    }

    public function destroy(Request $request, EventSchedule $schedule, ActivityLogService $logs): RedirectResponse
    {
        $logs->log($request->user(), 'event_schedule_deleted', $schedule, ['title' => $schedule->title]);
        $schedule->delete();

        return back()->with('status', 'Schedule item deleted.');
    }

    public function reorder(Request $request, Event $event, ActivityLogService $logs): RedirectResponse
    {
        $data = $request->validate([
            'schedule_ids' => ['required', 'array'],
            'schedule_ids.*' => ['integer', 'distinct', 'exists:event_schedules,id'],
        ]);
        foreach ($data['schedule_ids'] as $position => $id) {
            $event->schedules()->whereKey($id)->update(['sort_order' => $position]);
        }
        $logs->log($request->user(), 'event_schedule_reordered', $event);

        return back()->with('status', 'Schedule order updated.');
    }
}
