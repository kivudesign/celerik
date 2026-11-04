<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    public function testRendersFormWithCsrfAndMethodSpoofing(): void
    {
        $html = Form::make('profile')->action('/profile')->method('put')->csrf('tok"en')->render();

        self::assertStringContainsString('<input type="hidden" name="_token" value="tok&quot;en">', $html);
        self::assertStringContainsString('<input type="hidden" name="_method" value="PUT">', $html);
        self::assertStringContainsString('action="/profile" method="POST" novalidate', $html);
        self::assertStringContainsString('data-celerik="form"', $html);
    }

    public function testGetFormsDoNotLeakCsrfToken(): void
    {
        $html = Form::make()->method('GET')->csrf('secret')->render();

        self::assertStringNotContainsString('secret', $html);
    }

    public function testRendersValidationRulesAsHtml5Attributes(): void
    {
        $html = Form::make('signup')->add(
            Field::email('email')->required()->maxLength(120)->help('We never share it.'),
            Field::password('password')->required()->minLength(8),
            Field::password('password_confirmation', 'Confirm password')->equalTo('password'),
            Field::tel('phone')->mask('(999) 999-9999'),
        )->render();

        self::assertStringContainsString('<label for="signup-email" class="form-label">Email <span class="celerik-required" aria-hidden="true">*</span></label>', $html);
        self::assertStringContainsString('maxlength="120" id="signup-email" name="email" required aria-describedby="signup-email-help" type="email"', $html);
        self::assertStringContainsString('<div id="signup-email-help" class="form-text">We never share it.</div>', $html);
        self::assertStringContainsString('minlength="8"', $html);
        self::assertStringContainsString('data-parsley-equalto="#signup-password"', $html);
        self::assertStringContainsString('data-celerik-mask="(999) 999-9999"', $html);
    }

    public function testShowsServerErrorsAndOldInputButNeverOldPasswords(): void
    {
        $html = Form::make('f')
            ->add(Field::text('name'), Field::password('password'), Field::text('address[city]', 'City'))
            ->errors(['name' => ['Name is taken.', 'Second'], 'address.city' => 'City is required.'])
            ->values(['name' => '<Ada>', 'password' => 'hunter2', 'address' => ['city' => 'Paris']])
            ->render();

        self::assertStringContainsString('aria-describedby="f-name-error" aria-invalid="true" type="text" value="&lt;Ada&gt;" class="form-control is-invalid"', $html);
        self::assertStringContainsString('<div id="f-name-error" class="invalid-feedback celerik-server-error">Name is taken.</div>', $html);
        self::assertStringContainsString('id="f-address-city" name="address[city]"', $html);
        self::assertStringContainsString('value="Paris"', $html);
        self::assertStringContainsString('City is required.', $html);
        self::assertStringNotContainsString('hunter2', $html);
    }

    public function testRendersSelectTextareaAndCheckbox(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Field::select('country', ['fr' => 'France', 'ca' => 'Canada'])->placeholder('Choose...')->value('ca'),
            Field::textarea('bio')->value('</textarea><script>'),
            Field::checkbox('terms', 'I accept the terms')->required()->value(true),
        )->render();

        self::assertStringContainsString('<option value="">Choose...</option><option value="fr">France</option><option value="ca" selected>Canada</option>', $html);
        self::assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;</textarea>', $html);
        self::assertStringContainsString('<div class="mb-3 form-check"><input id="f-terms" name="terms" required type="checkbox" value="1" checked class="form-check-input">', $html);
        self::assertStringNotContainsString('<button', $html);
    }

    public function testRejectsInvalidMethodAndFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Form::make()->method('TRACE');
    }

    public function testRejectsInvalidFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::text('"><script>');
    }
}
