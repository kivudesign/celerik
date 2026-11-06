<?php

declare(strict_types=1);

namespace App\Controller;

use App\Twig\CelerikThemeExtension;
use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Components\ThemeBuilder;
use Celerik\Ui\Theme\Theme;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Theme builder: a development tool, only available in the dev environment. Saving writes
 * config/celerik-theme.json (keep it in git) and public/css/celerik-theme.css, which
 * base.html.twig loads through celerik_saved_theme(). In CI, rebuild the stylesheet with
 * vendor/bin/celerik-theme build config/celerik-theme.json public/css/celerik-theme.css.
 */
#[Route('/celerik/theme', name: 'celerik_theme')]
final class CelerikThemeController extends AbstractController
{
    private const CSRF_ID = 'celerik-theme';

    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        private readonly CelerikThemeExtension $themes,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function show(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $this->denyOutsideDev();
        $requested = (string) $request->query->get('theme');
        $base = in_array($requested, Theme::BASES, true) ? $requested : 'bootstrap';

        return $this->render('celerik/theme-builder.html.twig', [
            'builder' => ThemeBuilder::make('builder')
                ->theme($this->themes->saved() ?? Theme::preset($base))
                ->assetsUrl('/bundles/celerikui')
                ->saveUrl($this->generateUrl('celerik_theme_save'))
                ->csrf($csrf->getToken(self::CSRF_ID)->getValue())
                ->preview(...self::previewComponents()),
        ]);
    }

    #[Route('', name: '_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->denyOutsideDev();
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $token)) {
            return new JsonResponse(['message' => 'Invalid CSRF token.'], 419);
        }

        try {
            $theme = ThemeBuilder::fromRequest($request->request->all(), $request->getContent());
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }
        $this->themes->save($theme);

        return $request->getContentTypeFormat() === 'json'
            ? new JsonResponse(['data' => $theme->toArray(), 'meta' => []])
            : new RedirectResponse($this->generateUrl('celerik_theme'), 303);
    }

    private function denyOutsideDev(): void
    {
        if ($this->environment !== 'dev') {
            throw $this->createNotFoundException();
        }
    }

    /** @return list<Form|Grid|Tabs|Chart> */
    private static function previewComponents(): array
    {
        return [
            Form::make('preview-form')
                ->add(
                    Field::text('name')->required(),
                    Field::email('email')->required()->help('We never share your e-mail.'),
                    Field::checkbox('terms', 'I accept the terms'),
                )
                ->errors(['email' => 'This e-mail is already registered.'])
                ->submit('Create account'),
            Grid::make('preview-grid')
                ->caption('Users')
                ->column('name')
                ->column('email', 'E-mail')
                ->pageSize(2)
                ->rows([
                    ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
                    ['name' => 'Alan Turing', 'email' => 'alan@example.com'],
                    ['name' => 'Grace Hopper', 'email' => 'grace@example.com'],
                ]),
            Tabs::make('preview-tabs')
                ->label('Settings')
                ->tab('profile', 'Profile', 'Your name and e-mail.')
                ->tab('security', 'Security', 'Change your password.'),
            Chart::make('preview-chart')
                ->type('line')
                ->title('Revenue')
                ->labels(['Jan', 'Feb', 'Mar'])
                ->dataset('North', [12, 19, 15])
                ->dataset('South', [9, 11, 17])
                ->height(200),
        ];
    }
}
