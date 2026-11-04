<?php

declare(strict_types=1);

$first = ['Ada', 'Alan', 'Grace', 'Linus', 'Margaret', 'Dennis', 'Barbara', 'Ken', 'Frances', 'Tim'];
$last = [
    'Lovelace', 'Turing', 'Hopper', 'Torvalds', 'Hamilton', 'Ritchie', 'Liskov', 'Thompson', 'Allen', 'Berners-Lee',
];
$cities = ['Paris', 'London', 'Montreal', 'Berlin', 'Dakar'];

$people = [];
for ($i = 1; $i <= 250; $i++) {
    $name = $first[$i % 10] . ' ' . $last[intdiv($i, 10) % 10];
    $people[] = [
        'id' => $i,
        'name' => $name,
        'email' => strtolower(str_replace(' ', '.', $name)) . $i . '@example.com',
        'city' => $cities[$i % 5],
    ];
}

return $people;
