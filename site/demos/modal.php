<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Modal;

$modal = Modal::make('contact')
    ->title('Contact us')
    ->body(Form::make('contact-form')->add(Field::email('email', 'E-mail')->required())->submit(null))
    ->cancelButton()
    ->button('Send', 'primary', attributes: ['type' => 'submit', 'form' => 'contact-form']);

return [$modal->trigger('Contact us'), $modal];
