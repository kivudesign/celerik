# AI Development Notes

Guidance for AI agents (and humans) scaffolding code in this repository.

## Read First

1. [README.md](README.md): what the suite does and how components are used.
2. [CONTRIBUTING.md](CONTRIBUTING.md): coding, accessibility, licensing and security rules.
3. [Components.md](Components.md): which library to use for which component.
4. [ROADMAP.md](ROADMAP.md): what is in scope now.
5. `src/Component.php` and `src/Data/`: the component and data-source contracts.

## Decisions Already Made

| Topic | Decision |
|---|---|
| Package / namespace | `celerik/ui`, `Celerik\Ui\` |
| Versions | PHP ≥ 8.2, Bootstrap 5.3, Laravel 11+, Symfony 7 |
| jQuery | Peer dependency (DataTables, Parsley.js need it); external in the Vite build |
| Grid protocol | The endpoint accepts DataTables parameters (or the simple format) and returns `{data, meta}`; the JS wrapper converts |
| Phase 1 Form scope | Validation (Parsley) + input masks (Inputmask); Upload and WYSIWYG in Phase 2 |
| JS init | `data-celerik` + `data-celerik-options`, no inline scripts (CSP-safe) |
| Assets | Prebuilt ES module in `public/`: a small entry plus one lazy chunk per component library, published by the framework adapters |

## Scaffolding Templates

### CRUD grid for a model (Laravel)

```php
// app/Ui/ProductGrid.php — one definition for the page and its endpoint
final class ProductGrid
{
    public static function make(): Grid
    {
        return Grid::make('products')
            ->caption('Products')
            ->url(route('products.data'))
            ->column('id', 'ID', fn (Column $c) => $c->searchable(false))
            ->column('name')
            ->column('price', null, fn (Column $c) => $c->searchable(false)->className('text-end'));
    }
}

// Controller
public function data(Request $request): JsonResponse
{
    $this->authorize('viewAny', Product::class);

    return response()->json(ProductGrid::make()->respond(new EloquentDataSource(Product::query()), $request->query()));
}
```

Map database columns to grid columns like this:
- String columns → searchable.
- Numeric, date and boolean columns → `searchable(false)`. A `LIKE` on a non-text column fails on PostgreSQL.
- Secrets → never declared.

### Endpoint pattern

- **Grid**: `GET /<resource>/data` → `$grid->respond($source, $query)`.
- **Chart**: `GET /<resource>/stats` → `['data' => $chart->toData()]` for `Chart::url()`.
- **Form**: a normal POST. On failure, render again with `errors()` and `values()`.
- **Scheduler**:
  - `GET /<resource>/events?start=…&end=…` → `$scheduler->respond($source, $query)`. Answer 400 on `InvalidArgumentException`.
  - `POST /<resource>/events/{create,update,delete}` with a JSON body and the `X-CSRF-TOKEN` header. Validate with `Event::fromArray()` and answer `{data: event}` (`{data: null}` for delete). Any non-2xx status rolls the change back in the calendar.
  - Convert dates to the server/app time zone before saving: Eloquent and Doctrine drop UTC offsets.
- **Uploads** (`Field::file()->upload($url)`):
  - `POST $url` with one file → `UploadHandler::process()` → answer the id as `text/plain` (422 with the message on `UploadException`).
  - `DELETE $url` with the id as body → `UploadHandler::revert()`.
  - On form submit, the field holds ids → `claim($id, $dir)`. If the browser sent real files instead (no JavaScript), use `store()`.
  - One `UploadRules` per field, shared by the field and its handler. Directories outside the web root.
- **Editor**: always `EditorHtml::sanitize()` the submitted HTML before storing it.
- **Modal with a form**: put the form in `body()`, submit it with a footer button (`['type' => 'submit', 'form' => '<form id>']`), and call `open($errors !== [])` so server errors reopen it.
- **Notifications**: Post/Redirect/Get with flash messages, then `Notification::make()->fromFlash(...)` in the layout. From JavaScript: `Celerik.notify(type, message)`.
- **Themes**: pick one with `Assets::tags($url, theme: 'material'|'tailwind')` (or the Blade/Twig equivalents). When changing theme colors, keep `tests/js/themes.test.js` passing: it checks WCAG contrast.
- **File manager**: one route accepting GET and POST that calls `FileManagerConnector::run(FileManagerConfig::local($rootOutsideWebRoot, $rules), authorized: <real check>)`. Never pass `true` unconditionally. Serve elFinder's assets (`vendor/studio-42/elfinder/{css,img,js,sounds}`) at `FileManager::assetsUrl()`.

### Integration snippets

- Eloquent → Grid: `new EloquentDataSource(Model::query()->where(...))`.
- Doctrine → Grid: `new DoctrineDataSource($repo->createQueryBuilder('p')->select('p.id, p.name'))`.
- Doctrine → Chart: `->fromRows($qb->select('p.category AS label, COUNT(p.id) AS total')->groupBy('p.category')->getQuery()->getArrayResult(), 'label', ['total' => 'Total'])`.
- Eloquent → Chart: `->fromRows(Model::query()->selectRaw('... as label, count(*) as total')->groupBy('label')->get()->toArray(), 'label', ['total' => 'Total'])`.

## Generated Docs and Tests

For every new component, generate:
- A PHPUnit test covering rendering, escaping, ARIA attributes and `toArray()`.
- A fixture in `tests/js/fixtures/build.php` (axe-core checks it automatically).
- A Jest test for its `*-config.js` module and one integration test with the real library.
- PHPDoc on every public method, and an example in each `examples/` folder.

Always run `composer lint && composer test && npm run lint && npm test` before proposing a change.
