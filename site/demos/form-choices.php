<?php

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;

return Form::make('preferences')
    ->action('/preferences')
    ->add(
        Field::radio('plan', ['free' => 'Free', 'team' => 'Team', 'business' => 'Business'])->required(),
        Field::checkboxes('topics', ['news' => 'News', 'releases' => 'Releases', 'security' => 'Security'], 'Topics')
            ->inline()
            ->required()
            ->help('Choose at least one.'),
        Field::switch('newsletter', 'Monthly newsletter')->value(true),
        Field::range('volume', 'Notification volume')->min(0)->max(10)->value(6),
        Field::color('accent', 'Accent color')->value('#0d6efd'),
    )
    ->submit('Save preferences');
