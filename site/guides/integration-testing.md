# Testing an integration

Celerik's own test suites prove that the components work. They cannot prove that **your** application wires them correctly: routes, CSRF, authorization, asset paths, time zones, themes and your Content Security Policy live in your code.

Test in this order. Each layer catches problems the previous one cannot see. Step-by-step guides and ready-made scripts are in the [celerik-doc](https://github.com/bim-g/celerik-doc) repository.

| # | Layer | Catches | Guide |
|---|---|---|---|
| 1 | Unit and component tests | Logic and rendering bugs | [Test suites](https://github.com/bim-g/celerik-doc/blob/main/testing/test-suites.md) |
| 2 | HTTP checks | Endpoint contracts, CSRF, validation, authorization, stored data | [HTTP checks](https://github.com/bim-g/celerik-doc/blob/main/testing/http-checks.md) |
| 3 | Browser checks | Missing assets, wrong chunk paths, JavaScript errors, components that never start | [Browser checks](https://github.com/bim-g/celerik-doc/blob/main/testing/browser-checks.md) |
| 4 | Accessibility in a browser | Contrast and markup issues that only appear once libraries have rendered, in your theme | [Accessibility](https://github.com/bim-g/celerik-doc/blob/main/testing/accessibility.md) |
| 5 | Content Security Policy | Inline scripts or styles your policy blocks | [CSP](https://github.com/bim-g/celerik-doc/blob/main/testing/csp.md) |
| 6 | Security | Upload, rich text and File Manager attacks | [Security](https://github.com/bim-g/celerik-doc/blob/main/testing/security.md) |

## The scripts

| Script | Does |
|---|---|
| `check-page.sh` | Loads a page in headless Edge or Chrome (its own profile) and prints the components that started, axe-core violations, console errors and CSP violations. |
| `browser-check.js` | Appended to a test page, it waits for every component and runs axe-core with colour contrast. |
| `elfinder-security.php` | Sends attack requests to your File Manager connector and reports which were refused. |
| `make-test-files.php` | Writes valid and malicious sample uploads. |

## Your theme

A custom theme is your code too. Check it before it ships:

```bash
vendor/bin/celerik-theme check theme.json      # every contrast pair, exit 1 on a failure
```

Then load a page that uses it in light and dark mode with `check-page.sh`: axe-core checks the contrast of the rendered components, which the token check cannot see (for example text on a coloured calendar event).

## Checklist

Copy this into your pull request or release notes.

- **Assets**: Bootstrap 5.3 (CSS and `bootstrap.bundle.js`) and jQuery 3.7 load before the Celerik tags; the whole `public/` folder is published, `chunks/` included, again after every update; the base theme and your custom theme load in that order.
- **Endpoints**: each endpoint uses the same component definition as its page (`respond()`); endpoints are behind authorization; JSON changes check the CSRF token; bad input answers 400 or 422 with a message.
- **Data**: dates are converted to the right time zone before saving; uploads are claimed or stored outside the web root, and `purge()` runs from a cron job; editor HTML is sanitized with `EditorHtml::sanitize()`.
- **Browser**: every component starts, with no console errors; no axe-core violations in each theme and mode; Tab order, Escape in dialogs, arrow keys in tabs, and the calendar's event dialog work from the keyboard; your production CSP reports no violations.
- **Theme**: `celerik-theme check` passes; the ThemeBuilder, if deployed, is behind authorization or local only.
- **File Manager** (if used): its root is outside the web root, one per tenant or user when files are private; `elfinder-security.php` reports every attack refused; `composer audit` is clean.
