<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Meeting;
use Celerik\Ui\Adapters\Laravel\EloquentEventSource;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\Event;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Scheduler example. Changes are POSTed as JSON with the X-CSRF-TOKEN header, which
 * Laravel's CSRF middleware accepts natively. Add authorization (policies, gates or
 * middleware) to these routes in a real application.
 */
final class CelerikCalendarController extends Controller
{
    private const FIELDS = ['start' => 'starts_at', 'end' => 'ends_at', 'allDay' => 'all_day'];

    public function show(): View
    {
        return view('celerik.scheduler', ['scheduler' => $this->scheduler()]);
    }

    public function events(Request $request): JsonResponse
    {
        try {
            return response()->json(
                $this->scheduler()->respond(new EloquentEventSource(Meeting::query(), self::FIELDS), $request->query()),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function create(Request $request): JsonResponse
    {
        return $this->save(new Meeting(), $request);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->save(Meeting::query()->findOrFail($request->integer('id')), $request);
    }

    public function delete(Request $request): JsonResponse
    {
        Meeting::query()->findOrFail($request->integer('id'))->delete();

        return response()->json(['data' => null]);
    }

    private function save(Meeting $meeting, Request $request): JsonResponse
    {
        try {
            // Event::fromArray() validates the title, dates (ISO 8601, end >= start), color and URL.
            $event = Event::fromArray($request->only(['title', 'start', 'end', 'allDay']));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Eloquent stores a date's wall-clock time and drops its offset: convert to the app
        // time zone first, or "14:00+01:00" would be saved as 14:00 UTC.
        $timeZone = new DateTimeZone(config('app.timezone'));

        $meeting->fill([
            'title' => $event->title,
            'starts_at' => $event->start->setTimezone($timeZone),
            'ends_at' => $event->end?->setTimezone($timeZone),
            'all_day' => $event->allDay,
        ])->save();

        return response()->json(['data' => $this->toEvent($meeting)]);
    }

    private function toEvent(Meeting $meeting): Event
    {
        return new Event(
            $meeting->title,
            $meeting->starts_at->toImmutable(),
            $meeting->ends_at?->toImmutable(),
            $meeting->all_day,
            $meeting->id,
        );
    }

    private function scheduler(): Scheduler
    {
        return Scheduler::make('meetings')
            ->title('Meetings')
            ->url(route('celerik.scheduler.events'))
            ->view('week')
            ->firstDay(1)
            ->businessHours('08:00', '18:00')
            ->createUrl(route('celerik.scheduler.create'))
            ->updateUrl(route('celerik.scheduler.update'))
            ->deleteUrl(route('celerik.scheduler.delete'))
            ->csrf(csrf_token());
    }
}
