<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Repeater;
use Celerik\Ui\Components\Form\Section;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class FormRepeaterTest extends TestCase
{
    private static function lines(): Repeater
    {
        return Repeater::make('lines', 'Order lines')
            ->schema(Field::text('product')->required(), Field::number('qty', 'Quantity')->min(1))
            ->columns(2)
            ->min(1)
            ->max(3)
            ->labels(add: 'Add a line', item: 'Line %d');
    }

    public function testRendersOneEmptyRowAndATemplate(): void
    {
        $html = Form::make('f')->submit(null)->add(self::lines())->render();

        self::assertStringContainsString(
            '<div id="f-lines" class="mb-3 celerik-repeater" tabindex="-1" data-celerik-repeater="{&quot;min&quot;:1,&quot;max&quot;:3,&quot;item&quot;:&quot;Line %d&quot;}">'
            . '<fieldset><legend class="form-label fs-6">Order lines <span class="celerik-required" aria-hidden="true">*</span></legend>'
            . '<ol class="celerik-repeater-items"><li class="card celerik-repeater-item" data-celerik-key="0">'
            . '<fieldset class="card-body"><legend class="celerik-repeater-label">Line 1</legend>'
            . '<div class="celerik-repeater-actions"><button type="button" class="btn btn-sm btn-outline-danger" data-celerik-repeater-remove disabled>Remove</button></div>'
            . '<div class="row"><div class="col-12 col-md-6"><div class="mb-3"><label for="f-lines-0-product"',
            $html,
        );
        self::assertStringContainsString('id="f-lines-0-product" name="lines[0][product]" required', $html);
        self::assertStringContainsString('id="f-lines-0-qty" name="lines[0][qty]"', $html);
        self::assertStringContainsString('<button type="button" class="btn btn-sm btn-outline-primary" data-celerik-repeater-add>Add a line</button><template>', $html);
        self::assertStringContainsString('id="f-lines-__index__-product" name="lines[__index__][product]"', $html);
    }

    public function testRowsComeFromOldInputThenValueAndKeepTheirKeys(): void
    {
        $repeater = self::lines()->value([['product' => 'Tea', 'qty' => 2]]);
        $html = Form::make('f')->submit(null)->add($repeater)->render();
        self::assertStringContainsString('name="lines[0][product]" required type="text" value="Tea"', $html);

        // After a failed submit: old input wins, its keys are kept, and errors find their row.
        $html = Form::make('f')->submit(null)->errorSummary()->add($repeater)
            ->values(['lines' => [3 => ['product' => 'Coffee', 'qty' => 0], 7 => ['product' => 'Milk', 'qty' => 1]]])
            ->errors(['lines.3.qty' => 'The quantity must be at least 1.'])
            ->render();

        self::assertStringNotContainsString('Tea', $html);
        self::assertStringContainsString('<li class="card celerik-repeater-item" data-celerik-key="3"><fieldset class="card-body"><legend class="celerik-repeater-label">Line 1</legend>', $html);
        self::assertStringContainsString('<legend class="celerik-repeater-label">Line 2</legend>', $html);
        self::assertStringContainsString('name="lines[3][qty]" aria-describedby="f-lines-3-qty-error" aria-invalid="true" type="number" value="0"', $html);
        self::assertStringContainsString('name="lines[7][product]" required type="text" value="Milk"', $html);
        self::assertStringContainsString('<a href="#f-lines-3-qty" class="alert-link">The quantity must be at least 1.</a>', $html);
        // Max 3: Add is still enabled with two rows; Remove is enabled above min.
        self::assertStringContainsString('data-celerik-repeater-add>Add a line</button>', $html);
        self::assertStringContainsString('data-celerik-repeater-remove>Remove</button>', $html);
    }

    public function testShowsAnErrorOnTheRepeaterItselfAndDisablesAddAtMax(): void
    {
        $html = Form::make('f')->submit(null)->errorSummary()->add(self::lines())
            ->values(['lines' => [['product' => 'A'], ['product' => 'B'], ['product' => 'C']]])
            ->errors(['lines' => 'Too many lines.'])
            ->render();

        self::assertStringContainsString('<fieldset aria-describedby="f-lines-error">', $html);
        self::assertStringContainsString('</fieldset><div id="f-lines-error" class="invalid-feedback d-block celerik-server-error">Too many lines.</div>', $html);
        self::assertStringContainsString('<a href="#f-lines" class="alert-link">Too many lines.</a>', $html);
        self::assertStringContainsString('data-celerik-repeater-add disabled>Add a line</button>', $html);
    }

    public function testReorderableRowsHaveMoveButtons(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Repeater::make('contacts')->schema(Field::email('email'))->reorderable()->defaultItems(2),
        )->render();

        self::assertStringContainsString('data-celerik-repeater-move="up" disabled><svg aria-hidden="true"', $html);
        self::assertStringContainsString('<span class="visually-hidden">Move up</span></button>', $html);
        self::assertStringContainsString('<legend class="celerik-repeater-label">Item 2</legend>', $html);
        self::assertSame(1, substr_count($html, 'data-celerik-repeater-move="down" disabled'), 'the last row');
    }

    public function testRepeatersInSectionsAndWizards(): void
    {
        $form = Form::make('f')->wizard()->add(
            Section::make('Customer')->add(Field::text('name')),
            Section::make('Lines')->add(self::lines()),
        )->values(['lines' => [['product' => '']]])->errors(['lines.0.product' => 'Required.']);
        $html = $form->render();

        // The wizard opens at the step whose repeater row has the error.
        self::assertStringContainsString('<div class="celerik-wizard-step" data-celerik-step="1">', $html);
        self::assertSame(['name', 'lines[0][product]', 'lines[0][qty]'], array_map(
            static fn (Field $field): string => $field->getName(),
            $form->getFields(),
        ));
    }

    public function testIgnoresOldInputKeysThatAreNotNames(): void
    {
        $html = Form::make('f')->submit(null)->add(self::lines())
            ->values(['lines' => ['"><x' => ['product' => 'bad'], 'a1' => ['product' => 'ok'], 2 => 'not a row']])
            ->render();

        self::assertStringNotContainsString('bad', $html);
        self::assertStringContainsString('name="lines[a1][product]" required type="text" value="ok"', $html);
        self::assertStringContainsString('name="lines[2][product]" required', $html);
    }

    public function testRejectsUnsupportedSchemas(): void
    {
        $attempts = [
            static fn () => Repeater::make('files')->schema(Field::file('photo')),
            static fn () => Repeater::make('notes')->schema(Field::editor('body')),
            static fn () => Repeater::make('ids')->schema(Field::hidden('id', 1)),
            static fn () => Repeater::make('a b'),
            static fn () => Repeater::make('a')->max(2)->min(3),
            static fn () => Repeater::make('a')->min(2)->max(1),
            static fn () => Form::make('f')->add(Repeater::make('empty'))->render(),
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
