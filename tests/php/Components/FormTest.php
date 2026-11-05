<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Upload\UploadRules;
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

    public function testPlainFileFieldMakesTheFormMultipartAndDescribesTheRules(): void
    {
        $html = Form::make('f')->add(
            Field::file('cv', 'CV')->rules(UploadRules::types('application/pdf')->maxSize('2M'))->required(),
        )->render();

        self::assertStringContainsString('enctype="multipart/form-data"', $html);
        self::assertStringContainsString(
            '<input id="f-cv" name="cv" required aria-describedby="f-cv-help" type="file" accept="application/pdf,.pdf" class="form-control">',
            $html,
        );
        self::assertStringContainsString('<div id="f-cv-help" class="form-text">PDF. Up to 2 MB.</div>', $html);
        self::assertStringNotContainsString('data-celerik-upload', $html);
    }

    public function testAsyncFileFieldPassesUploadOptionsAndAppendsBracketsForMultipleFiles(): void
    {
        $form = Form::make('f')->csrf('tok')->add(
            Field::file('photos')->rules(UploadRules::images()->maxFiles(3))->upload('/uploads')->required(),
        );
        $html = $form->render();

        self::assertStringContainsString('name="photos[]"', $html);
        self::assertStringContainsString(' multiple ', $html);
        self::assertStringContainsString('data-parsley-excluded="true"', $html);
        self::assertStringNotContainsString(' required ', $html, 'FilePond enforces required itself.');
        self::assertStringContainsString(
            'data-celerik-upload="{&quot;url&quot;:&quot;/uploads&quot;,&quot;required&quot;:true,&quot;maxFiles&quot;:3,&quot;maxSize&quot;:5242880,'
            . '&quot;types&quot;:[&quot;image/jpeg&quot;,&quot;image/png&quot;,&quot;image/gif&quot;,&quot;image/webp&quot;]}"',
            $html,
        );
        self::assertSame(['csrf' => ['field' => '_token']], $form->toArray());
    }

    public function testEditorRendersALabelledTextareaWithEscapedContent(): void
    {
        $html = Form::make('f')->add(
            Field::editor('body', 'Article')->toolbar('basic')->placeholder('Write…')->required()->value('<p>Hi</p><script>x</script>'),
        )->render();

        self::assertStringContainsString('<label for="f-body" id="f-body-label" class="form-label">Article', $html);
        self::assertStringContainsString('class="form-control celerik-editor-source" rows="8"', $html);
        self::assertStringContainsString('data-celerik-editor="{&quot;toolbar&quot;:&quot;basic&quot;,&quot;placeholder&quot;:&quot;Write…&quot;}"', $html);
        self::assertStringContainsString('&lt;p&gt;Hi&lt;/p&gt;&lt;script&gt;x&lt;/script&gt;</textarea>', $html);
        self::assertStringNotContainsString('enctype', $html);
    }

    public function testFieldSpecificOptionsAreRejectedOnOtherTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::text('name')->upload('/uploads');
    }

    public function testRejectsUnknownToolbar(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::editor('body')->toolbar('everything');
    }

    public function testRejectsInvalidFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Field::text('"><script>');
    }
}
