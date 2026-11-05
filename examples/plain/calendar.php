<?php

/**
 * Scheduler part of the plain PHP example, included by index.php after session_start().
 *
 * Events are stored in the session to keep the demo self-contained. Endpoints:
 *   GET  /events.json      events for the visible range (Scheduler::respond())
 *   POST /events/create    JSON {title, start, end, allDay}         → {data: event}
 *   POST /events/update    JSON {id, title, start, end, allDay}     → {data: event}
 *   POST /events/delete    JSON {id}                                → {data: null}
 * Every POST must carry the CSRF token in the X-CSRF-TOKEN header (the Scheduler sends it).
 */

declare(strict_types=1);

use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\ArrayEventSource;
use Celerik\Ui\Data\Event;

/** @var string $path */

if (!isset($_SESSION['events'])) {
    $monday = new DateTimeImmutable('monday this week');
    $_SESSION['events'] = [
        1 => (new Event('Team stand-up', $monday->modify('+1 day 09:00'), $monday->modify('+1 day 09:15'), id: 1))
            ->toArray(),
        2 => (new Event('Sprint planning', $monday->modify('+2 days 14:00'), $monday->modify('+2 days 15:30'), id: 2))
            ->toArray(),
        3 => (new Event('Company holiday', $monday->modify('+4 days'), allDay: true, id: 3, color: '#198754'))
            ->toArray(),
    ];
    $_SESSION['next_event_id'] = 4;
}

$scheduler = Scheduler::make('calendar')
    ->title('Team calendar')
    ->url('/events.json')
    ->view('week')
    ->firstDay(1)
    ->businessHours('08:00', '18:00')
    ->slotTimes('07:00', '20:00')
    ->createUrl('/events/create')
    ->updateUrl('/events/update')
    ->deleteUrl('/events/delete')
    ->csrf($_SESSION['csrf']);

/**
 * @param array<string, mixed> $body
 */
$json = static function (int $status, array $body): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
};

if ($path === '/events.json') {
    try {
        $json(200, $scheduler->respond(new ArrayEventSource(array_values($_SESSION['events'])), $_GET)->toArray());
    } catch (InvalidArgumentException $e) {
        $json(400, ['error' => $e->getMessage()]);
    }
}

if (in_array($path, ['/events/create', '/events/update', '/events/delete'], true)) {
    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !hash_equals($_SESSION['csrf'], $token)) {
        $json(419, ['error' => 'Invalid CSRF token.']);
    }

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $json(400, ['error' => 'Expected a JSON object.']);
    }

    $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);

    if ($path === '/events/delete') {
        unset($_SESSION['events'][$id]);
        $json(200, ['data' => null]);
    }

    if ($path === '/events/update' && ($id === false || !isset($_SESSION['events'][$id]))) {
        $json(404, ['error' => 'Unknown event.']);
    }

    try {
        $id = $path === '/events/create' ? $_SESSION['next_event_id']++ : $id;
        $event = Event::fromArray(['id' => $id] + $payload);
    } catch (InvalidArgumentException $e) {
        $json(422, ['error' => $e->getMessage()]);
    }

    $_SESSION['events'][$id] = $event->toArray();
    $json(200, ['data' => $event->toArray()]);
}
