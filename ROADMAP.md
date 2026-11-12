# Roadmap

## Phase 1 (MVP) — done ✅

- Grid, Chart and Form components.
- Laravel and Symfony adapters.
- Bootstrap theme.

## Phase 2 — done ✅

- Scheduler, File Manager, Upload, rich text editor.
- Modals, Tabs, Notifications.
- Material and Tailwind-inspired themes, with light and dark mode.

## Phase 3 — done ✅

- Accessibility polish ✅: browser accessibility tests in every theme, reduced motion, Windows High Contrast, keyboard editing in the Scheduler, form error summary, charts readable without color, WCAG 2.2 AA (see [ACCESSIBILITY.md](ACCESSIBILITY.md)).
- ThemeBuilder UI ✅: theme tokens and presets, `Theme` in PHP with WCAG contrast checks, the ThemeBuilder component (live preview, contrast report, import and export) and the `celerik-theme` command (see [THEMING.md](THEMING.md)).
- Documentation portal ✅: a VitePress site (`site/`) with getting started guides, a page and a live demo per component (rendered by PHP at build time), the theming, security, accessibility and testing guides, and an API reference generated from the code; checked with axe-core and a link check, and published to GitHub Pages (<https://kivudesign.github.io/celerik/>).

## Distribution — done ✅

- Releases include the built bundle, so installing needs no Node.js ([GitHub releases](https://github.com/kivudesign/celerik/releases), [Packagist](https://packagist.org/packages/celerik/ui)).
- The bundle on npm (`@celerikui/ui`) and on the jsDelivr CDN (`Assets::cdnUrl()`).

## Phase 4 — Components and usability

Celerik UI is, and will stay, free and open source (MIT). Phase 4 makes it cover more of what PHP applications need, and makes it easier to use:

- Form fields: radio group, switch, range, color, a searchable select with remote options, date and time ranges.
- Grid: row selection with bulk actions, column filters, inline editing.
- New components, in order of demand: Tree view, Stepper (form wizard), Accordion, Dropdown menu, Card and stat tile.
- Developer experience: clear error messages, a setup check command, translations of every built-in text, recipes in the documentation (CRUD admin page, dashboard, upload with preview, a form in a modal), complete IDE autocompletion.
- Community: issue forms, labels for first contributions, GitHub Discussions, a showcase of projects built with Celerik.

## Road to 1.0

- Review the public API (PHP builders, JavaScript API and events, CSS custom properties, theme schema, bundle files), and fix what needs fixing while 0.x allows it.
- Publish a backward compatibility policy and a changelog, then release 1.0 after a release candidate.

Ideas and requests are welcome in the [issues](https://github.com/kivudesign/celerik/issues).
