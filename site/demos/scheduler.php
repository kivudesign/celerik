<?php

use Celerik\Ui\Components\Scheduler;

// Read-only here. With createUrl(), updateUrl() and deleteUrl() users can add,
// move (by mouse or keyboard) and delete events through your endpoints.
return Scheduler::make('team-calendar')
    ->title('Team calendar')
    ->view('month')
    ->date('2026-11-05')
    ->timeZone('UTC')
    ->firstDay(1)
    ->events([
        ['id' => 1, 'title' => 'Kick-off', 'start' => '2026-11-03T09:00:00Z', 'end' => '2026-11-03T10:00:00Z'],
        ['id' => 2, 'title' => 'Design review', 'start' => '2026-11-05T14:00:00Z', 'end' => '2026-11-05T15:30:00Z'],
        ['id' => 3, 'title' => 'Release', 'start' => '2026-11-12', 'color' => '#198754'],
        ['id' => 4, 'title' => 'Holidays', 'start' => '2026-11-23', 'end' => '2026-11-28'],
    ]);
