# Contributing Guide

We welcome contributions from developers, designers and AI agents.

## How to Contribute

1. Fork the repo.
2. Create a feature branch (`feature/grid-component`).
3. Implement changes following the [project rules](#project-rules).
4. Add or update documentation in the relevant `.md` file.
5. Write tests (PHPUnit, Jest, axe-core). See [TESTING.md](TESTING.md).
6. Run `composer lint && composer test && npm run lint && npm test`.
7. Submit a pull request with a clear description.

## Adding a New Component

- Check [Components.md](Components.md) for the chosen library, and check its license (core: MIT, Apache 2.0, BSD only).
- **PHP**
  - Add a class in `src/Components/` extending `Celerik\Ui\Component`.
  - Register it in `ComponentRegistry::withDefaults()`.
- **JS**
  - Add `resources/js/<name>.js` with an initializer, and register it in `resources/js/index.js`.
  - Keep config building in a pure `<name>-config.js` module so it can be unit-tested.
- **Styles**: add them to `resources/scss/_components.scss` using variables from `_variables.scss`.
- **Tests**
  - Add the component to `tests/js/fixtures/build.php` so axe-core checks its markup.
  - Add PHPUnit and Jest tests.
- **Docs**
  - Add a page in `site/components/`, with a demo in `site/demos/<name>.php` (`<Demo name="…" />` and `<<< @/demos/<name>.php`), and add it to the sidebar in `site/.vitepress/config.mjs`.
  - Add usage examples in `examples/`.
  - Document every public method with PHPDoc: the API reference is generated from it (`npm run docs:reference`).
  - List the library in `THIRD_PARTY_NOTICES.md`.

## Project Rules

- **Code style**
  - PHP: PSR-12 (`composer lint`), `declare(strict_types=1)`, classes `final` unless designed for extension.
  - JavaScript: ESLint recommended rules (`npm run lint`), ES modules.
- **Framework neutrality**: code outside `src/Adapters/` must not depend on Laravel, Symfony, Doctrine or Twig. Those packages are only `require-dev` or `suggest`.
- **Accessibility**: target WCAG 2.1 AA.
  - Tables have captions, form fields have labels, charts have a text alternative.
  - Every component has an axe-core fixture.
- **Performance**: large grids use server-side mode. Page size is capped at 1000 rows.
- **Licensing**: only MIT, Apache 2.0, BSD or ISC libraries in the bundle. List each one in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
- **Security**
  - Escape all output and emit no inline scripts.
  - Only declared fields may be sorted, searched or returned.
  - Forms carry CSRF tokens.
  - Data endpoints are protected by the application's authorization.

## Code Review

- All PRs must pass CI checks.
- Accessibility and performance must be validated.
- At least one maintainer approval is required.

## Community Standards

- Respect the [project rules](#project-rules).
- Keep documentation up to date.
- Be kind and collaborative (see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)).
