<?php

/**
 * Renders the PHP components to HTML fixtures used by the Jest and axe-core tests,
 * so the JS wrappers are always tested against the markup PHP actually produces.
 */

declare(strict_types=1);

use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\FileManager;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Support\Html;
use Celerik\Ui\Theme\Theme;
use Celerik\Ui\Upload\UploadRules;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$fixtures = [
    'scheduler' => Scheduler::make('team-calendar')
        ->title('Team calendar')
        ->view('list')
        ->date('2026-11-05')
        ->timeZone('UTC')
        ->firstDay(1)
        ->events([
            ['id' => 1, 'title' => 'Kick-off', 'start' => '2026-11-03T09:00:00Z', 'end' => '2026-11-03T10:00:00Z'],
            ['id' => 2, 'title' => 'Design review', 'start' => '2026-11-05T14:00:00Z', 'end' => '2026-11-05T15:30:00Z'],
            ['id' => 3, 'title' => 'Holiday', 'start' => '2026-11-06'],
        ])
        ->updateUrl('/events/update')
        ->csrf('test-token'),

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

    'filemanager' => FileManager::make('files')
        ->title('Shared files')
        ->url('/files/connector')
        ->assetsUrl('/elfinder'),

    'modal' => Modal::make('contact-modal')
        ->title('Contact us')
        ->body(Form::make('modal-form')->add(Field::text('name')->required(), Field::email('email')->required())->submit(null))
        ->cancelButton()
        ->button('Send', 'primary', attributes: ['type' => 'submit', 'form' => 'modal-form'])
        ->staticBackdrop(),

    'tabs' => Tabs::make('account')
        ->label('Account settings')
        ->tab('profile', 'Profile', 'Your name and e-mail.')
        ->tab('security', 'Security', Html::raw('<p>Change your password.</p>'))
        ->tab('billing', 'Billing', 'Invoices and payment methods.')
        ->rememberInHash(),

    'notification' => Notification::make('flash')
        ->success('Profile saved.')
        ->error('Tom & Jerry <img src=x onerror="window.pwned=1">')
        ->warning('Disk almost full.'),

    'form-rich' => Form::make('article')
        ->action('/articles')
        ->csrf('test-token')
        ->add(
            Field::text('title')->required(),
            Field::editor('body', 'Article')
                ->required()
                ->help('Headings, lists and links are allowed.')
                ->value('<h2>Intro</h2><p>Hello <strong>world</strong></p><script>alert(1)</script>'),
            Field::file('cover', 'Cover image')->rules(UploadRules::images()->maxSize('2M'))->upload('/uploads')->required(),
            Field::file('attachments')->rules(UploadRules::documents()->maxFiles(3))->upload('/uploads'),
        )
        ->submit('Publish'),

    'form-summary' => Form::make('order')
        ->errorSummary()
        ->add(
            Field::text('name')->required(),
            Field::email('email')->required(),
            Field::editor('notes')->required(),
        )
        ->submit('Order'),

    'themebuilder' => ThemeBuilder::make('builder')
        ->theme(Theme::preset('material')->color('light', 'danger', '#dd3545'))
        ->saveUrl('/theme')
        ->csrf('test-token')
        ->preview(Form::make('preview-form')->add(Field::text('name'))->submit('Send')),

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

// Stylesheets generated from the presets: compared with the compiled themes (presets.test.js).
foreach (Theme::BASES as $base) {
    file_put_contents(__DIR__ . '/theme-' . $base . '.css', Theme::preset($base)->toCss());
}

// The teal browser-test theme on each base, with a font and a whole-number radius:
// resources/js/theme-css.js must write the same text (theme-css.test.js).
$custom = Theme::fromJson((string) file_get_contents(dirname(__DIR__, 2) . '/browser/custom-theme.json'))
    ->fontFamily('"Inter", system-ui, sans-serif')
    ->radius(1.0);
foreach (Theme::BASES as $base) {
    file_put_contents(__DIR__ . '/theme-custom-' . $base . '.css', $custom->base($base)->toCss());
}

echo 'Wrote ' . count($fixtures) . ' fixtures and ' . count(Theme::BASES) . " theme stylesheets to tests/js/fixtures\n";
