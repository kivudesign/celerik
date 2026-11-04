<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use Celerik\Ui\Adapters\Symfony\DoctrineDataSource;
use Celerik\Ui\Components\Chart;
use Celerik\Ui\Components\Form;
use Celerik\Ui\Components\Form\Field;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Grid\Column;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/celerik', name: 'celerik_')]
final class CelerikDemoController extends AbstractController
{
    private const CSRF_ID = 'celerik_contact';

    public function __construct(private readonly ProductRepository $products)
    {
    }

    #[Route('/grid', name: 'grid', methods: ['GET'])]
    public function grid(): Response
    {
        return $this->render('celerik/grid.html.twig', ['grid' => $this->productsGrid()]);
    }

    /**
     * The same Grid definition renders the table and answers its requests,
     * so only declared columns can be sorted, searched or returned.
     */
    #[Route('/grid/data', name: 'grid_data', methods: ['GET'])]
    public function gridData(Request $request): JsonResponse
    {
        $query = $this->products->createQueryBuilder('p')->select('p.id, p.name, p.category, p.price');

        return $this->json(
            $this->productsGrid()->respond(new DoctrineDataSource($query), $request->query->all()),
        );
    }

    #[Route('/chart', name: 'chart', methods: ['GET'])]
    public function chart(): Response
    {
        $rows = $this->products->createQueryBuilder('p')
            ->select('p.category AS category, COUNT(p.id) AS products')
            ->groupBy('p.category')
            ->orderBy('p.category')
            ->getQuery()
            ->getArrayResult();

        $chart = Chart::make('categories')
            ->type('bar')
            ->title('Products per category')
            ->fromRows($rows, 'category', ['products' => 'Products'])
            ->height(320);

        return $this->render('celerik/chart.html.twig', ['chart' => $chart]);
    }

    #[Route('/form', name: 'form', methods: ['GET', 'POST'])]
    public function form(Request $request, ValidatorInterface $validator, CsrfTokenManagerInterface $csrf): Response
    {
        $errors = [];
        $values = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $values = $request->request->all();
            $violations = $validator->validate($values, new Assert\Collection([
                'fields' => [
                    'name' => [new Assert\NotBlank(), new Assert\Length(max: 80)],
                    'email' => [new Assert\NotBlank(), new Assert\Email()],
                    'phone' => new Assert\Optional(new Assert\Length(max: 20)),
                    'message' => [new Assert\NotBlank(), new Assert\Length(min: 10)],
                ],
                'allowExtraFields' => true,
            ]));

            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] ??= (string) $violation->getMessage();
            }

            if ($errors === []) {
                $this->addFlash('success', 'Thanks, your message was sent.');

                return $this->redirectToRoute('celerik_form');
            }
        }

        $form = Form::make('contact')
            ->action($this->generateUrl('celerik_form'))
            ->csrf($csrf->getToken(self::CSRF_ID)->getValue())
            ->add(
                Field::text('name')->required()->maxLength(80),
                Field::email('email')->required(),
                Field::tel('phone')->mask('(999) 999-9999')->help('Optional.'),
                Field::textarea('message')->required()->minLength(10),
            )
            ->errors($errors)
            ->values($values)
            ->submit('Send');

        $response = new Response(status: $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);

        return $this->render('celerik/form.html.twig', ['form' => $form], $response);
    }

    private function productsGrid(): Grid
    {
        return Grid::make('products')
            ->caption('Products')
            ->url($this->generateUrl('celerik_grid_data'))
            ->addColumn(
                Column::make('id', 'ID')->searchable(false),
                Column::make('name'),
                Column::make('category'),
                Column::make('price')->searchable(false)->className('text-end'),
            )
            ->pageSize(25)
            ->export('copy', 'csv', 'print');
    }
}
