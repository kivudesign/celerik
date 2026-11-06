# Tabs

Bootstrap 5 tabs following the WAI-ARIA tabs pattern.

<Demo name="tabs" />

<<< @/demos/tabs.php

## Options

| Method | Use |
|---|---|
| `tab($id, $label, $content)` | A tab and its panel. Content is text (escaped) or a `Stringable`: another component, `Html::raw()`. |
| `label($label)` | The tab list's accessible name, e.g. "Account settings". |
| `active($id)` | The tab selected first (default: the first one). |
| `style('tabs' \| 'pills' \| 'underline')`, `fill()` | Look, and tabs sharing the full width. |
| `rememberInHash()` | Keeps the selected tab in the URL hash (`#security`), so links and reloads open it. |

## Accessibility

`tablist`, `tab` and `tabpanel` roles with `aria-controls`, `aria-selected` and `aria-labelledby`. Left and Right arrows, Home and End move between tabs, and only the selected tab is in the Tab order. Bootstrap's JavaScript (`bootstrap.bundle.js`) switches the panels: without it only the selected panel is shown.
