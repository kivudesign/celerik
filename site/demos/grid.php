<?php

use Celerik\Ui\Components\Grid;

return Grid::make('users')
    ->caption('Team members')
    ->column('name', 'Name')
    ->column('role', 'Role')
    ->column('city', 'City')
    ->export('copy', 'csv')
    ->pageSize(5)
    ->rows([
        ['name' => 'Ada Lovelace', 'role' => 'Engineer', 'city' => 'London'],
        ['name' => 'Alan Turing', 'role' => 'Researcher', 'city' => 'Manchester'],
        ['name' => 'Grace Hopper', 'role' => 'Admiral', 'city' => 'Arlington'],
        ['name' => 'Katherine Johnson', 'role' => 'Mathematician', 'city' => 'Hampton'],
        ['name' => 'Linus Torvalds', 'role' => 'Maintainer', 'city' => 'Portland'],
        ['name' => 'Margaret Hamilton', 'role' => 'Director', 'city' => 'Boston'],
        ['name' => 'Tim Berners-Lee', 'role' => 'Inventor', 'city' => 'Geneva'],
    ]);
