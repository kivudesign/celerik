# Testing Guide

| Suite | Tool | Command | Location |
|---|---|---|---|
| PHP unit + adapter tests | PHPUnit 11 | `composer test` | `tests/php/` |
| JS unit + integration tests | Jest 29 (jsdom) | `npm test` | `tests/js/` |
| Accessibility (markup) | axe-core in jsdom (WCAG 2.1 A/AA tags) | `npm test` | `tests/js/a11y.test.js` |
| Accessibility (browser) | Playwright + axe-core, with color contrast | `npm run test:a11y` | `tests/browser/` |
| Code style | PHP_CodeSniffer (PSR-12), ESLint | `composer lint`, `npm run lint` | – |

## PHP Tests

- Components: rendering, escaping, ARIA attributes, option serialization, validation of arguments.
- Data layer: both request formats, field whitelisting, clamping, literal `%`/`_` in search, `ArrayDataSource` paging, search and sort.
- Events: strict ISO 8601 dates, validation (title, end ≥ start, safe colors and URLs), date ranges and overlap rules.
- Uploads: real files (valid PNG and PDF) and attacks (PHP renamed `.png`, `shell.php.gif` polyglot, SVG with a script, HTML, `../` in names), size and type limits, claim-once ids, revert, purge.
- File Manager: the locked-down elFinder options, safe-name rules, hidden dotfiles, and the upload hook rejecting content that does not match.
- Editor HTML: what the toolbars produce is kept; scripts, event handlers, `javascript:` links, iframes and styles are removed.
- Laravel adapter: `EloquentDataSource` and `EloquentEventSource` run against in-memory SQLite (`illuminate/database` is a dev dependency).
- Symfony adapter: the Twig extension renders through a real `Twig\Environment`. `DoctrineDataSource` and `DoctrineEventSource` run against in-memory SQLite (`doctrine/orm` is a dev dependency).

## JavaScript Tests

- Pure config builders (`*-config.js`) are unit-tested.
- Integration tests run the real libraries in jsdom:
  - DataTables paging, search and export buttons
  - Parsley rules and Bootstrap classes
  - Inputmask masks
  - FullCalendar month, week and list views
  - Quill (content loading, submitted value, validation state) and FilePond (labelling, required file, fallback to a plain input)
  - Bootstrap Modal (open on load, dialog semantics) and Tabs (URL hash, arrow keys), and Notyf (escaped messages, announcer text, sticky errors, named dismiss buttons)
  - File Manager config and asset loading (CSP nonce, loaded once). elFinder itself is tested against the real connector over HTTP and in a browser, not in jsdom.
- `npm test` first runs `tests/js/fixtures/build.php`, which renders the PHP components to HTML. JS tests always use the markup PHP really produces.
- Chart.js rendering is not tested in jsdom (no canvas); only its config is.
- Scheduler changes (create, move, resize, delete) are tested with an injected request function, including the rollback when the server refuses a change.

## Accessibility Tests

- axe-core checks every component's markup with the `wcag2a`, `wcag2aa`, `wcag21a` and `wcag21aa` rules, both before and after JS enhancement (and after a failed form validation).
- Color contrast can't be computed in jsdom, so a second suite runs axe-core in a real browser.

### Browser matrix (`npm run test:a11y`)

`tests/browser/server.php` renders every component on one page (Grid, Chart, Forms with the editor and an upload, Scheduler in month view, Tabs, File Manager with a real connector, Modal, Notifications). Bootstrap, jQuery and elFinder are served locally, so results don't depend on a CDN. Playwright starts it with PHP's built-in server.

For each theme (bootstrap, material, tailwind) in light and dark mode, the tests check:

- every component fires `celerik:ready`, with no console errors;
- axe-core finds no violations of the WCAG 2.1 A/AA rules, color contrast included;
- the same with the modal open.

```bash
npm run build                       # the tests use the bundle in public/
npx playwright install chromium     # once; or use an installed browser:
npm run test:a11y                   # PLAYWRIGHT_CHANNEL=msedge (or chrome) npm run test:a11y
```

On failure, `test-results/` holds a screenshot and a trace for each failed test (`npx playwright show-trace <trace.zip>`); in CI they are uploaded as the `playwright-results` artifact. Playwright starts its own browser with a temporary profile, so it never touches open browser windows.

`tests/browser/keyboard.spec.mjs` checks keyboard use once, in the default theme:

- Modal: opens with Enter, keeps focus inside, closes with Escape, returns focus to the trigger.
- Tabs: arrow keys, Home and End, with a single tab stop (roving `tabindex`).
- Grid: sorting (`aria-sort`) and paging from the keyboard.
- Form: a failed submit focuses the first invalid field; the rich text editor can be reached and typed in.
- Scheduler: toolbar navigation. Notifications: dismiss with Enter.

Screen readers and elFinder's own keyboard shortcuts are still checked manually.

## Theme Tests

- `tests/js/themes.test.js` compiles each theme and checks WCAG contrast for its light and dark palettes: body, muted and link text (4.5:1), text on surfaces, primary button labels including hover (4.5:1), and form field borders (3:1, WCAG 1.4.11).
- In a browser, axe-core runs on the full example page with color-contrast enabled for every theme and mode. All six combinations report no WCAG 2.1 A/AA violations.

## Performance Tests

- Benchmark grids with large datasets in server-side mode (`examples/plain` serves 250 rows; increase it in `people.php`).
- Test charts with high-volume data.

## Coverage

- Target: 80% line coverage for `src/` and `resources/js/`.
- Measure with `XDEBUG_MODE=coverage composer test -- --coverage-text` and `npx jest --coverage`.
