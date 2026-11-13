<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Form\Section;
use LogicException;
use PHPUnit\Framework\TestCase;

final class FormWizardTest extends TestCase
{
    private static function wizard(): Form
    {
        return Form::make('f')->wizard()->submit('Create')->add(
            Field::hidden('plan', 'team'),
            Section::make('Account')->add(Field::email('email')->required()),
            Section::make('Address')->columns(2)->add(Field::text('city'), Field::text('zip')),
            Section::make('Confirm')->add(Field::checkbox('terms', 'I accept the terms')->required()),
        );
    }

    public function testRendersTheFirstStepWithAnIndicatorAndButtons(): void
    {
        $html = self::wizard()->render();

        self::assertStringContainsString('class="celerik-form celerik-wizard"', $html);
        self::assertStringContainsString(
            '<ol class="celerik-wizard-steps" aria-label="Progress">'
            . '<li aria-current="step"><span class="celerik-wizard-number" aria-hidden="true">1</span>'
            . '<span class="celerik-wizard-label">Account<span class="visually-hidden celerik-wizard-done" hidden> (completed)</span></span></li>'
            . '<li><span class="celerik-wizard-number" aria-hidden="true">2</span>',
            $html,
        );
        // Hidden fields stay outside the steps.
        self::assertStringContainsString('</ol><input type="hidden" id="f-plan" name="plan" value="team"><div class="celerik-wizard-step" data-celerik-step="0">', $html);
        self::assertStringContainsString(
            '<h2 id="f-section-Account-heading" class="card-title h5 mb-3" tabindex="-1">'
            . '<span class="visually-hidden">Step 1 of 3: </span>Account</h2>',
            $html,
        );
        self::assertStringContainsString('<div class="celerik-wizard-step" data-celerik-step="1" hidden>', $html);
        self::assertStringContainsString('<div class="celerik-wizard-step" data-celerik-step="2" hidden>', $html);
        self::assertStringContainsString(
            '<div class="celerik-wizard-nav">'
            . '<button type="button" class="btn btn-outline-secondary" data-celerik-wizard="previous" hidden>Back</button>'
            . '<button type="button" class="btn btn-primary" data-celerik-wizard="next">Next</button>'
            . '<button type="submit" class="btn btn-primary" hidden>Create</button></div></form>',
            $html,
        );
        self::assertSame(1, substr_count($html, 'type="submit"'));
    }

    public function testStartsAtTheFirstStepWithAServerError(): void
    {
        $html = self::wizard()->errors(['terms' => 'Accept the terms.', 'zip' => 'Invalid ZIP code.'])->render();

        self::assertStringContainsString('<div class="celerik-wizard-step" data-celerik-step="0" hidden>', $html);
        self::assertStringContainsString('<div class="celerik-wizard-step" data-celerik-step="1">', $html);
        self::assertStringContainsString(
            '<li class="is-complete"><span class="celerik-wizard-number" aria-hidden="true">1</span>'
            . '<span class="celerik-wizard-label">Account<span class="visually-hidden celerik-wizard-done"> (completed)</span>',
            $html,
        );
        self::assertStringContainsString('<li aria-current="step"><span class="celerik-wizard-number" aria-hidden="true">2</span>', $html);
        self::assertStringContainsString('data-celerik-wizard="previous">Back</button>', $html);
    }

    public function testTheLastStepShowsTheSubmitButton(): void
    {
        $html = self::wizard()->errors(['terms' => 'Accept the terms.'])->render();

        self::assertStringContainsString('data-celerik-wizard="next" hidden>Next</button><button type="submit" class="btn btn-primary">Create</button>', $html);
    }

    public function testLabelsCanBeTranslated(): void
    {
        $html = Form::make('f')
            ->wizard(next: 'Suivant', previous: 'Retour', progress: 'Progression', stepOf: 'Étape %d sur %d : ', completed: 'terminée')
            ->add(Section::make('Compte')->add(Field::text('name')), Section::make('Adresse')->add(Field::text('city')))
            ->render();

        self::assertStringContainsString('aria-label="Progression"', $html);
        self::assertStringContainsString('<span class="visually-hidden">Étape 2 sur 2 : </span>Adresse', $html);
        self::assertStringContainsString('> (terminée)</span>', $html);
        self::assertStringContainsString('>Retour</button>', $html);
        self::assertStringContainsString('>Suivant</button>', $html);
    }

    public function testStepsAreSections(): void
    {
        $forms = [
            Form::make('f')->wizard()->add(Field::text('name')),
            Form::make('f')->wizard()->add(Section::fieldset('Address')->add(Field::text('city'))),
            Form::make('f')->wizard()->add(Section::make('Notes')->collapsible()->add(Field::text('note'))),
            Form::make('f')->wizard()->add(Field::hidden('id', 1)),
        ];

        foreach ($forms as $index => $form) {
            try {
                $form->render();
                self::fail(sprintf('Form %d was rendered.', $index));
            } catch (LogicException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
