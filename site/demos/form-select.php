<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;

$countries = [
    'ar' => 'Argentina', 'au' => 'Australia', 'be' => 'Belgium', 'br' => 'Brazil', 'ca' => 'Canada',
    'cd' => 'DR Congo', 'eg' => 'Egypt', 'fr' => 'France', 'de' => 'Germany', 'in' => 'India',
    'jp' => 'Japan', 'ke' => 'Kenya', 'mx' => 'Mexico', 'ng' => 'Nigeria', 'za' => 'South Africa',
];

return Form::make('travel')
    ->action('/travel')
    ->add(
        Field::select('country', $countries, 'Destination')
            ->searchable()
            ->placeholder('Type a country')
            ->required(),
        Field::select('languages', ['en' => 'English', 'fr' => 'French', 'es' => 'Spanish', 'sw' => 'Swahili'])
            ->multiple()
            ->searchable()
            ->value(['en', 'fr'])
            ->help('Choose one or more.'),
    )
    ->submit('Save');
