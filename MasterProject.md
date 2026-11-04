# Master Project

You are tasked with creating a complete open‑source UI component suite for PHP, inspired by Telerik UI, but built on top of Bootstrap and open‑source JavaScript libraries.
The suite must be framework‑agnostic but provide optional adapters for Laravel (Blade, Eloquent) and Symfony (Twig, Doctrine).

You must use the following documentation files as your blueprint:

- ./doc/PRD.md (Product Requirements Document)
- ./doc/Architecture.md
- ./doc/Rules.md
- ./doc/Phases.md
- ./doc/Design.md
- ./doc/Memory.md
- Components.md
- README.md
- CONTRIBUTING.md
- CODE_OF_CONDUCT.md
- SECURITY.md
- TESTING.md
- ROADMAP.md
- LICENSE.md

## Project Requirements

- Replicate all Telerik UI web components (Grid, Chart, Scheduler, Gantt, Editors, Forms, File Manager, PDF Viewer, etc.).
- For each component, integrate an open‑source alternative (see Components.md mapping).
- Provide PHP wrapper classes and Blade/Twig helpers for each component.
- Ensure Bootstrap‑based styling with SCSS variables and optional theme builder.
- Support server‑side data binding (JSON endpoints from PHP).
- Maintain accessibility (WCAG compliance).
- Package as a Composer library.

## Deliverables

1. **Codebase**
   - PHP classes for each component.
   - Laravel and Symfony adapters.
   - JS wrappers for open‑source libraries.
   - SCSS theming system.

2. **Documentation**
   - Examples for each component in `docs/examples/`.
   - API reference for PHP helpers.
   - Theming guide.

3. **Testing**
   - PHPUnit tests for PHP wrappers.
   - Jest tests for JS integration.
   - Accessibility tests with axe-core.

4. **Community**
   - CONTRIBUTING.md, CODE_OF_CONDUCT.md, SECURITY.md.
   - ROADMAP.md for phased delivery.
   - LICENSE.md (MIT core, note third‑party licenses).

## Strategy

- Phase 1: MVP (Grid, Chart, Form, Laravel and Symfony adapters).
- Phase 2: Scheduler, File Manager, theming.
- Phase 3: Accessibility polish, ThemeBuilder UI, docs site.
- Phase 4: Community growth, premium extensions.

## Instructions

- Generate the initial repo structure with all `.md` files included.
- Scaffold PHP classes and Blade/Twig helpers for Phase 1 components.
- Integrate DataTables (Grid), Chart.js (Chart), Parsley.js (Form validation).
- Provide working Laravel example in `examples/laravel`.
- Provide working Symfony example in `examples/symfony`.
- Ensure all code follows PSR‑12 and ESLint standards.
- Write unit tests for each component.
- Document usage in README.md and examples.

Your output should include:

- Suggested repo structure.
- Initial code scaffolding for Phase 1.
- Documentation stubs for examples.
- Notes on integration with open‑source libraries.
