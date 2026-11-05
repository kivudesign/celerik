<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Support\EditorHtml;
use Celerik\Ui\Upload\StoredFile;
use Celerik\Ui\Upload\UploadedFile;
use Celerik\Ui\Upload\UploadException;
use Celerik\Ui\Upload\UploadHandler;
use Celerik\Ui\Upload\UploadRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile as LaravelUploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Rich text editor and asynchronous uploads. FilePond sends the X-CSRF-TOKEN header,
 * which Laravel's CSRF middleware accepts. Files are kept in storage/app/private,
 * outside the public directory: serve them through a controller that checks access.
 */
final class CelerikArticleController extends Controller
{
    public function show(Request $request): View
    {
        return view('celerik.article', ['form' => $this->form($request), 'article' => session('article')]);
    }

    /**
     * FilePond "process" (POST) and "revert" (DELETE) for one field.
     */
    public function upload(Request $request, string $field): Response
    {
        $handler = $this->handler($field);

        if ($request->isMethod('DELETE')) {
            $handler->revert(trim($request->getContent()));

            return response('', 200);
        }

        $file = $request->file($field);
        $file = is_array($file) ? ($file[0] ?? null) : $file;
        if (!$file instanceof LaravelUploadedFile) {
            return response('Send exactly one file.', 422);
        }

        try {
            $id = $handler->process(self::toUploadedFile($file));
        } catch (UploadException $e) {
            return response($e->getMessage(), 422);
        }

        return response($id, 200, ['Content-Type' => 'text/plain']);
    }

    public function submit(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string']]);
        $body = EditorHtml::sanitize($data['body']);

        try {
            $cover = $this->receive($request, 'cover');
            $attachments = $this->receive($request, 'attachments');
            $attachments = array_slice($attachments, 0, $this->rules('attachments')->maxFiles);
        } catch (UploadException $e) {
            throw ValidationException::withMessages(['cover' => $e->getMessage()]);
        }

        if ($cover === []) {
            throw ValidationException::withMessages(['cover' => 'Please add a cover image.']);
        }

        // Store $data['title'], $body, $cover[0]->name and the attachment names in your database here.
        return redirect()->route('celerik.article')->with('article', [
            'title' => $data['title'],
            'body' => $body,
            'cover' => $cover[0]->originalName,
            'attachments' => array_map(static fn (StoredFile $file): string => $file->originalName, $attachments),
        ]);
    }

    /**
     * FilePond submits upload ids; without JavaScript the browser submits the files.
     *
     * @return list<StoredFile>
     */
    private function receive(Request $request, string $field): array
    {
        $handler = $this->handler($field);
        $directory = storage_path('app/private/articles/' . $field);

        if ($request->hasFile($field)) {
            $files = $request->file($field);

            $store = static fn (LaravelUploadedFile $file): StoredFile
                => $handler->store(self::toUploadedFile($file), $directory);

            return array_map($store, is_array($files) ? $files : [$files]);
        }

        $ids = array_filter((array) $request->input($field, []), 'is_string');

        return array_values(array_map(static fn (string $id): StoredFile => $handler->claim($id, $directory), $ids));
    }

    private function form(Request $request): Form
    {
        return Form::make('article')
            ->action(route('celerik.article.submit'))
            ->csrf(csrf_token())
            ->add(
                Field::text('title')->required()->maxLength(120),
                Field::editor('body', 'Article')->required()->placeholder('Write your article…'),
                Field::file('cover', 'Cover image')
                    ->rules($this->rules('cover'))
                    ->upload(route('celerik.article.upload', 'cover'))
                    ->required(),
                Field::file('attachments')
                    ->rules($this->rules('attachments'))
                    ->upload(route('celerik.article.upload', 'attachments')),
            )
            ->errors($request->session()->get('errors')?->getBag('default')->toArray() ?? [])
            ->values($request->old())
            ->submit('Publish');
    }

    private static function toUploadedFile(LaravelUploadedFile $file): UploadedFile
    {
        return new UploadedFile($file->getPathname(), $file->getClientOriginalName(), $file->getError());
    }

    private function handler(string $field): UploadHandler
    {
        return new UploadHandler($this->rules($field), storage_path('app/private/uploads-tmp'));
    }

    private function rules(string $field): UploadRules
    {
        return match ($field) {
            'cover' => UploadRules::images()->maxSize('2M'),
            'attachments' => UploadRules::documents()->maxSize('5M')->maxFiles(3),
            default => abort(404),
        };
    }
}
