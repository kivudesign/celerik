# How it works

## PHP renders, JavaScript enhances

Every component extends `Celerik\Ui\Component`. Its `render()` returns escaped HTML, and the root element carries two attributes:

```html
<table id="users" class="table table-striped table-hover w-100 celerik-grid"
       data-celerik="grid"
       data-celerik-options="{&quot;pageSize&quot;:5, …}">
```

- `data-celerik` names the component.
- `data-celerik-options` holds its options as JSON, built by the component's `toArray()`.

`celerik.js` finds these elements, loads the matching library on demand (DataTables for a grid, FullCalendar for a scheduler…) and starts it. A page without a scheduler never downloads FullCalendar. No inline scripts are written, so a strict [Content Security Policy](./csp) works.

Without JavaScript, components still make sense: a grid is a table, a chart has a data table, a scheduler lists its events, a form posts to its action.

## Content: text or HTML

Strings are always escaped. To insert markup, pass a `Stringable`: another component, or `Html::raw()` for HTML you trust.

```php
use Celerik\Ui\Support\Html;

Tabs::make('help')
    ->tab('text', 'Text', 'Shown as <b>text</b>')                 // escaped
    ->tab('html', 'HTML', Html::raw('<p>Trusted <b>HTML</b></p>')) // inserted
    ->tab('form', 'Form', $form);                                  // a component
```

## Library options

Each component maps its PHP options to the library. To set anything else, pass raw library options; they are applied last, so they win:

```php
Grid::make('users')->options(['scrollY' => '50vh', 'stateSave' => true]);
Chart::make('sales')->options(['plugins' => ['legend' => ['position' => 'bottom']]]);
```

## Events

When a component is ready, its element fires `celerik:ready` (it bubbles). The library instance is in `event.detail.instance`, and stays on `element.celerik`:

```js
document.addEventListener('celerik:ready', (event) => {
  if (event.target.id === 'users') {
    const table = event.detail.instance; // the DataTables API
  }
});
```

Components that change data fire their own events, for example `celerik:scheduler:select`, `celerik:scheduler:edit`, `celerik:scheduler:delete` and `celerik:scheduler:error` (see the [Scheduler](../components/scheduler#editing)).

## The JavaScript API

`window.Celerik` (also the default export of `celerik.js`):

| Function | Use |
|---|---|
| `Celerik.init(root)` | Start components added to the page later, e.g. after an AJAX update. Components already started are skipped. |
| `Celerik.notify(type, message)` | Show a toast (`success`, `error`, `info`, `warning`); the message is always text. |
| `Celerik.register(name, initializer)` | Add your own component: `initializer(element, options)` returns its instance (or a promise of it). |

```js
container.innerHTML = htmlFromServer; // markup rendered by PHP
await Celerik.init(container);
Celerik.notify('success', 'Saved.');
```

## Plugins

A plugin pairs a PHP component (registered in `ComponentRegistry`) with a JavaScript initializer of the same name:

```php
final class Rating extends Component
{
    public static function name(): string { return 'rating'; }
    public function toArray(): array { return ['max' => 5]; }
    public function render(): string
    {
        return Html::tag('div', $this->rootAttributes(['my-rating']), '');
    }
}
```

```js
Celerik.register('rating', (element, options) => new MyRating(element, options.max));
```
