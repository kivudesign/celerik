<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;

return Form::make('signup')
    ->action('/signup')
    ->errorSummary()
    ->add(
        Field::text('name')->required()->maxLength(80),
        Field::email('email', 'E-mail')->required()->help('We never share your e-mail.'),
        Field::tel('phone')->mask('(999) 999-9999')->help('Optional.'),
        Field::password('password')->required()->minLength(8),
        Field::select('country', ['fr' => 'France', 'ca' => 'Canada', 'cd' => 'DR Congo'])->placeholder('Choose...'),
        Field::checkbox('terms', 'I accept the terms')->required(),
    )
    ->submit('Create account');
