<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Section;

return Form::make('signup')
    ->action('/signup')
    ->columns(2)
    ->add(
        Field::text('first_name')->required(),
        Field::text('last_name')->required(),
        Field::email('email')->required()->columnSpan('full'),
        Section::make('Address')
            ->description('Where we send your membership card.')
            ->columnSpan('full')
            ->columns(3)
            ->add(
                Field::text('street')->columnSpan('full'),
                Field::text('city')->columnSpan(2),
                Field::text('zip', 'ZIP code'),
            ),
        Section::fieldset('Contact preferences')
            ->columnSpan('full')
            ->add(
                Field::checkboxes('channels', ['email' => 'E-mail', 'sms' => 'SMS', 'post' => 'Post'])->inline(),
            ),
        Section::make('Company')
            ->description('Only if you sign up for a business.')
            ->collapsible(collapsed: true)
            ->columnSpan('full')
            ->columns(2)
            ->add(
                Field::text('company'),
                Field::text('vat', 'VAT number')->pattern('[A-Z]{2}[0-9A-Z]{2,12}'),
            ),
    )
    ->submit('Sign up');
