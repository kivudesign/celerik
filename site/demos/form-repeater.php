<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Repeater;

return Form::make('invoice')
    ->action('/invoices')
    ->add(
        Field::text('customer')->required(),
        Repeater::make('lines', 'Invoice lines')
            ->schema(
                Field::text('description')->required()->columnSpan(2),
                Field::number('quantity')->min(1)->value(1)->required(),
                Field::number('price', 'Unit price')->min(0)->step(0.01)->required(),
            )
            ->columns(4)
            ->min(1)
            ->max(10)
            ->reorderable()
            ->labels(add: 'Add a line', item: 'Line %d')
            ->help('Up to 10 lines.')
            ->value([
                ['description' => 'Design workshop', 'quantity' => 1, 'price' => 900],
                ['description' => 'Component library, per screen', 'quantity' => 12, 'price' => 150],
            ]),
    )
    ->submit('Save invoice');
