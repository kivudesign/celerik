# Components

Every component is a PHP class with a fluent API. It renders accessible Bootstrap 5 markup, and the JavaScript bundle enhances it on the client. The demos below are the real components, rendered by PHP when this site is built. Switch the look in a demo's toolbar; the site's dark mode applies to the demos too.

| Component | PHP class | Library |
|---|---|---|
| Grid | `Celerik\Ui\Components\Grid` | DataTables 2 |
| Chart | `Celerik\Ui\Components\Chart` | Chart.js 4 |
| Form and fields | `Celerik\Ui\Components\Form`, `Form\Field` | Parsley.js, Inputmask |
| Rich text editor | `Field::editor()` | Quill 2 |
| File upload | `Field::file()` | FilePond 4 |
| Scheduler | `Celerik\Ui\Components\Scheduler` | FullCalendar 6 |
| Modal | `Celerik\Ui\Components\Modal` | Bootstrap 5 |
| Tabs | `Celerik\Ui\Components\Tabs` | Bootstrap 5 |
| Notification | `Celerik\Ui\Components\Notification` | Notyf 3 |
| File manager | `Celerik\Ui\Components\FileManager` | elFinder 2.1 |
| Theme builder | `Celerik\Ui\Components\ThemeBuilder` | – |

## Grid

<Demo name="grid" :min-height="420" />

## Chart

<Demo name="chart" :min-height="360" />

## Form

<Demo name="form" :min-height="560" />

## Tabs

<Demo name="tabs" />

## Modal

<Demo name="modal" :min-height="320" />

## Notification

<Demo name="notification" :min-height="220" />

## Scheduler

<Demo name="scheduler" :min-height="640" />
