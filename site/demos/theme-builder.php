<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Theme\Theme;

// Export downloads the theme; nothing is saved here (no saveUrl()).
return ThemeBuilder::make('builder')
    ->theme(Theme::preset('bootstrap'))
    ->preview(
        Form::make('preview-form')
            ->add(
                Field::text('name')->required(),
                Field::email('email', 'E-mail')->required()->help('We never share your e-mail.'),
                Field::checkbox('terms', 'I accept the terms'),
            )
            ->errors(['email' => 'This e-mail is already registered.'])
            ->submit('Create account'),
        Grid::make('preview-grid')
            ->caption('Users')
            ->column('name')
            ->column('email', 'E-mail')
            ->pageSize(2)
            ->rows([
                ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
                ['name' => 'Alan Turing', 'email' => 'alan@example.com'],
                ['name' => 'Grace Hopper', 'email' => 'grace@example.com'],
            ]),
    );
