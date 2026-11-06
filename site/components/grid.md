# Grid

A data table on [DataTables 2](https://datatables.net/): sorting, search, paging and export, client-side or server-side.

<Demo name="grid" :min-height="420" />

<<< @/demos/grid.php

## Client-side or server-side

- **Client-side**: pass the rows with `rows()`. PHP renders them in the table, and the browser sorts, searches and pages them. Good for up to a few hundred rows.
- **Server-side**: pass an endpoint with `url()`, and answer it with `respond()` using the same grid definition (see [Data endpoints](../guide/data#grid)). The browser asks for one page at a time.

```php
$grid = Grid::make('users')->caption('Users')->url('/users/data')->column('name')->column('email', 'E-mail');

// GET /users/data
echo json_encode($grid->respond(new ArrayDataSource($rows), $_GET));
```

## Options

| Method | Use |
|---|---|
| `column($field, $title = null, $configure = null)` | Adds a column; the title defaults to the field name. `$configure` receives the `Column`. |
| `addColumn(Column ...$columns)` | Adds prepared columns (`Column::make('email', 'E-mail')`). |
| `caption($caption, $visuallyHidden = false)` | The table's caption, which names it for screen readers. Always set one. |
| `rows(iterable $rows)` | Client-side data: arrays or objects with the column fields (dot notation for nested values). |
| `url($url)` | Server-side mode. |
| `pageSize($size)`, `pageSizes([10, 25, 50])` | Rows per page, and the choices offered. |
| `searchable(false)` | Hides the search box. |
| `export('copy', 'csv', 'print')` | Export buttons. |
| `options([...])` | Raw DataTables options, applied last. |

### Columns

```php
Grid::make('orders')
    ->column('id', 'ID', static fn (Column $column) => $column->searchable(false)->width('5rem'))
    ->column('customer.name', 'Customer')
    ->column('total', 'Total', static fn (Column $column) => $column->className('text-end'))
    ->column('internal_ref', null, static fn (Column $column) => $column->hidden());
```

| Method | Use |
|---|---|
| `sortable(false)` | Not sortable (also refused by `respond()`). |
| `searchable(false)` | Not searched (also refused as a filter). |
| `hidden()` | Sent to the client but not shown, e.g. an id for row actions. |
| `className($class)`, `width($width)` | CSS class of the cells, and the column width. |

## Accessibility

- A real `<table>` with a `<caption>` and `scope="col"` headers, before and after JavaScript.
- Sort buttons are named ("Name: Activate to sort") and `aria-sort` reports the order; DataTables announces results in a polite status region.
- Every control can be used from the keyboard, and focus stays visible in Windows High Contrast.

## Security

`respond()` only sorts, searches and filters declared columns, rejects malformed field names, escapes `%` and `_` in the search term, and returns only declared fields, whatever your data source returns. Cells are always rendered as text.
