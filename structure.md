# Structure

```text
celerik/
├── README.md                     # Overview, install, quick start
├── MasterProject.md              # Original project brief
├── Components.md                 # Telerik → open-source mapping, licenses, phases
├── AI.md                         # Notes for AI-assisted development
├── structure.md                  # This file
├── CONTRIBUTING.md  CODE_OF_CONDUCT.md  SECURITY.md  TESTING.md  ROADMAP.md
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
│   │   └── Scheduler.php
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
├── resources/                    # Frontend sources (not shipped in the Composer dist)
│   ├── js/
│   │   ├── index.js              # Bundle entry, registers initializers, window.Celerik
│   │   ├── core.js               # register / init / deepMerge
│   │   ├── grid.js   grid-config.js
│   │   ├── chart.js  chart-config.js
│   │   ├── form.js   form-config.js
│   │   ├── scheduler.js  scheduler-config.js
│   │   ├── editor.js  editor-config.js      # Quill, loaded only for forms with an editor
│   │   ├── upload.js  upload-config.js      # FilePond, loaded only for forms with uploads
│   │   ├── modal.js  tabs.js  bootstrap-global.js   # Bootstrap JS (peer dependency)
│   │   ├── notification.js  notification-config.js  # Notyf, Celerik.notify()
│   │   └── jquery-global.js      # `jquery` → window.jQuery (peer dependency)
│   └── scss/
│       ├── _variables.scss
│       ├── _components.scss
│       └── themes/
│           └── bootstrap.scss    # material.scss, tailwind.scss in Phase 2
│
├── public/                       # Build output, shipped with the package
│   ├── celerik.js                # ES module entry (~3 KB)
│   ├── celerik.css
│   └── chunks/                   # grid, chart, form, scheduler, editor, upload (JS + CSS): loaded on demand
│
├── tests/
│   ├── php/                      # PHPUnit (Celerik\Ui\Tests\)
│   │   ├── Components/  Data/  Support/  Upload/
│   │   └── Adapters/Laravel/  Adapters/Symfony/
│   └── js/                       # Jest + axe-core
│       ├── fixtures/build.php    # Renders PHP components to HTML fixtures
│       ├── core.test.js  grid.test.js  chart.test.js  form.test.js  a11y.test.js
│       ├── scheduler.test.js  scheduler-integration.test.js  editor.test.js  upload.test.js
│       └── helpers.js  setup.js  style-stub.js
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
└── package.json  vite.config.mjs  jest.config.cjs  babel.config.cjs  eslint.config.mjs
```
