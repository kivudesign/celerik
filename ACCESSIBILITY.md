# Accessibility

Celerik UI targets **WCAG 2.2 level AA** for the markup and behaviour it produces, in every theme (bootstrap, material, tailwind) and in light and dark mode.

Automated tests can't prove conformance on their own. They catch regressions in what can be measured; screen-reader and keyboard use also need people. The [manual test plan](#manual-test-plan) below lists what is checked by hand. Its results table starts empty until a release is tested.

## How it is tested

| Check | Tool | Where |
|---|---|---|
| Markup before and after JavaScript, every component | axe-core in jsdom (WCAG 2.0/2.1 A and AA rules) | `tests/js/a11y.test.js`, `npm test` |
| Color contrast, 3 themes × light/dark, plus the open modal, the event dialog and the error summary | Playwright + axe-core (WCAG 2.0, 2.1 and 2.2 AA rules) | `tests/browser/a11y.spec.mjs`, `npm run test:a11y` |
| Keyboard: modal, tabs, grid, form, editor, calendar, toasts | Playwright | `tests/browser/keyboard.spec.mjs`, `scheduler.spec.mjs` |
| Windows High Contrast (forced colors): visible focus on every tab stop, selected states | Playwright, `forcedColors: 'active'` | `tests/browser/forced-colors.spec.mjs` |
| Reduced motion | Playwright, `reducedMotion: 'reduce'` | `tests/browser/motion.spec.mjs` |
| Target size (2.5.8) and focus not obscured (2.4.11) | Playwright measurements | `tests/browser/wcag22.spec.mjs` |
| Series without color (1.4.1) | Playwright | `tests/browser/chart.spec.mjs` |

All of these run in CI on every pull request. See [TESTING.md](TESTING.md).

## User preferences

- **Dark mode**: `data-bs-theme="dark"`; every theme keeps 4.5:1 text contrast and 3:1 for field borders and focus indicators.
- **Reduced motion** (`prefers-reduced-motion: reduce`): charts are drawn without animation, toast animations are cut to 1 ms, calendar, File Manager and upload transitions are removed. Bootstrap removes its own fades.
- **Forced colors** (Windows High Contrast): focus is shown with a system-color outline instead of a box-shadow ring; the active tab, today's date, the current page and the File Manager selection use system colors; toasts get a border.
- **Zoom and reflow**: components use Bootstrap's responsive layout and relative units.

## Components

| Component | Status | Keyboard | Notes |
|---|---|---|---|
| Grid | Supported | Tab to the search, page size, export, sort buttons and pager; Enter or Space sorts and pages | `caption()` names the table; header cells have `scope`; `aria-sort` and a polite status region report sorting, paging and results |
| Chart | Supported | No interaction | Canvas named by its title; a visually hidden data table is the text alternative; series differ by point shape and line dash (`patterns()`), in the legend too |
| Form | Supported | Native fields; a failed submit focuses the first invalid field, or the error summary | Labels, `aria-describedby` for help and errors, `aria-invalid`; required fields marked; optional `errorSummary()` announces all errors once, with links to the fields |
| Rich text editor | Supported | Tab and Shift+Tab move in and out of the editor (no keyboard trap); toolbar buttons are in the tab order | Labelled `role="textbox"` with `aria-multiline`, `aria-required`, `aria-invalid`; Quill's link tooltip inputs are labelled |
| File upload | Supported, with limits | The "Browse" button opens the file picker; Enter and Space work | Labelled by the field label; FilePond announces progress and errors. Dragging files is optional; the picker does the same |
| Scheduler | Supported | Toolbar buttons; Enter or Space on an event opens a dialog to change its dates or delete it; "New event" button | Dragging and selecting days are never the only way (2.1.1, 2.5.7). Month-view days of other months use the muted color instead of 30% opacity |
| Modal | Supported | Opens from its trigger; focus stays inside; Escape closes; focus returns to the trigger | Labelled by its title; Bootstrap's dialog pattern |
| Tabs | Supported | Arrow keys, Home and End; one tab stop | WAI-ARIA tabs pattern (`tablist`, `tab`, `tabpanel`), optional `label()` |
| Notification | Supported | Dismiss buttons are named and reachable | Polite live-region announcements of the plain text; errors stay until dismissed; other toasts pause while hovered or focused (2.2.1) |
| File manager | Partial | elFinder's own shortcuts (arrows, Enter, Delete, Ctrl+C/V…); toolbar buttons reachable | Region named by `title()`; selection contrast fixed; 24 px tree arrows. elFinder is not designed around screen readers: see the known limits below |
| Theme builder | Supported | Every token has a labelled hex field (the color pickers are skipped in the tab order); preview mode is a radio group; Undo moves focus on when it becomes disabled | The contrast report says Pass/Fail in text, and only its summary is announced; invalid values are reported on their field. Without JavaScript it is a form with a report computed in PHP |

