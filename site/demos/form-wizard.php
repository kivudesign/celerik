<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Section;

return Form::make('onboarding')
    ->action('/onboarding')
    ->wizard()
    ->add(
        Section::make('Account')
            ->description('How we contact you.')
            ->columns(2)
            ->add(
                Field::text('name')->required(),
                Field::email('email')->required(),
            ),
        Section::make('Company')
            ->columns(2)
            ->add(
                Field::text('company')->required(),
                Field::select('size', ['1' => 'Just me', '10' => '2 to 10', '50' => '11 to 50'], 'Team size')
                    ->placeholder('Choose...')
                    ->required(),
            ),
        Section::make('Confirm')->add(
            Field::switch('newsletter', 'Send me product news'),
            Field::checkbox('terms', 'I accept the terms')->required(),
        ),
    )
    ->submit('Create my account');
