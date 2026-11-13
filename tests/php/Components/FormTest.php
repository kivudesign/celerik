<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Data\ArrayDataSource;
use Celerik\Ui\Upload\UploadRules;
use InvalidArgumentException;
use LogicException;
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

    public function testRadioGroupIsAFieldsetWithALegendAndOneRequiredRadioPerChoice(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Field::radio('plan', ['free' => 'Free', 'team' => 'Team <b>'])->required()->help('Billed monthly.'),
        )->values(['plan' => 'team'])->render();

        self::assertStringContainsString('<fieldset id="f-plan-group" aria-describedby="f-plan-help"><legend class="form-label fs-6">Plan <span class="celerik-required" aria-hidden="true">*</span></legend>', $html);
        self::assertStringContainsString('<div class="form-check"><input type="radio" id="f-plan" name="plan" value="free" required class="form-check-input"><label for="f-plan" class="form-check-label">Free</label></div>', $html);
        self::assertStringContainsString('<input type="radio" id="f-plan-1" name="plan" value="team" checked required class="form-check-input"><label for="f-plan-1" class="form-check-label">Team &lt;b&gt;</label>', $html);
        self::assertStringContainsString('</fieldset><div id="f-plan-help" class="form-text">Billed monthly.</div>', $html);
    }

    public function testCheckboxGroupSubmitsAnArrayAndRequiresOneBoxThroughParsley(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Field::checkboxes('topics', ['news' => 'News', 'security' => 'Security', 3 => 'Other'])->required()->inline(),
        )->values(['topics' => ['security', 3]])->render();

        self::assertStringContainsString('<div class="form-check form-check-inline"><input type="checkbox" id="f-topics" name="topics[]" value="news" data-parsley-required="true" class="form-check-input">', $html);
        self::assertStringContainsString('id="f-topics-1" name="topics[]" value="security" checked class="form-check-input"', $html);
        self::assertStringContainsString('id="f-topics-2" name="topics[]" value="3" checked class="form-check-input"', $html);
        // Native "required" on every box would require every box.
        self::assertStringNotContainsString(' required ', $html);
    }

    public function testChoiceGroupShowsTheServerErrorAndMarksEveryInputInvalid(): void
    {
        $html = Form::make('f')->submit(null)->errorSummary()->add(
            Field::radio('plan', ['free' => 'Free', 'team' => 'Team']),
        )->errors(['plan' => 'Choose a plan.'])->render();

        self::assertStringContainsString('aria-describedby="f-plan-error"', $html);
        self::assertSame(2, substr_count($html, 'aria-invalid="true" class="form-check-input is-invalid"'));
        self::assertStringContainsString('</fieldset><div id="f-plan-error" class="invalid-feedback d-block celerik-server-error">Choose a plan.</div>', $html);
        // The summary links to the first radio button.
        self::assertStringContainsString('<a href="#f-plan" class="alert-link">Choose a plan.</a>', $html);
    }

    public function testSwitchIsACheckboxWithTheSwitchRole(): void
    {
        $html = Form::make('f')->submit(null)->add(Field::switch('newsletter')->value('1'))->render();

        self::assertStringContainsString('<div class="mb-3 form-check form-switch"><input id="f-newsletter" name="newsletter" type="checkbox" role="switch" value="1" checked class="form-check-input"><label for="f-newsletter" class="form-check-label">Newsletter</label></div>', $html);
    }

    public function testRangeShowsItsValueAfterTheLabel(): void
    {
        $html = Form::make('f')->submit(null)->floatingLabels()->add(
            Field::range('volume')->min(0)->max(10)->step(2)->value(4),
            Field::range('zoom')->min(1)->max(4),
        )->render();

        self::assertStringContainsString('<div class="mb-3"><label for="f-volume" class="form-label">Volume: <output for="f-volume" class="celerik-range-value" aria-hidden="true">4</output></label><input min="0" max="10" step="2" id="f-volume" name="volume" type="range" value="4" class="form-range"></div>', $html);
        // Without a value, browsers start halfway.
        self::assertStringContainsString('<output for="f-zoom" class="celerik-range-value" aria-hidden="true">2.5</output>', $html);
    }

    public function testColorUsesBootstrapsColorControl(): void
    {
        $html = Form::make('f')->submit(null)->add(Field::color('accent')->value('#0d6efd'))->render();

        self::assertStringContainsString('<input id="f-accent" name="accent" type="color" value="#0d6efd" class="form-control form-control-color">', $html);
    }

    public function testSearchableAndMultipleSelects(): void
    {
        $html = Form::make('f')->submit(null)->floatingLabels()->add(
            Field::select('country', ['fr' => 'France', 'ca' => 'Canada'])->searchable()->placeholder('Choose'),
            Field::select('languages', ['en' => 'English', 'fr' => 'French'])->multiple()->placeholder('None'),
        )->values(['languages' => ['fr']])->render();

        // Searchable: Tom Select options, the placeholder in the options (not on the select), no floating label.
        self::assertStringContainsString('<div class="mb-3"><label for="f-country" class="form-label">Country</label><select id="f-country" name="country" data-celerik-select="{&quot;placeholder&quot;:&quot;Choose&quot;}" class="form-select"><option value="">Choose</option>', $html);
        // Multiple: name[], array value, no empty option.
        self::assertStringContainsString('<select id="f-languages" name="languages[]" placeholder="None" multiple class="form-select"><option value="en">English</option><option value="fr" selected>French</option></select>', $html);
    }

    public function testRemoteSelectPassesItsEndpointAndKeepsTheInitialChoices(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Field::select('author', [7 => 'Grace Hopper'])->remote('/people?team=2')->value(7),
        )->render();

        self::assertStringContainsString('data-celerik-select="{&quot;url&quot;:&quot;/people?team=2&quot;}"', $html);
        self::assertStringContainsString('<option value="7" selected>Grace Hopper</option>', $html);
    }

    public function testRemoteSelectRespondsWithTheFirstMatchesSortedByLabelAndOnlyValueAndLabel(): void
    {
        $people = new ArrayDataSource([
            ['id' => 1, 'name' => 'Grace Hopper', 'email' => 'grace@example.com'],
            ['id' => 2, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['id' => 3, 'name' => 'Alan Turing', 'email' => 'alan@example.com'],
            ['id' => 4, 'name' => 'Barbara Liskov', 'email' => 'barbara@example.com'],
        ]);
        $field = Field::select('author', [])->remote('/people', limit: 2);

        // The client cannot choose the page size, the sort or the searched fields.
        $result = $field->respond($people, [
            'search' => 'a', 'per_page' => 1000, 'sort' => '-email', 'filter' => ['email' => 'grace@example.com'],
        ])->toArray();

        self::assertSame([['value' => '2', 'label' => 'Ada Lovelace'], ['value' => '3', 'label' => 'Alan Turing']], $result['data']);
        self::assertSame(4, $result['meta']['filtered']);
        self::assertSame(2, $result['meta']['perPage']);

        $byEmail = $field->respond($people, ['search' => 'example.com'])->toArray();
        self::assertSame([], $byEmail['data']);
    }

    public function testRemoteSelectOptionsAreValidated(): void
    {
        $cases = [
            static fn () => Field::text('name')->remote('/people'),
            static fn () => Field::select('a', [])->remote('/people', value: 'id; drop'),
            static fn () => Field::select('a', [])->remote('/people', limit: 101),
            static fn () => Field::text('name')->multiple(),
            static fn () => Field::select('a', [])->respond(new ArrayDataSource([]), []),
        ];
        foreach ($cases as $case) {
            try {
                $case();
                self::fail('An invalid remote select was accepted.');
            } catch (InvalidArgumentException | LogicException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testTimeAndDateTimeUseNativeInputs(): void
    {
        $html = Form::make('f')->submit(null)->add(
            Field::time('opens')->min('08:00')->step(900)->value('09:30'),
            Field::datetime('reminder'),
        )->render();

        self::assertStringContainsString('<input min="08:00" step="900" id="f-opens" name="opens" type="time" value="09:30" class="form-control">', $html);
        self::assertStringContainsString('<input id="f-reminder" name="reminder" type="datetime-local" class="form-control">', $html);
    }

    public function testDateRangeIsAFieldsetOfTwoLinkedInputs(): void
    {
        $html = Form::make('f')->submit(null)->floatingLabels()->add(
            Field::dateRange('stay')->labels('Arrival', 'Departure')->min('2026-01-01')->required()->help('Up to 30 nights.'),
        )->values(['stay' => ['start' => '2026-03-01', 'end' => '2026-03-04']])->render();

        self::assertStringContainsString('<fieldset id="f-stay-group" aria-describedby="f-stay-help"><legend class="form-label fs-6">Stay <span class="celerik-required" aria-hidden="true">*</span></legend><div class="row g-2">', $html);
        self::assertStringContainsString('<div class="col-sm-6"><label for="f-stay" class="form-label">Arrival</label><input min="2026-01-01" type="date" id="f-stay" name="stay[start]" value="2026-03-01" required class="form-control"></div>', $html);
        self::assertStringContainsString('<label for="f-stay-end" class="form-label">Departure</label><input min="2026-01-01" type="date" id="f-stay-end" name="stay[end]" value="2026-03-04" required data-parsley-after="#f-stay" class="form-control">', $html);
    }

    public function testDateRangeShowsAnErrorReportedOnEitherInput(): void
    {
        $html = Form::make('f')->submit(null)->errorSummary()->add(
            Field::dateRange('shift', 'Shift', 'time'),
        )->errors(['shift.end' => 'The end must be after the start.'])->render();

        self::assertStringContainsString('type="time" id="f-shift" name="shift[start]" aria-invalid="true" class="form-control is-invalid"', $html);
        self::assertStringContainsString('</fieldset><div id="f-shift-error" class="invalid-feedback d-block celerik-server-error">The end must be after the start.</div>', $html);
        self::assertStringContainsString('<a href="#f-shift" class="alert-link">The end must be after the start.</a>', $html);
    }

    public function testDateRangeRejectsOtherTypesAndLabelsOnlyApplyToIt(): void
    {
        try {
            Field::dateRange('stay', null, 'month');
            self::fail('A month range was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        Field::date('day')->labels('From', 'To');
    }

    public function testChoiceGroupsNeedChoicesAndInlineOnlyAppliesToThem(): void
    {
        try {
            Field::radio('plan', []);
            self::fail('An empty radio group was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        Field::select('plan', ['a' => 'A'])->inline();
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

    public function testFloatingLabelsPutTheControlFirstWithAPlaceholder(): void
    {
        $html = Form::make('f')->floatingLabels()->submit(null)->add(
            Field::email('email')->required()->help('Never shared.'),
            Field::select('country', ['fr' => 'France'])->placeholder('Choose...'),
            Field::checkbox('terms', 'I agree'),
            Field::file('cv'),
        )->render();

        self::assertStringContainsString(
            '<div class="form-floating mb-3"><input id="f-email" name="email" required placeholder="Email" '
            . 'aria-describedby="f-email-help" type="email" class="form-control">'
            . '<label for="f-email">Email <span class="celerik-required" aria-hidden="true">*</span></label>'
            . '<div id="f-email-help" class="form-text">Never shared.</div></div>',
            $html,
        );
        self::assertStringContainsString('<div class="form-floating mb-3"><select id="f-country"', $html);
        self::assertStringContainsString('<div class="mb-3 form-check"><input id="f-terms"', $html, 'Checkboxes keep their layout.');
        self::assertStringContainsString('<div class="mb-3"><label for="f-cv" id="f-cv-label" class="form-label">', $html, 'File fields keep their layout.');
    }

    public function testErrorSummaryListsServerErrorsWithLinksToTheFields(): void
    {
        $form = Form::make('signup')
            ->errorSummary(title: 'Fix <these> problems')
            ->add(Field::text('name'), Field::email('email'), Field::text('address[city]', 'City'))
            ->errors(['email' => 'This e-mail is taken.', 'address.city' => ['Enter a <city>.']]);
        $html = $form->render();

        self::assertStringContainsString(
            '<div id="signup-error-summary" class="alert alert-danger celerik-error-summary" tabindex="-1" '
            . 'aria-labelledby="signup-error-summary-title"><h2 id="signup-error-summary-title" class="h6">'
            . 'Fix &lt;these&gt; problems</h2><ul class="mb-0"><li><a href="#signup-email" class="alert-link">This e-mail is taken.</a></li>'
            . '<li><a href="#signup-address-city" class="alert-link">Enter a &lt;city&gt;.</a></li></ul></div>',
            $html,
        );
        self::assertLessThan(strpos($html, 'id="signup-name"'), strpos($html, 'celerik-error-summary'), 'The summary comes first.');
        self::assertTrue($form->toArray()['errorSummary']);
    }

    public function testErrorSummaryIsHiddenWithoutErrorsAndOffByDefault(): void
    {
        $html = Form::make('f')->errorSummary()->add(Field::text('name'))->render();
        self::assertStringContainsString('class="alert alert-danger celerik-error-summary" tabindex="-1" aria-labelledby="f-error-summary-title" hidden>', $html);
        self::assertStringContainsString('There is a problem', $html);

        $plain = Form::make('f')->add(Field::text('name'))->errors(['name' => 'Required.']);
        self::assertStringNotContainsString('celerik-error-summary', $plain->render());
        self::assertArrayNotHasKey('errorSummary', $plain->toArray());
        self::assertArrayNotHasKey('errorSummary', Form::make('f')->errorSummary()->errorSummary(false)->toArray());
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