### Known limits

These come from the third-party libraries and are not fixed by Celerik:

- **elFinder** (File Manager): the file list and folder tree are not exposed as ARIA grid or tree widgets, so screen readers announce little structure. Its dialogs are jQuery UI dialogs. If screen-reader users must manage files, also give them a plain list and upload form.
- **FullCalendar**: days are not focusable; events and toolbar buttons are reached with Tab, not with arrow keys, and long months mean many tab stops.
- **FilePond**: image previews have no text alternative beyond the file name.
- **Quill**: the toolbar is not a `role="toolbar"` widget (each button is a tab stop), and its button names come from Quill, in English.
- **Chart.js**: the canvas itself is an image; the data is available in the hidden table, not by exploring the chart.

## What your application must still do

Celerik makes its own components accessible; the page around them is yours:

- set the page language (`<html lang="…">`) and a unique `<title>`;
- use headings and landmarks (`<main>`, `<nav>`) in a logical order;
- name components (`title()`, `caption()`, `label()`) in the page's language, and translate built-in labels (`Scheduler::labels()`, the notification dismiss label, Quill and FilePond texts);
- keep your own content and colors at 4.5:1 contrast, also when you customize a theme;
- write error messages that say how to fix the problem;
- test your pages with the same tools: `documentation/` (the [celerik-doc](https://github.com/bim-g/celerik-doc) repository) has a page checker and an integration checklist.

## Manual test plan

Run before each release, on `tests/browser/server.php` (`php -S 127.0.0.1:8123 tests/browser/server.php`) and on one real application.

| # | Check | How |
|---|---|---|
| 1 | Keyboard only | Unplug the mouse. Reach and use every control on the page, in order, with Tab, Shift+Tab, Enter, Space, arrows and Escape. Focus is always visible. |
| 2 | NVDA + Firefox | Browse mode and focus mode. Each component is named; form errors and the error summary are announced once; toasts are read; the calendar event dialog is announced as a dialog with its title. |
| 3 | NVDA + Edge | Same as 2. |
| 4 | Windows Narrator + Edge | Same as 2, focus on forms and the modal. |
| 5 | VoiceOver + Safari (macOS) | Same as 2, with the rotor (headings, form controls, links). |
| 6 | Zoom 200% and reflow at 320 px width | No content or function is lost; no horizontal scrolling except inside the grid and calendar. |
| 7 | Windows High Contrast (Desert, Aquatic) | Focus, selection and toast boundaries stay visible. |
| 8 | Reduced motion | Windows "Animation effects" off, or macOS "Reduce motion": nothing slides or fades. |

### Results

| Release | Date | Tester | Checks | Result | Issues |
|---|---|---|---|---|---|
| | | | | | |

## Reporting a problem

Open an issue on [GitHub](https://github.com/kivudesign/celerik/issues) with the label `accessibility`. Say which component, theme and mode, the browser and assistive technology (with versions), what you expected and what happened. Accessibility bugs that block a task are treated like other high-priority bugs.
