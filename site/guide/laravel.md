# Laravel

For Laravel 11 and later. The service provider is auto-discovered.

## Setup

```bash
composer require celerik/ui
php artisan vendor:publish --tag=celerik-assets     # public/vendor/celerik
php artisan vendor:publish --tag=celerik-elfinder   # only for the File Manager
```

## Blade

```blade
<x-celerik-assets />
<x-celerik-assets theme="material" />
<x-celerik-assets theme="tailwind" custom-theme="{{ asset('css/acme.css') }}" :nonce="$cspNonce" />

<x-celerik :component="$grid" />
```

`<x-celerik-assets>` takes `base-url` (default `asset('vendor/celerik')`), `nonce`, `theme` and `custom-theme`.

## A server-side grid with Eloquent

```php
use App\Models\User;
use Celerik\Ui\Adapters\Laravel\EloquentDataSource;
use Celerik\Ui\Components\Grid;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    private function grid(): Grid
    {
        return Grid::make('users')
            ->caption('Users')
            ->url(route('users.data'))
            ->column('name')
            ->column('email', 'E-mail')
            ->export('copy', 'csv');
    }

    public function index()
    {
        return view('users.index', ['grid' => $this->grid()]);
    }

    public function data(Request $request)
    {
        $source = new EloquentDataSource(User::query(), ['name', 'email']);

        return response()->json($this->grid()->respond($source, $request->query()));
    }
}
```

## Forms

```php
$form = Form::make('contact')
    ->action(route('contact.store'))
    ->csrf(csrf_token())
    ->add(Field::email('email')->required())
    ->errors(session('errors')?->getBag('default')->toArray() ?? [])
    ->values(old());
```

`errors()` accepts Laravel's message bag arrays (`field => [messages]`) and dotted names such as `address.city`.

## CSRF for JSON requests

Components that post JSON (Scheduler, uploads, File Manager, ThemeBuilder) send the token in the `X-CSRF-TOKEN` header, which Laravel's middleware checks: pass `csrf_token()` to their `csrf()` method.

## The example

`examples/laravel/` holds drop-in controllers, routes and views, checked on Laravel 13. See its README.
