<?php

/**
 * Renders the PHP components to HTML fixtures used by the Jest and axe-core tests,
 * so the JS wrappers are always tested against the markup PHP actually produces.
 */

declare(strict_types=1);

use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$fixtures = [
    'grid' => Grid::make('users')
        ->caption('Users')
        ->column('id', 'ID')
        ->column('name')
        ->column('email', 'E-mail')
        ->export('copy', 'csv')
        ->pageSize(2)
        ->pageSizes([2, 10])
        ->rows([
            ['id' => 1, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['id' => 2, 'name' => 'Alan Turing', 'email' => 'alan@example.com'],
            ['id' => 3, 'name' => 'Grace Hopper', 'email' => 'grace@example.com'],
        ]),

    'chart' => Chart::make('sales')
        ->type('bar')
        ->title('Monthly revenue')
        ->labels(['Jan', 'Feb', 'Mar'])
        ->dataset('Revenue', [1200, 1900, 1500]),

    'form' => Form::make('signup')
        ->action('/signup')
        ->csrf('test-token')
        ->add(
            Field::text('name')->required()->maxLength(80),
            Field::email('email')->required()->help('We never share your e-mail.'),
            Field::tel('phone')->mask('(999) 999-9999'),
            Field::password('password')->required()->minLength(8),
            Field::password('password_confirmation', 'Confirm password')->required()->equalTo('password'),
            Field::select('country', ['fr' => 'France', 'ca' => 'Canada'])->placeholder('Choose...'),
            Field::checkbox('terms', 'I accept the terms')->required(),
        )
        ->errors(['email' => 'This e-mail is already registered.'])
        ->submit('Create account'),
];

foreach ($fixtures as $name => $component) {
    file_put_contents(__DIR__ . '/' . $name . '.html', $component->render() . "\n");
}

echo 'Wrote ' . count($fixtures) . " fixtures to tests/js/fixtures\n";
