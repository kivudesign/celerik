# Symfony

For Symfony 7.

## Setup

```bash
composer require celerik/ui
```

Register the bundle in `config/bundles.php`, then install the assets:

```php
Celerik\Ui\Adapters\Symfony\CelerikUiBundle::class => ['all' => true],
```

```bash
php bin/console assets:install   # public/bundles/celerikui
```

## Twig

```twig
{{ celerik_assets() }}
{{ celerik_assets(theme: 'material') }}
{{ celerik_assets(theme: 'tailwind', customTheme: asset('css/acme.css'), nonce: csp_nonce) }}

{{ celerik(grid) }}
```

`celerik_assets()` takes `baseUrl` (default `/bundles/celerikui`), `nonce`, `theme` and `customTheme`.

## A server-side grid with Doctrine

```php
use App\Repository\ProductRepository;
use Celerik\Ui\Adapters\Symfony\DoctrineDataSource;
use Celerik\Ui\Components\Grid;

#[Route('/products')]
final class ProductController extends AbstractController
{
    private function grid(): Grid
    {
        return Grid::make('products')
            ->caption('Products')
            ->url($this->generateUrl('products_data'))
            ->column('name')
            ->column('price');
    }

    #[Route('', name: 'products')]
    public function index(): Response
    {
        return $this->render('products/index.html.twig', ['grid' => $this->grid()]);
    }

    #[Route('/data', name: 'products_data')]
    public function data(Request $request, ProductRepository $products): JsonResponse
    {
        $qb = $products->createQueryBuilder('p')->select('p.id, p.name, p.price');

        return $this->json($this->grid()->respond(new DoctrineDataSource($qb, ['name']), $request->query->all()));
    }
}
```

Select scalar fields rather than whole entities. Map public names to DQL paths with `fieldMap`, for example `['author' => 'a.name']`.

## CSRF

Symfony does not check JSON requests by itself. Components that post JSON send the token in the `X-CSRF-TOKEN` header: generate it with `CsrfTokenManagerInterface`, and check it in the endpoint:

```php
if (!$this->isCsrfTokenValid('calendar', (string) $request->headers->get('X-CSRF-TOKEN'))) {
    return new JsonResponse(['message' => 'Invalid CSRF token.'], 419);
}
```

## The example

`examples/symfony/` holds drop-in controllers, entities and templates, checked on Symfony 7.4. See its README.
