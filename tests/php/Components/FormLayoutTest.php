<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Section;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class FormLayoutTest extends TestCase
{
    public function testColumnsAndSpans(): void
    {
        $html = Form::make('f')->submit(null)->columns(3, 'lg')->add(
            Field::text('first_name'),
            Field::text('last_name')->columnSpan(2),
            Field::hidden('id', 7),
            Field::textarea('bio')->columnSpan('full'),
            Field::text('nickname')->columnSpan(5),
        )->render();

        self::assertStringContainsString('<div class="row"><div class="col-12 col-lg-4"><div class="mb-3"><label for="f-first_name"', $html);
        self::assertStringContainsString('<div class="col-12 col-lg-8"><div class="mb-3"><label for="f-last_name"', $html);
        // A hidden input takes no column; a span wider than the grid is the full width.
        self::assertStringContainsString('</div></div><input type="hidden" id="f-id" name="id" value="7"><div class="col-12 col-lg-12"><div class="mb-3"><label for="f-bio"', $html);
        self::assertStringContainsString('<div class="col-12 col-lg-12"><div class="mb-3"><label for="f-nickname"', $html);
    }

    public function testOneColumnRendersNoGrid(): void
    {
        $html = Form::make('f')->submit(null)->add(Field::text('name')->columnSpan(2))->render();

        self::assertStringNotContainsString('class="row"', $html);
        self::assertStringNotContainsString('col-', $html);
    }

    public function testSectionsHaveAHeadingDescriptionAndTheirOwnColumns(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Section::make('Shipping address')
                ->description('Where we send the order.')
                ->columns(2)
                ->add(
                    Field::text('street')->columnSpan('full'),
                    Field::text('city'),
                    Section::make('Delivery notes')->add(Field::textarea('notes')),
                ),
        )->render();

        self::assertStringContainsString(
            '<section id="f-section-Shipping-address" class="card mb-4 celerik-form-section"'
            . ' aria-labelledby="f-section-Shipping-address-heading"'
            . ' aria-describedby="f-section-Shipping-address-description"><div class="card-body">'
            . '<h2 id="f-section-Shipping-address-heading" class="card-title h5 mb-1">Shipping address</h2>'
            . '<p id="f-section-Shipping-address-description" class="form-text mt-0 mb-3">Where we send the order.</p>'
            . '<div class="row"><div class="col-12 col-md-12"><div class="mb-3"><label for="f-street"',
            $html,
        );
        self::assertStringContainsString('<div class="col-12 col-md-6"><div class="mb-3"><label for="f-city"', $html);
        // A section in a section has the next heading level.
        self::assertStringContainsString('<h3 id="f-section-Delivery-notes-heading" class="card-title h6 mb-3">Delivery notes</h3>', $html);
    }

    public function testFieldsetsHaveALegend(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Section::fieldset('Address')->id('address')->columns(2)->add(Field::text('city'), Field::text('zip')),
        )->render();

        self::assertStringContainsString(
            '<fieldset id="address" class="celerik-form-fieldset mb-3"><legend class="celerik-form-legend">Address</legend><div class="row">',
            $html,
        );
    }

    public function testCollapsibleSectionsOpenWhenAFieldInsideHasAnError(): void
    {
        $form = Form::make('f')->submit(null)->errorSummary()->add(
            Section::make('Billing')->collapsible(collapsed: true)->add(Field::text('vat')),
            Section::make('Notes')->collapsible(collapsed: true)->add(Field::text('note')),
            Section::make('Shipping')->collapsible()->add(Field::text('street')),
        );
        $html = $form->errors(['vat' => 'The VAT number is invalid.'])->render();

        self::assertStringContainsString(
            '<details id="f-section-Billing" class="card mb-4 celerik-form-section" open>'
            . '<summary id="f-section-Billing-heading" class="celerik-form-summary card-body h5">Billing</summary>'
            . '<div class="card-body pt-0" role="group" aria-labelledby="f-section-Billing-heading">',
            $html,
        );
        self::assertStringContainsString('<details id="f-section-Notes" class="card mb-4 celerik-form-section"><summary', $html);
        self::assertStringContainsString('<details id="f-section-Shipping" class="card mb-4 celerik-form-section" open>', $html);
        // Fields in sections are in the error summary and found by getFields().
        self::assertStringContainsString('<a href="#f-vat" class="alert-link">The VAT number is invalid.</a>', $html);
        self::assertSame(['vat', 'note', 'street'], array_map(static fn (Field $field): string => $field->getName(), $form->getFields()));
    }

    public function testAFileFieldInASectionMakesAMultipartForm(): void
    {
        $html = Form::make('f')->add(Section::make('Avatar')->add(Field::file('avatar')))->render();

        self::assertStringContainsString('enctype="multipart/form-data"', $html);
    }

    public function testRejectsUnsupportedLayouts(): void
    {
        $attempts = [
            static fn () => Form::make('f')->columns(5),
            static fn () => Form::make('f')->columns(2, 'huge'),
            static fn () => Section::make('A')->columns(0),
            static fn () => Field::text('a')->columnSpan(0),
            static fn () => Field::text('a')->columnSpan('half'),
            static fn () => Section::fieldset('A')->collapsible(),
        ];

        foreach ($attempts as $index => $attempt) {
            try {
                $attempt();
                self::fail(sprintf('Attempt %d was accepted.', $index));
            } catch (InvalidArgumentException | LogicException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
