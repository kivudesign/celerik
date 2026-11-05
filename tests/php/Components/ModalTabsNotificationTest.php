<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ModalTabsNotificationTest extends TestCase
{
    public function testStringContentIsEscapedAndStringableContentIsHtml(): void
    {
        self::assertSame('&lt;b&gt;x&lt;/b&gt;', Html::content('<b>x</b>'));
        self::assertSame('<b>x</b>', Html::content(Html::raw('<b>x</b>')));
    }

    public function testModalRendersALabelledDialogWithFooterAndTrigger(): void
    {
        $modal = Modal::make('contact-modal')
            ->title('Contact <us>')
            ->body(Form::make('contact')->add(Field::text('name'))->submit(null))
            ->cancelButton()
            ->button('Send', 'primary', attributes: ['type' => 'submit', 'form' => 'contact'])
            ->size('lg')
            ->centered()
            ->staticBackdrop();

        $html = $modal->render();

        self::assertStringContainsString(
            '<div id="contact-modal" class="modal fade celerik-modal" data-celerik="modal" data-celerik-options="{}" tabindex="-1" '
            . 'aria-labelledby="contact-modal-title" aria-hidden="true" data-bs-backdrop="static">',
            $html,
        );
        self::assertStringNotContainsString('data-bs-keyboard', $html, 'Escape must keep closing the dialog.');
        self::assertStringContainsString('<div class="modal-dialog modal-lg modal-dialog-centered">', $html);
        self::assertStringContainsString('<h2 class="modal-title fs-5" id="contact-modal-title">Contact &lt;us&gt;</h2>', $html);
        self::assertStringContainsString('<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>', $html);
        self::assertStringContainsString('<form id="contact"', $html);
        self::assertStringContainsString('<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>', $html);
        self::assertStringContainsString('<button type="submit" form="contact" class="btn btn-primary">Send</button>', $html);

        self::assertSame(
            '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contact-modal" aria-haspopup="dialog">Write to us</button>',
            $modal->trigger('Write to us'),
        );
    }

    public function testModalCanOpenOnLoad(): void
    {
        self::assertSame(['open' => true], Modal::make()->open()->toArray());
        self::assertSame([], Modal::make()->toArray());
    }

    public function testTabsFollowTheAriaTabsPattern(): void
    {
        $html = Tabs::make('account')
            ->label('Account settings')
            ->tab('profile', 'Profile', 'Name & <e-mail>')
            ->tab('security', 'Security', Html::raw('<p>Password</p>'))
            ->active('security')
            ->style('pills')
            ->rememberInHash()
            ->render();

        self::assertStringContainsString('<ul class="nav nav-pills mb-3" role="tablist" aria-label="Account settings">', $html);
        self::assertStringContainsString(
            '<button type="button" class="nav-link" id="account-profile-tab" data-bs-toggle="tab" data-bs-target="#account-profile" '
            . 'data-celerik-tab="profile" role="tab" aria-controls="account-profile" aria-selected="false" tabindex="-1">Profile</button>',
            $html,
        );
        self::assertStringContainsString('class="nav-link active" id="account-security-tab"', $html);
        self::assertStringContainsString('aria-selected="true">Security</button>', $html);
        self::assertStringContainsString(
            '<div class="tab-pane fade" id="account-profile" role="tabpanel" aria-labelledby="account-profile-tab" tabindex="0">Name &amp; &lt;e-mail&gt;</div>',
            $html,
        );
        self::assertStringContainsString('<div class="tab-pane fade show active" id="account-security"', $html);
        self::assertStringContainsString('<p>Password</p>', $html);
        self::assertStringContainsString('data-celerik-options="{&quot;rememberInHash&quot;:true}"', $html);
    }

    /**
     * @return iterable<string, array{callable(): mixed}>
     */
    public static function invalidUsage(): iterable
    {
        yield 'no tabs' => [static fn () => Tabs::make()->render()];
        yield 'unknown active tab' => [static fn () => Tabs::make()->tab('a', 'A', 'x')->active('b')->render()];
        yield 'duplicate tab' => [static fn () => Tabs::make()->tab('a', 'A', 'x')->tab('a', 'B', 'y')];
        yield 'invalid tab id' => [static fn () => Tabs::make()->tab('a b', 'A', 'x')];
        yield 'unknown tab style' => [static fn () => Tabs::make()->style('cards')];
        yield 'unknown modal size' => [static fn () => Modal::make()->size('huge')];
        yield 'unknown button variant' => [static fn () => Modal::make()->button('x', 'purple')];
        yield 'unknown notification type' => [static fn () => Notification::make()->add('fatal', 'x')];
        yield 'invalid position' => [static fn () => Notification::make()->position('middle', 'top')];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidUsage')]
    public function testRejectsInvalidUsage(callable $use): void
    {
        $this->expectException(InvalidArgumentException::class);

        $use();
    }

    public function testNotificationMapsFlashMessagesAndKeepsErrorsUntilDismissed(): void
    {
        $notification = Notification::make('flash')
            ->fromFlash([
                'status' => 'Profile saved.',
                'danger' => ['Payment failed.', ''],
                'notice' => 'Maintenance tonight.',
                '_previous' => ['url' => '/x'],
                'warning' => ['Disk <almost> full.'],
            ])
            ->position('center', 'bottom');

        self::assertSame([
            ['type' => 'success', 'message' => 'Profile saved.'],
            ['type' => 'error', 'message' => 'Payment failed.'],
            ['type' => 'info', 'message' => 'Maintenance tonight.'],
            ['type' => 'warning', 'message' => 'Disk <almost> full.'],
        ], $notification->getMessages());

        $options = $notification->toArray();
        self::assertSame(['x' => 'center', 'y' => 'bottom'], $options['position']);
        self::assertSame(0, $options['errorDuration']);
        self::assertTrue($options['dismissible']);
        self::assertArrayNotHasKey('labels', $options);
        self::assertSame(['dismiss' => 'Fermer'], Notification::make()->dismissLabel('Fermer')->toArray()['labels']);
    }

    public function testNotificationFallbackIsEscapedAndUsesTheRightLiveRegionRole(): void
    {
        $html = Notification::make('flash')->success('Saved <b>!</b>')->error('Failed')->render();

        self::assertStringContainsString('<noscript><div class="alert alert-success" role="status">Saved &lt;b&gt;!&lt;/b&gt;</div>', $html);
        self::assertStringContainsString('<div class="alert alert-danger" role="alert">Failed</div></noscript>', $html);
        self::assertStringNotContainsString('<noscript>', Notification::make()->render());
    }
}
