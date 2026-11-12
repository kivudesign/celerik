# Changelog

All notable changes to Celerik UI are listed here, newest first. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/). Before 1.0, a minor version (0.x.0) may include breaking changes; they are listed under **Changed** or **Removed**.

The same version is published on Packagist (`celerik/ui`) and npm (`@celerikui/ui`).

## [Unreleased]

### Added

- Form fields: `Field::radio()` and `Field::checkboxes()` (groups in a fieldset, `inline()`), `Field::switch()`, `Field::range()` (shows its value) and `Field::color()`; `step()` for number, range, date and time fields.
- jQuery 4 support: the peer dependency accepts `^3.7.1 || ^4.0.0`, and the tests run on jQuery 4 ([#83](https://github.com/kivudesign/celerik/pull/83)).

### Changed

- Docs and examples load `jquery@4.0.0` from the CDN ([#83](https://github.com/kivudesign/celerik/pull/83)).
- The roadmap's Phase 4 is about components and usability. Celerik UI stays free and open source, with no paid or premium editions ([#81](https://github.com/kivudesign/celerik/pull/81)).

### Development

- Lockfiles are committed, and CI installs from them. A CI job also tests the lowest dependency versions `composer.json` allows ([#63](https://github.com/kivudesign/celerik/pull/63)).
- Dependabot opens weekly update pull requests for npm, Composer and GitHub Actions ([#63](https://github.com/kivudesign/celerik/pull/63)).
- Faster browser tests: they wait for animations to finish instead of fixed delays, and run on 4 workers on CI ([#82](https://github.com/kivudesign/celerik/pull/82)).
- Release notes are grouped by kind of change, and pull requests are labeled from their title.

## [0.1.1] - 2026-11-11

### Fixed

- Scheduler: day numbers on today's cell were below 4.5:1 contrast. They now use the body text color ([#59](https://github.com/kivudesign/celerik/pull/59)).

### Development

- GitHub Actions run on Node.js 24 ([#61](https://github.com/kivudesign/celerik/pull/61)).

## [0.1.0] - 2026-11-11

The first release with the full component set, the prebuilt bundle and the npm package. Version 0.0.2 was published the same day with the same content.

### Added

- **Scheduler** (FullCalendar), with Eloquent and Doctrine event sources ([#19](https://github.com/kivudesign/celerik/pull/19)).
- **File uploads** (FilePond) and a **rich text editor** (Quill) for forms ([#20](https://github.com/kivudesign/celerik/pull/20)).
- **Modal**, **Tabs** and **Notification** components ([#21](https://github.com/kivudesign/celerik/pull/21)).
- **File Manager** (elFinder), with a locked-down configuration ([#22](https://github.com/kivudesign/celerik/pull/22)).
- Material and Tailwind-inspired themes, dark mode on every theme ([#23](https://github.com/kivudesign/celerik/pull/23)).
- Accessibility conformance statement and a screen-reader test plan ([#34](https://github.com/kivudesign/celerik/pull/34)).
- **ThemeBuilder**: theme tokens, `Theme` in PHP, the builder component and the `celerik-theme` CLI ([#45](https://github.com/kivudesign/celerik/pull/45)).
- Documentation site with live demos, guides and the API reference, on GitHub Pages ([#55](https://github.com/kivudesign/celerik/pull/55)).
- The bundle is built at release time and published to npm as `@celerikui/ui`, served by jsDelivr (`Assets::cdnUrl()`) ([#56](https://github.com/kivudesign/celerik/pull/56), [#57](https://github.com/kivudesign/celerik/pull/57)).

### Changed

- The bundle is split into chunks: each page loads only the code of the components it uses ([#18](https://github.com/kivudesign/celerik/pull/18)).

### Fixed

- `LIKE` wildcards (`%`, `_`) in Grid search terms are escaped ([#18](https://github.com/kivudesign/celerik/pull/18)).
- Contrast fixes in dark mode ([#23](https://github.com/kivudesign/celerik/pull/23)).

## [0.0.1] - 2026-11-04

The Phase 1 MVP ([#11](https://github.com/kivudesign/celerik/pull/11)).

### Added

- Core: the component contract, registry, assets helper and data layer (`DataRequest`, `DataResult`, `ArrayDataSource`).
- **Grid** (DataTables), **Chart** (Chart.js) and **Form** (Parsley.js and Inputmask) components.
- Laravel adapter (Blade components, Eloquent) and Symfony adapter (bundle, Twig, Doctrine).
- Bootstrap SCSS theme and JS wrappers.
- Examples for plain PHP, Laravel and Symfony.

[Unreleased]: https://github.com/kivudesign/celerik/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/kivudesign/celerik/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/kivudesign/celerik/compare/v0.0.1...v0.1.0
[0.0.1]: https://github.com/kivudesign/celerik/releases/tag/v0.0.1
