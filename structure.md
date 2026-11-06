# Structure

```text
celerik/
├── README.md                     # Overview, install, quick start
├── MasterProject.md              # Original project brief
├── Components.md                 # Telerik → open-source mapping, licenses, phases
├── AI.md                         # Notes for AI-assisted development
├── structure.md                  # This file
├── CONTRIBUTING.md  CODE_OF_CONDUCT.md  SECURITY.md  TESTING.md  ACCESSIBILITY.md  ROADMAP.md
├── LICENSE.md                    # MIT
├── THIRD_PARTY_NOTICES.md        # Licenses of bundled libraries
│
├── src/                          # PHP, namespace Celerik\Ui\
│   ├── Component.php             # Base class (make, options, render, toArray)
│   ├── ComponentRegistry.php     # Plugin registry
│   ├── Assets.php                # <link>/<script> tags for the bundle
│   ├── Components/
│   │   ├── Grid.php
│   │   ├── Grid/Column.php
│   │   ├── Chart.php
│   │   ├── Form.php
│   │   ├── Form/Field.php
│   │   ├── Scheduler.php
│   │   ├── Modal.php  Tabs.php  Notification.php
│   │   ├── FileManager.php
│   │   └── ThemeBuilder.php      # Theme editor: live preview, contrast report, export
│   ├── Data/
│   │   ├── DataSourceInterface.php
│   │   ├── DataRequest.php       # Parses DataTables + simple query formats
│   │   ├── DataResult.php        # {data, meta}
│   │   ├── ArrayDataSource.php
│   │   ├── Event.php             # Validated calendar event (FullCalendar format)
│   │   ├── DateRange.php         # Visible range from ?start=&end=
│   │   ├── EventSourceInterface.php  ArrayEventSource.php  EventResult.php
│   ├── Support/
│   │   ├── Html.php              # Escaping HTML builder
│   │   ├── Arr.php               # Dot-notation helpers
│   │   ├── Dates.php             # Strict ISO 8601 parsing
│   │   ├── EditorHtml.php        # Sanitizes rich text editor output
│   │   └── RawHtml.php           # Trusted markup for Modal/Tabs content (Html::raw())
│   ├── Theme/
│   │   ├── Theme.php             # Validated theme tokens (presets, fromJson, toCss)
│   │   ├── Stylesheet.php        # Writes the theme stylesheet
│   │   ├── Contrast.php  ContrastResult.php   # WCAG checks of theme/contrast.json
│   │   └── Console.php           # bin/celerik-theme
│   ├── FileManager/
│   │   ├── FileManagerConfig.php     # Locked-down elFinder volume
│   │   └── FileManagerConnector.php  # run($config, authorized: ...)
│   ├── Upload/
│   │   ├── UploadRules.php       # Accepted types, size, count (field + server)
│   │   ├── UploadHandler.php     # process / revert / claim / store / purge
│   │   ├── UploadedFile.php  StoredFile.php  UploadException.php
│   └── Adapters/
│       ├── Laravel/
│       │   ├── CelerikServiceProvider.php
│       │   ├── EloquentDataSource.php
│       │   ├── EloquentEventSource.php
│       │   └── View/
│       │       ├── CelerikComponent.php    # <x-celerik :component="..." />
│       │       └── AssetsComponent.php     # <x-celerik-assets />
│       └── Symfony/
│           ├── CelerikUiBundle.php
│           ├── CelerikTwigExtension.php    # celerik(), celerik_assets()
│           ├── DoctrineDataSource.php
│           └── DoctrineEventSource.php
│
├── bin/celerik-theme             # preset / check / build a theme (vendor/bin/celerik-theme)
├── theme/                        # theme.schema.json, contrast.json, presets/{bootstrap,material,tailwind}.json
│
├── resources/                    # Frontend sources (not shipped in the Composer dist)
│   ├── js/
│   │   ├── index.js              # Bundle entry, registers initializers, window.Celerik
│   │   ├── core.js               # register / init / deepMerge / prefersReducedMotion
│   │   ├── grid.js   grid-config.js
│   │   ├── chart.js  chart-config.js
│   │   ├── form.js   form-config.js
│   │   ├── scheduler.js  scheduler-config.js  scheduler-dialog.js  # event dialog (keyboard editing)
│   │   ├── editor.js  editor-config.js      # Quill, loaded only for forms with an editor
│   │   ├── upload.js  upload-config.js      # FilePond, loaded only for forms with uploads
│   │   ├── modal.js  tabs.js  bootstrap-global.js   # Bootstrap JS (peer dependency)
│   │   ├── notification.js  notification-config.js  # Notyf, Celerik.notify()
│   │   ├── filemanager.js  filemanager-config.js    # jQuery UI + the app's copy of elFinder
│   │   ├── themebuilder.js  theme-css.js            # ThemeBuilder; theme stylesheet + contrast in JS
│   │   └── jquery-global.js      # `jquery` → window.jQuery (peer dependency)
│   └── scss/
│       ├── _variables.scss
│       ├── _components.scss
│       └── themes/
│           ├── bootstrap.scss    # default look (part of celerik.css)
│           ├── material.scss     # → public/celerik-material.css
│           └── tailwind.scss     # → public/celerik-tailwind.css
│
├── public/                       # Build output, shipped with the package
│   ├── celerik.js                # ES module entry (~3 KB)
│   ├── celerik.css
│   ├── celerik-material.css  celerik-tailwind.css   # optional themes
│   └── chunks/                   # grid, chart, form, scheduler, editor, upload (JS + CSS): loaded on demand
│
├── tests/
│   ├── php/                      # PHPUnit (Celerik\Ui\Tests\)
│   │   ├── Components/  Data/  Support/  Upload/  FileManager/  Theme/
│   │   └── Adapters/Laravel/  Adapters/Symfony/
│   ├── js/                       # Jest + axe-core
│   │   ├── fixtures/build.php    # Renders PHP components and preset stylesheets to fixtures
│   │   ├── core.test.js  grid.test.js  chart.test.js  form.test.js  a11y.test.js
│   │   ├── scheduler.test.js  scheduler-dialog.test.js  scheduler-integration.test.js
│   │   ├── editor.test.js  upload.test.js  ui.test.js  notification.test.js
│   │   ├── filemanager.test.js  themes.test.js  presets.test.js
│   │   ├── theme-css.test.js  theme-export.test.js  themebuilder.test.js
│   │   └── helpers.js  setup.js  style-stub.js  css-helpers.js
│   └── browser/                  # Playwright + axe-core in a real browser (npm run test:a11y)
│       ├── server.php            # Test page with every component, served by PHP's built-in server
│       ├── theme-builder-page.php  custom-theme.json   # /theme-builder and the teal test theme
│       ├── a11y.spec.mjs  keyboard.spec.mjs  scheduler.spec.mjs  forced-colors.spec.mjs
│       ├── motion.spec.mjs  wcag22.spec.mjs  chart.spec.mjs  custom-theme.spec.mjs
│       ├── theme-builder.spec.mjs  theme-builder-a11y.spec.mjs
│       └── helpers.mjs
│
├── examples/
│   ├── plain/                    # php -S localhost:8000 examples/plain/index.php
│   ├── laravel/                  # Controller, routes, Blade views
│   └── symfony/                  # Controller, entity, repository, Twig templates
│
├── .github/
│   ├── ISSUE_TEMPLATE.md
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── workflows/ci.yml
│
├── composer.json  phpunit.xml.dist  phpcs.xml.dist
└── package.json  vite.config.mjs  jest.config.cjs  playwright.config.mjs  babel.config.cjs  eslint.config.mjs
```
