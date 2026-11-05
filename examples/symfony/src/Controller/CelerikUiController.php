<?php

declare(strict_types=1);

namespace App\Controller;

use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Modal;
use Celerik\Ui\Components\Notification;
use Celerik\Ui\Components\Tabs;
use Celerik\Ui\Support\Html;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Tabs, a modal with a form, and flash messages shown as notifications.
 */
#[Route('/celerik/ui', name: 'celerik_ui')]
final class CelerikUiController extends AbstractController
{
    private const CSRF_ID = 'celerik_newsletter';

    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function show(Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $email = (string) $request->request->get('email');
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Please enter a valid e-mail address.';
            } else {
                // Messages are shown as text: user data in them is safe.
                $this->addFlash('success', $email . ' is now subscribed.');

                return $this->redirectToRoute('celerik_ui');
            }
        }

        $form = Form::make('newsletter')
            ->action($this->generateUrl('celerik_ui'))
            ->csrf($csrf->getToken(self::CSRF_ID)->getValue())
            ->add(Field::email('email', 'E-mail address')->required()->help('One e-mail per month, no spam.'))
            ->errors($errors)
            ->values($errors === [] ? [] : $request->request->all())
            ->submit(null);

        return $this->render('celerik/ui.html.twig', [
            'tabs' => Tabs::make('features')
                ->label('Features')
                ->tab('grid', 'Grid', 'Server-side paging, sorting, search and export.')
                ->tab('forms', 'Forms', Html::raw(
                    '<p class="mb-0">Validation, uploads and a <strong>rich text editor</strong>.</p>',
                ))
                ->tab('calendar', 'Calendar', 'Month, week, day and list views.')
                ->rememberInHash(),
            'modal' => Modal::make('newsletter-modal')
                ->title('Subscribe to the newsletter')
                ->body($form)
                ->cancelButton()
                ->button('Subscribe', 'primary', attributes: ['type' => 'submit', 'form' => 'newsletter'])
                ->staticBackdrop()
                // Reopen the modal when the server rejected the form.
                ->open($errors !== []),
            'notification' => Notification::make('flash')->fromFlash($request->getSession()->getFlashBag()->all()),
        ], new Response(status: $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
