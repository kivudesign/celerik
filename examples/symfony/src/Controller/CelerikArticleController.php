<?php

declare(strict_types=1);

namespace App\Controller;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Support\EditorHtml;
use Celerik\Ui\Upload\StoredFile;
use Celerik\Ui\Upload\UploadedFile;
use Celerik\Ui\Upload\UploadException;
use Celerik\Ui\Upload\UploadHandler;
use Celerik\Ui\Upload\UploadRules;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Rich text editor and asynchronous uploads. FilePond sends the token in the
 * X-CSRF-TOKEN header, checked in upload(). Files are kept in var/uploads, outside
 * public/: serve them through a controller that checks access.
 */
#[Route('/celerik/article', name: 'celerik_article')]
final class CelerikArticleController extends AbstractController
{
    private const CSRF_ID = 'celerik_article';

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads')]
        private readonly string $storage,
    ) {
    }

    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function article(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $errors = [];
        $values = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $values = $request->request->all();
            $title = trim((string) $request->request->get('title'));
            $body = EditorHtml::sanitize((string) $request->request->get('body'));

            if ($title === '') {
                $errors['title'] = 'Please enter a title.';
            }
            if (trim(strip_tags($body)) === '') {
                $errors['body'] = 'Please write the article.';
            }

            try {
                $cover = $this->receive($request, 'cover');
                $attachments = $this->receive($request, 'attachments');
                $attachments = array_slice($attachments, 0, $this->rules('attachments')->maxFiles);
                if ($cover === []) {
                    $errors['cover'] = 'Please add a cover image.';
                }
            } catch (UploadException $e) {
                $errors['cover'] = $e->getMessage();
            }

            if ($errors === []) {
                // Store $title, $body, $cover[0]->name and the attachment names in your database here.
                $this->addFlash('success', sprintf('Published "%s" with cover %s.', $title, $cover[0]->originalName));
                $request->getSession()->set('celerik_article_body', $body);

                return $this->redirectToRoute('celerik_article');
            }
        }

        $form = Form::make('article')
            ->action($this->generateUrl('celerik_article'))
            ->csrf($csrf->getToken(self::CSRF_ID)->getValue())
            ->add(
                Field::text('title')->required()->maxLength(120),
                Field::editor('body', 'Article')->required()->placeholder('Write your article…'),
                Field::file('cover', 'Cover image')
                    ->rules($this->rules('cover'))
                    ->upload($this->generateUrl('celerik_article_upload', ['field' => 'cover']))
                    ->required(),
                Field::file('attachments')
                    ->rules($this->rules('attachments'))
                    ->upload($this->generateUrl('celerik_article_upload', ['field' => 'attachments'])),
            )
            ->errors($errors)
            ->values($values)
            ->submit('Publish');

        return $this->render('celerik/article.html.twig', [
            'form' => $form,
            // Sanitized by EditorHtml::sanitize() before it was stored.
            'body' => $request->getSession()->remove('celerik_article_body'),
        ], new Response(status: $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * FilePond "process" (POST) and "revert" (DELETE) for one field.
     */
    #[Route(
        '/upload/{field}',
        name: '_upload',
        requirements: ['field' => 'cover|attachments'],
        methods: ['POST', 'DELETE'],
    )]
    public function upload(Request $request, string $field): Response
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $handler = $this->handler($field);

        if ($request->isMethod('DELETE')) {
            $handler->revert(trim($request->getContent()));

            return new Response();
        }

        $file = $request->files->get($field);
        $file = is_array($file) ? ($file[0] ?? null) : $file;
        if (!$file instanceof SymfonyUploadedFile) {
            return new Response('Send exactly one file.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return new Response($handler->process(self::toUploadedFile($file)), 200, ['Content-Type' => 'text/plain']);
        } catch (UploadException $e) {
            return new Response($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * FilePond submits upload ids; without JavaScript the browser submits the files.
     *
     * @return list<StoredFile>
     */
    private function receive(Request $request, string $field): array
    {
        $handler = $this->handler($field);
        $directory = $this->storage . '/articles/' . $field;

        $files = $request->files->get($field);
        if ($files !== null && $files !== []) {
            $store = static fn (SymfonyUploadedFile $file): StoredFile
                => $handler->store(self::toUploadedFile($file), $directory);

            return array_map($store, array_filter(is_array($files) ? $files : [$files]));
        }

        $ids = $request->request->all()[$field] ?? [];
        $ids = array_filter(is_array($ids) ? $ids : [$ids], 'is_string');

        return array_values(array_map(static fn (string $id): StoredFile => $handler->claim($id, $directory), $ids));
    }

    private static function toUploadedFile(SymfonyUploadedFile $file): UploadedFile
    {
        return new UploadedFile($file->getPathname(), $file->getClientOriginalName(), $file->getError());
    }

    private function handler(string $field): UploadHandler
    {
        return new UploadHandler($this->rules($field), $this->storage . '/tmp');
    }

    private function rules(string $field): UploadRules
    {
        return $field === 'cover'
            ? UploadRules::images()->maxSize('2M')
            : UploadRules::documents()->maxSize('5M')->maxFiles(3);
    }
}
