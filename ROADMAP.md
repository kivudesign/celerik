# Roadmap

Celerik UI is, and will stay, free and open source (MIT).

## Where we are going

Celerik UI aims to give PHP applications what [Telerik UI](https://www.telerik.com/kendo-jquery-ui) gives .NET and JavaScript ones: a complete set of business components, ready to use from PHP, accessible and themeable. It is far from complete yet. The phases below fill the gaps in order of need, one family of components at a time.

Two references guide the work:

- **Telerik UI's catalogue** sets the breadth: which components a full suite has. [Components.md](Components.md) maps each one to an open-source library and to a phase.
- **[Filament](https://filamentphp.com)** sets the depth for admin and business screens: forms with layout and repeatable groups, tables with filters and actions, confirmation dialogs, read-only record views and dashboard widgets. Filament is built on Laravel and Livewire; Celerik offers the same ideas to plain PHP, Laravel and Symfony, with server-rendered HTML and progressive enhancement.

Every component follows the same rules: a PHP builder, an endpoint helper when it loads data, keyboard and screen-reader support (WCAG 2.2 AA, tested in every theme and mode), the three themes in light and dark mode, a documentation page with a live demo, and only MIT, Apache 2.0, BSD or ISC libraries.

## Done

### Phase 1 (MVP) ✅

- Grid, Chart and Form components.
- Laravel and Symfony adapters.
- Bootstrap theme.

### Phase 2 ✅

- Scheduler, File Manager, Upload, rich text editor.
- Modals, Tabs, Notifications.
- Material and Tailwind-inspired themes, with light and dark mode.

### Phase 3 ✅

- Accessibility polish: browser accessibility tests in every theme, reduced motion, Windows High Contrast, keyboard editing in the Scheduler, form error summary, charts readable without color, WCAG 2.2 AA (see [ACCESSIBILITY.md](ACCESSIBILITY.md)).
- ThemeBuilder: theme tokens and presets, `Theme` in PHP with WCAG contrast checks, the ThemeBuilder component and the `celerik-theme` command (see [THEMING.md](THEMING.md)).
- Documentation portal: guides, a page and a live demo per component, an API reference generated from the code, published to GitHub Pages (<https://kivudesign.github.io/celerik/>).

### Distribution ✅

- Releases include the built bundle, so installing needs no Node.js ([GitHub releases](https://github.com/kivudesign/celerik/releases), [Packagist](https://packagist.org/packages/celerik/ui)).
- The bundle on npm (`@celerikui/ui`) and on the jsDelivr CDN (`Assets::cdnUrl()`).
- Reproducible CI and releases, weekly dependency updates, a [changelog](CHANGELOG.md) and release notes grouped by kind of change.

## Next

### Phase 4 — Forms (in progress)

Everything a create or edit screen needs.

- Fields:
  - ✅ radio group, checkbox group, switch, range, color;
  - ✅ searchable, multiple and remote selects;
  - ✅ date, time and date-time pickers, date and time ranges (the browser's native pickers);
  - tags input, rating, OTP code, key-value list.
- Layout:
  - ✅ sections (collapsible), fieldsets and columns inside a form;
  - a wizard (multi-step form, the Telerik Stepper and Wizard).
- Repeatable groups of fields (a repeater): order lines, addresses, contacts.

### Phase 5 — Data

Tables and lists that do the work of an admin screen.

- Grid:
  - column filters (text, select, yes/no, date range);
  - row actions (view, edit, delete with confirmation);
  - row selection with bulk actions;
  - column totals and grouping;
  - columns the user can show or hide;
  - inline editing;
  - an empty state.
- Actions: buttons that open a confirmation dialog or a form in a modal, usable anywhere (grid rows, toolbars, pages).
- ListView with a pager, TreeView, TreeList.
- Task board (Kanban) with drag and drop.

### Phase 6 — Navigation and layout

The frame of an application.

- Admin layout: app bar, sidebar navigation (the Telerik Drawer), breadcrumb, user menu.
- Menu, context menu, dropdown and split buttons, toolbar.
- Accordion (the Telerik PanelBar), card, splitter, tile layout for dashboards.

### Phase 7 — Display and feedback

Showing records and states.

- Record view: a read-only layout of a record's fields, by sections (Filament's infolists).
- Stat tiles with a trend and a sparkline, and dashboard widgets (stats, chart, table).
- Badge, avatar, chip, tooltip and popover, progress bar and circle, loader and skeleton.
- Timeline, carousel.

### Phase 8 — Visualization, media and specialized editors

- Gauges, sparklines, Gantt chart, map, diagram and org chart.
- QR code and barcode, signature pad, PDF viewer, media player.
- Markdown editor, code editor, image editor.

## Later

- Spreadsheet and PivotGrid: large libraries, after the phases above.
- Developer experience: clear error messages, a setup check command, translations of every built-in text, recipes in the documentation (CRUD admin page, dashboard), complete IDE autocompletion.
- Community: issue forms, labels for first contributions, GitHub Discussions, a showcase of projects built with Celerik.

## Road to 1.0

- Review the public API (PHP builders, JavaScript API and events, CSS custom properties, theme schema, bundle files), and fix what needs fixing while 0.x allows it.
- Publish a backward compatibility policy, then release 1.0 after a release candidate.

Ideas and requests are welcome in the [issues](https://github.com/kivudesign/celerik/issues).
