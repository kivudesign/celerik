# Component Mapping

Telerik UI → open-source alternatives.

This document maps the major Telerik UI web components to open-source libraries.
- ✅ The **core** only includes MIT, Apache 2.0 or BSD libraries, or native Bootstrap 5.3 / HTML features.
- ⚠️ GPL, commercial or proprietary libraries are listed separately as **optional extras**.
- 🧩 marks libraries that need jQuery (a peer dependency the application loads itself).

The **Phase** column refers to the [roadmap](ROADMAP.md); "–" means not yet scheduled.

---

## Core Components

### Data & Lists

| Component | Library | License | Phase |
|---|---|---|---|
| Grid | DataTables 🧩 (+ Buttons) | MIT | 1 ✅ |
| Grid (alternative) | AG Grid Community | MIT | – |
| TreeList | FancyTree 🧩 | MIT | – |
| TreeView | jsTree 🧩 | MIT | – |
| PivotGrid | PivotTable.js 🧩 | MIT | – |
| Spreadsheet | Univer | Apache 2.0 | – |
| ListView | Native HTML lists + Bootstrap list group | MIT | – |

### Selection & Inputs

| Component | Library | License | Phase |
|---|---|---|---|
| DropDownList, ComboBox, MultiColumnComboBox | Tom Select | Apache 2.0 | – |
| MultiSelect | Tom Select / Choices.js | Apache 2.0 / MIT | – |
| AutoComplete | Tom Select / Awesomplete | Apache 2.0 / MIT | – |
| NumericTextBox | HTML5 `input type=number` | – | 1 ✅ (`Field::number`) |
| MaskedTextBox | Inputmask | MIT | 1 ✅ (`Field::mask`) |
| Slider | noUiSlider | MIT | – |
| Switch | Bootstrap `form-switch` | MIT | – |
| ColorPicker | Pickr | MIT | – |
| DatePicker, TimePicker, DateTimePicker | Flatpickr | MIT | – |

### Scheduling

| Component | Library | License | Phase |
|---|---|---|---|
| Scheduler | FullCalendar (standard plugins) | MIT | 2 ✅ |
| Calendar | FullCalendar (`Scheduler` month view) / Flatpickr inline | MIT | 2 ✅ |
| Gantt | Frappe Gantt | MIT | – |

> FullCalendar's resource and timeline views are **premium** (commercial). Only the MIT standard plugins are bundled.

### Navigation & Layout

| Component | Library | License | Phase |
|---|---|---|---|
| Menu | MetisMenu / Bootstrap navbar & dropdowns | MIT | – |
| ContextMenu | Bootstrap dropdown positioned with Popper | MIT | – |
| Toolbar | Bootstrap `btn-toolbar` | MIT | – |
| PanelBar | Bootstrap Accordion / Collapse | MIT | – |
| TabStrip | Bootstrap Tabs | MIT | 2 |
| Drawer | Bootstrap Offcanvas | MIT | – |
| Splitter | Split.js | MIT | – |
| Layout Manager | Gridstack.js / GoldenLayout | MIT | – |

### Feedback & Dialogs

| Component | Library | License | Phase |
|---|---|---|---|
| Window / Dialog / Modal | Bootstrap Modal | MIT | 2 |
| Notification | Notyf | MIT | 2 |
| ProgressBar | Bootstrap Progress | MIT | – |

### Charts & Visualization

| Component | Library | License | Phase |
|---|---|---|---|
| Chart (line, bar, pie, doughnut, radar, scatter…) | Chart.js | MIT | 1 ✅ |
| Advanced charts | Plotly.js / D3.js | MIT / ISC | – |
| StockChart | TradingView Lightweight Charts | Apache 2.0 | – |
| Sparkline | Peity / Chart.js mini charts | MIT | – |
| Gauges (radial / linear) | JustGage / Gauge.js | MIT | – |
| BulletGraph | D3.js custom chart | ISC | – |
| Map | Leaflet / OpenLayers | BSD-2 | – |
| Diagram | Mermaid | MIT | – |
| OrgChart | D3 hierarchy layout | ISC | – |

### Editors & Forms

| Component | Library | License | Phase |
|---|---|---|---|
| Form validation | Parsley.js 🧩 | MIT | 1 ✅ |
| Upload | FilePond / Dropzone | MIT | 2 |
| Rich Text Editor | Quill / Editor.js | BSD-3 / Apache 2.0 | 2 |
| Markdown Editor | EasyMDE | MIT | – |
| File Manager | elFinder 🧩 | BSD-3 | 2 |

### Miscellaneous

| Component | Library | License | Phase |
|---|---|---|---|
| PDF Viewer | PDF.js | Apache 2.0 | – |
| Media Player | Plyr / Video.js | MIT / Apache 2.0 | – |
| Drag & Drop | SortableJS | MIT | – |
| Captcha | hCaptcha / reCAPTCHA / Cloudflare Turnstile (external services) | service terms | – |

---

## Optional Extras (not MIT / Apache / BSD)

These libraries are **not bundled** because of GPL, commercial or proprietary licenses. Applications may install them separately if they accept the terms.

| Component | Library | License |
|---|---|---|
| Gantt | dhtmlxGantt | GPL / commercial |
| Gantt | Bryntum Gantt | Commercial |
| Spreadsheet | Handsontable | Non-commercial license since v7 / commercial |
| OrgChart, Diagram | GoJS | Commercial |
| Rich Text Editor | TinyMCE 7, CKEditor 5 | GPL / commercial |
| PivotGrid | WebDataRocks | Proprietary (free tier) |
| Scheduler resources / timeline | FullCalendar Premium | Commercial |
| Menu | mmenu.js (v8+) | CC BY-NC 4.0 / commercial |

---

## Replaced Libraries

These were considered earlier and replaced because they are archived, unmaintained, tied to Bootstrap 3, or not usable in the core:

| Replaced | By | Reason |
|---|---|---|
| Luckysheet | Univer | Archived; Univer is its successor |
| SimpleMDE | EasyMDE | Unmaintained fork origin |
| Selectize.js | Tom Select | Abandoned; Tom Select is its successor |
| Select2 | Tom Select | Needs jQuery, slow release cadence |
| Pikaday, Bootstrap Timepicker | Flatpickr | Unmaintained / Bootstrap 3 era |
| Bootstrap Switch, Switchery | Bootstrap `form-switch` | Bootstrap 3 era / abandoned; native in Bootstrap 5 |
| Spectrum | Pickr | Unmaintained, needs jQuery |
| RichFilemanager | elFinder | Abandoned |
| Toastr | Notyf | Needs jQuery, unmaintained |
| Accordion.js, jQuery UI Toolbar | Bootstrap Accordion, `btn-toolbar` | Native in Bootstrap 5 (jQuery UI has no Toolbar widget) |
| jQuery contextMenu | Bootstrap dropdown + Popper | Avoid a jQuery-only dependency |
| mmenu.js | MetisMenu / Bootstrap | Non-commercial license since v8 |
| "dayflow" | FullCalendar | No identifiable maintained library under that name |

---

## Licensing Notes

- ✅ MIT, Apache 2.0, BSD and ISC libraries may be bundled in the core.
- ⚠️ GPL, commercial and proprietary libraries are excluded and must stay optional extras.
- Developers are responsible for compliance when enabling extras.
- Bundled libraries and their licenses are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
