<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;

return Form::make('booking')
    ->action('/bookings')
    ->add(
        Field::dateRange('stay')
            ->labels('Arrival', 'Departure')
            ->min('2026-01-01')
            ->required()
            ->help('The departure can be the arrival day.'),
        Field::time('check_in', 'Check-in time')->min('14:00')->max('22:00')->step(900)->value('15:00'),
        Field::dateRange('meeting', 'Meeting', 'datetime-local'),
    )
    ->submit('Book');
