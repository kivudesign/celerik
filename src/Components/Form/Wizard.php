<?php

declare(strict_types=1);

namespace Celerik\Ui\Components\Form;

use Celerik\Ui\Support\Html;
use Closure;
use LogicException;

/**
 * Renders a form's sections as the steps of a wizard (Form::wizard()): a step indicator,
 * one step at a time, and Back, Next and submit buttons. form.js checks each step's fields
 * before the next one; the whole form is submitted at the end.
 *
 * @internal
 */
final class Wizard
{
    public function __construct(
        private readonly string $next,
        private readonly string $previous,
        private readonly string $progress,
        private readonly string $stepOf,
        private readonly string $completed,
    ) {
    }

    /**
     * @param list<Field|Section|Repeater>    $items
     * @param Closure(Field|Repeater): string $renderField
     * @param Closure(Field|Repeater): bool   $hasError
     */
    public function render(
        string $formId,
        array $items,
        Closure $renderField,
        Closure $hasError,
        ?string $submitLabel,
    ): string {
        $steps = [];
        foreach ($items as $item) {
            if ($item instanceof Section && !$item->isFieldset() && !$item->isCollapsible()) {
                $steps[] = $item;
            } elseif (!$item instanceof Field || $item->getType() !== 'hidden') {
                throw new LogicException(
                    'The steps of a wizard are sections (Section::make(), not collapsible); '
                    . 'other fields go inside them, except hidden ones.',
                );
            }
        }

        if ($steps === []) {
            throw new LogicException('A wizard needs at least one step: add Section::make() items.');
        }

        // After a failed submit, start at the first step with a server error.
        $current = 0;
        foreach ($steps as $index => $step) {
            if ($step->hasErrors($hasError)) {
                $current = $index;
                break;
            }
        }

        $count = count($steps);
        $content = $this->renderIndicator($steps, $current);
        $index = 0;
        foreach ($items as $item) {
            if ($item instanceof Field) {
                $content .= $renderField($item);
                continue;
            }

            $prefix = sprintf($this->stepOf, $index + 1, $count);
            $content .= Html::tag('div', [
                'class' => 'celerik-wizard-step',
                'data-celerik-step' => (string) $index,
                'hidden' => $index !== $current,
            ], $item->render($formId, $renderField, $hasError, 2, $prefix));
            $index++;
        }

        $last = $count - 1;
        $buttons = Html::tag('button', [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary',
            'data-celerik-wizard' => 'previous',
            'hidden' => $current === 0,
        ], Html::escape($this->previous));
        $buttons .= Html::tag('button', [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'data-celerik-wizard' => 'next',
            'hidden' => $current === $last,
        ], Html::escape($this->next));
        if ($submitLabel !== null) {
            $buttons .= Html::tag('button', [
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'hidden' => $current !== $last,
            ], Html::escape($submitLabel));
        }

        return $content . Html::tag('div', ['class' => 'celerik-wizard-nav'], $buttons);
    }

    /**
     * @param list<Section> $steps
     */
    private function renderIndicator(array $steps, int $current): string
    {
        $items = '';
        foreach ($steps as $index => $step) {
            $done = Html::tag(
                'span',
                ['class' => 'visually-hidden celerik-wizard-done', 'hidden' => $index >= $current],
                ' (' . Html::escape($this->completed) . ')',
            );
            $items .= Html::tag('li', [
                'class' => $index < $current ? 'is-complete' : null,
                'aria-current' => $index === $current ? 'step' : null,
            ], Html::tag('span', ['class' => 'celerik-wizard-number', 'aria-hidden' => 'true'], (string) ($index + 1))
                . Html::tag('span', ['class' => 'celerik-wizard-label'], Html::escape($step->getHeading()) . $done));
        }

        return Html::tag('ol', ['class' => 'celerik-wizard-steps', 'aria-label' => $this->progress], $items);
    }
}
