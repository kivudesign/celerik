<?php

/**
 * The ThemeBuilder test page, included by server.php (GET /theme-builder?theme=…&mode=…).
 */

declare(strict_types=1);

use Celerik\Ui\Assets;
use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Theme\Theme;

/** @var string $theme */
/** @var string $mode */

$builder = ThemeBuilder::make('builder')
    ->theme(Theme::preset($theme))
    ->assetsUrl('/assets')
    ->preview(
        Form::make('preview-form')
            ->add(
                Field::text('name')->required(),
                Field::email('email')->required()->help('We never share your e-mail.'),
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
        Tabs::make('preview-tabs')
            ->label('Settings')
            ->tab('profile', 'Profile', 'Your name and e-mail.')
            ->tab('security', 'Security', 'Change your password.'),
        Chart::make('preview-chart')
            ->type('line')
            ->title('Revenue')
            ->labels(['Jan', 'Feb', 'Mar'])
            ->dataset('North', [12, 19, 15])
            ->dataset('South', [9, 11, 17])
            ->height(200),
    );
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= $mode ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title>Celerik theme builder (<?= $theme ?>)</title>
    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
    <script src="/vendor/jquery/jquery.min.js" defer></script>
    <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js" defer></script>
    <?= Assets::tags('/assets', theme: $theme) ?>
</head>
<body>
<main class="container-fluid py-4">
    <h1 class="visually-hidden">Theme builder</h1>
    <?= $builder->render() ?>
</main>
</body>
</html>
