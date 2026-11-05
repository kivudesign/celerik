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
│   │   └── Form/Field.php
│   ├── Data/
│   │   ├── DataSourceInterface.php
│   │   ├── DataRequest.php       # Parses DataTables + simple query formats
│   │   ├── DataResult.php        # {data, meta}
│   │   └── ArrayDataSource.php
│   ├── Support/
│   │   ├── Html.php              # Escaping HTML builder
│   │   └── Arr.php               # Dot-notation helpers
│   └── Adapters/
│       ├── Laravel/
│       │   ├── CelerikServiceProvider.php
│       │   ├── EloquentDataSource.php
│       │   └── View/
│       │       ├── CelerikComponent.php    # <x-celerik :component="..." />
│       │       └── AssetsComponent.php     # <x-celerik-assets />
│       └── Symfony/
│           ├── CelerikUiBundle.php
│           ├── CelerikTwigExtension.php    # celerik(), celerik_assets()
│           └── DoctrineDataSource.php
│
├── resources/                    # Frontend sources (not shipped in the Composer dist)
│   ├── js/
│   │   ├── index.js              # Bundle entry, registers initializers, window.Celerik
│   │   ├── core.js               # register / init / deepMerge
│   │   ├── grid.js   grid-config.js
│   │   ├── chart.js  chart-config.js
│   │   └── form.js   form-config.js
│   └── scss/
│       ├── _variables.scss
│       ├── _components.scss
│       └── themes/
│           └── bootstrap.scss    # material.scss, tailwind.scss in Phase 2
│
├── public/                       # Build output, shipped with the package
│   ├── celerik.js  celerik.js.map
│   └── celerik.css
│
├── tests/
│   ├── php/                      # PHPUnit (Celerik\Ui\Tests\)
│   │   ├── Components/  Data/  Support/
│   │   └── Adapters/Laravel/  Adapters/Symfony/
│   └── js/                       # Jest + axe-core
│       ├── fixtures/build.php    # Renders PHP components to HTML fixtures
│       ├── core.test.js  grid.test.js  chart.test.js  form.test.js  a11y.test.js
│       └── helpers.js  setup.js
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
