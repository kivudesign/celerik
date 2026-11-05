<?php

/**
 * Modal, Tabs and Notification, included by index.php after session_start().
 *
 *   POST /newsletter   the form inside the modal: on errors the page reopens the modal,
 *                      on success it redirects and shows a notification (flash message).
 */

declare(strict_types=1);

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Support\Html;

/** @var string $path */
/** @var string|null $message Set by index.php after a successful contact form. */

$newsletterErrors = [];

if ($path === '/newsletter' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['_token'] ?? ''))) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    if (filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
        $newsletterErrors['email'] = 'Please enter a valid e-mail address.';
    } else {
        // Post/Redirect/Get: the message survives the redirect in the session.
        $_SESSION['flash']['success'][] = sprintf('%s is now subscribed.', $_POST['email']);
        header('Location: /#ui-title', true, 303);
        exit;
    }
}

$newsletterForm = Form::make('newsletter')
    ->action('/newsletter')
    ->csrf($_SESSION['csrf'])
    ->add(Field::email('email', 'E-mail address')->required()->help('One e-mail per month, no spam.'))
    ->errors($newsletterErrors)
    ->values($newsletterErrors === [] ? [] : $_POST)
    ->submit(null);

$newsletterModal = Modal::make('newsletter-modal')
    ->title('Subscribe to the newsletter')
    ->body($newsletterForm)
    ->cancelButton()
    ->button('Subscribe', 'primary', attributes: ['type' => 'submit', 'form' => 'newsletter'])
    ->staticBackdrop()
    ->open($newsletterErrors !== []);

$tabs = Tabs::make('features')
    ->label('Features')
    ->tab('grid', 'Grid', 'Server-side paging, sorting, search and export, with declared columns only.')
    ->tab('forms', 'Forms', Html::raw(
        '<p class="mb-0">Validation, input masks, uploads and a <strong>rich text editor</strong>.</p>',
    ))
    ->tab('calendar', 'Calendar', 'Month, week, day and list views, with changes saved through your endpoints.')
    ->rememberInHash();

$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
if ($message !== null) {
    $flash['success'][] = $message;
}

$notification = Notification::make('flash')->fromFlash($flash);
