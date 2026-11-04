# Testing Guide

| Suite | Tool | Command | Location |
|---|---|---|---|
| PHP unit + adapter tests | PHPUnit 11 | `composer test` | `tests/php/` |
| JS unit + integration tests | Jest 29 (jsdom) | `npm test` | `tests/js/` |
| Accessibility | axe-core (WCAG 2.1 A/AA tags) | `npm test` | `tests/js/a11y.test.js` |
| Code style | PHP_CodeSniffer (PSR-12), ESLint | `composer lint`, `npm run lint` | – |

## PHP Tests

- Components: rendering, escaping, ARIA attributes, option serialization, validation of arguments.
- Data layer: both request formats, field whitelisting, clamping, `ArrayDataSource` paging, search and sort.
- Laravel adapter: `EloquentDataSource` runs against in-memory SQLite (`illuminate/database` is a dev dependency).
- Symfony adapter: the Twig extension renders through a real `Twig\Environment`.
- `DoctrineDataSource` has no automated test yet (it needs `doctrine/orm`; planned).

## JavaScript Tests

- Pure config builders (`*-config.js`) are unit-tested.
- Integration tests run the real libraries in jsdom:
  - DataTables paging, search and export buttons
  - Parsley rules and Bootstrap classes
  - Inputmask masks
- `npm test` first runs `tests/js/fixtures/build.php`, which renders the PHP components to HTML. JS tests always use the markup PHP really produces.
- Chart.js rendering is not tested in jsdom (no canvas); only its config is.

## Accessibility Tests

- axe-core checks every component's markup with the `wcag2a`, `wcag2aa`, `wcag21a` and `wcag21aa` rules, both before and after JS enhancement (and after a failed form validation).
- Color contrast can't be computed in jsdom. It's checked manually in a browser for now, with automated browser runs (Playwright + axe) planned for Phase 3.
- Keyboard navigation has to be checked manually for each component until then.

## Performance Tests

- Benchmark grids with large datasets in server-side mode (`examples/plain` serves 250 rows; increase it in `people.php`).
- Test charts with high-volume data.

## Coverage

- Target: 80% line coverage for `src/` and `resources/js/`.
- Measure with `XDEBUG_MODE=coverage composer test -- --coverage-text` and `npx jest --coverage`.
