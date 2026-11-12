# Data endpoints

A component with a `url()` loads its data from your endpoint. Use **the same component definition** on the page and in the endpoint: the endpoint then accepts only the declared columns, sorts only sortable ones and pages at the declared size.

## Grid

```php
$grid = Grid::make('users')
    ->caption('Users')
    ->url('/users/data')
    ->column('id', 'ID', static fn ($column) => $column->searchable(false))
    ->column('name')
    ->column('email', 'E-mail');

// GET /users/data
header('Content-Type: application/json');
echo json_encode($grid->respond(new ArrayDataSource($rows), $_GET));
```

`respond()` reads the request, asks the data source for one page, and keeps only the declared columns in each row, so a source that returns more fields never leaks them.

### The response

Every Celerik endpoint answers with the same envelope:

```json
{
  "data": [{ "id": 1, "name": "Ada Lovelace", "email": "ada@example.com" }],
  "meta": { "page": 1, "perPage": 10, "total": 250, "filtered": 12, "draw": 3, "sort": [], "search": "ada", "filters": {} }
}
```

### The request

`respond()` understands two formats:

- the DataTables server-side protocol, sent by the grid: `draw`, `start`, `length`, `order[i][column|dir]`, `columns[i][data]`, `search[value]`;
- a simple format for your own clients: `?page=2&per_page=25&sort=name,-created_at&search=ada&filter[city]=Paris`.

Unknown fields are refused, the search term matches `%` and `_` literally, and a page holds at most `DataRequest::MAX_PER_PAGE` (1000) rows.

### Data sources

| Source | For |
|---|---|
| `Data\ArrayDataSource($rows)` | Arrays already in memory |
| `Adapters\Laravel\EloquentDataSource($query, searchable: ['name', 'email'])` | An Eloquent or query builder (Laravel) |
| `Adapters\Symfony\DoctrineDataSource($queryBuilder, searchable: [...], fieldMap: ['author' => 'a.name'])` | A Doctrine query builder (Symfony) |

Write your own by implementing `Data\DataSourceInterface::fetch(DataRequest $request): DataResult`.

## Chart

A chart with `url()` fetches `{"data": {"labels": [...], "datasets": [...]}}` and builds its data table text alternative from it:

```php
Chart::make('signups')->type('bar')->title('Sign-ups per day')->url('/stats/signups');

// GET /stats/signups
echo json_encode(['data' => Chart::make()->labels($days)->dataset('Sign-ups', $counts)->toData()]);
```

## Searchable select

A select with `remote()` asks `GET /authors/options?search=ada` as the user types. `respond()` searches and sorts on the label field only, ignores any other parameter, and answers with the value and label of the first matches:

```php
$author = Field::select('author_id', [])->remote('/authors/options', value: 'id', label: 'name', limit: 20);

// GET /authors/options
echo json_encode($author->respond(new ArrayDataSource($users), $_GET));
// {"data": [{"value": "2", "label": "Ada Lovelace"}], "meta": {...}}
```

See [Form: searchable and multiple selects](../components/form#searchable-and-multiple-selects).

## Scheduler

The calendar requests the visible range, `?start=…&end=…`, and posts changes as JSON:

```php
$scheduler = Scheduler::make('team')
    ->url('/events')
    ->createUrl('/events/create')
    ->updateUrl('/events/update')
    ->deleteUrl('/events/delete')
    ->csrf($csrfToken);   // sent in the X-CSRF-TOKEN header

// GET /events?start=2026-11-01&end=2026-12-01
try {
    echo json_encode($scheduler->respond(new ArrayEventSource($events), $_GET));
} catch (InvalidArgumentException $e) {
    http_response_code(400); // missing, invalid or too long range
}
```

Changes arrive as `{"id", "title", "start", "end", "allDay"}`. Validate them with `Data\Event::fromArray()`, save them, and answer `{"data": event}`, or an error status: the calendar then rolls the change back and fires `celerik:scheduler:error`.

| Source | For |
|---|---|
| `Data\ArrayEventSource($events)` | Events in memory |
| `Adapters\Laravel\EloquentEventSource($query, ['allDay' => 'all_day'])` | Eloquent; map your columns to `id`, `title`, `start`, `end`, `allDay`, `color`, `url` |
| `Adapters\Symfony\DoctrineEventSource($queryBuilder, [...])` | Doctrine, with the same mapping |

## Security

- Put endpoints behind your authorization, like any other route.
- Changes (Scheduler, uploads, the File Manager, the ThemeBuilder) must check the CSRF token: Laravel does it for the `X-CSRF-TOKEN` header; in Symfony and plain PHP check it yourself.
- Answer bad input with 400 or 422 and a message, not 500.
