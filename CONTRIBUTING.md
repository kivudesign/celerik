# Contributing Guide

We welcome contributions from developers, designers and AI agents.

## How to Contribute

1. Fork the repo.
2. Create a feature branch (`feature/grid-component`).
3. Implement changes following [doc/Rules.md](doc/Rules.md) (PSR-12, ESLint, WCAG 2.1 AA).
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
  - Add usage examples in `examples/`.
  - Add an entry in `doc/guides/api-reference.md`.
  - List the library in `THIRD_PARTY_NOTICES.md`.

## Code Review

- All PRs must pass CI checks.
- Accessibility and performance must be validated.
- At least one maintainer approval is required.

## Community Standards

- Respect the rules in [doc/Rules.md](doc/Rules.md).
- Keep documentation up to date.
- Be kind and collaborative (see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)).
