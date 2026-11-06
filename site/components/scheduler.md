# Scheduler

A calendar on [FullCalendar 6](https://fullcalendar.io/) (MIT plugins): month, week, day and list views, and events created, moved, resized and deleted through your endpoints, by mouse or keyboard.

<Demo name="scheduler" :min-height="640" />

<<< @/demos/scheduler.php

## Options

| Method | Use |
|---|---|
| `title($title)` | The calendar region's accessible name. |
| `view('month' \| 'week' \| 'day' \| 'list')`, `views(...)` | The first view, and the views offered in the toolbar. |
| `date('2026-11-05')`, `firstDay(1)` | The first date shown, and the first day of the week (0 = Sunday). |
| `timeZone('Europe/Paris')`, `locale('fr')` | `local` (the browser's, default), `UTC` or an IANA name; and the language of dates and the toolbar. |
| `businessHours('08:00', '18:00', [1, 2, 3, 4, 5])`, `slotTimes('07:00', '20:00')` | Working hours, and the hours shown in the week and day views. |
| `events([...])` | Embedded events, also listed for browsers without JavaScript. |
| `url($url)` | Loads the events of each visible range from an endpoint ([Data endpoints](../guide/data#scheduler)). |
| `createUrl()`, `updateUrl()`, `deleteUrl()`, `csrf($token)` | Make the calendar editable. |
| `labels([...])` | Translate the prompts and the event dialog. |

Events are `id`, `title`, `start`, `end`, `allDay`, `color` and `url`. A date without a time (`2026-11-12`) is an all-day event. A `color` gets a readable text color automatically.

## Editing

With `updateUrl()`, users drag and resize events, or press Enter (or Space, or click) on an event to change its dates in a dialog, which also has a Delete button with `deleteUrl()`. With `createUrl()`, they select days, or use the **New event** toolbar button. Every change is posted as JSON and rolled back if the server refuses it.

Replace the built-in prompt, dialog or confirmation with your own UI by cancelling the event:

```js
calendar.addEventListener('celerik:scheduler:edit', (event) => {
  event.preventDefault();
  openMyDialog(event.detail.event); // {id, title, start, end, allDay}
});
calendar.addEventListener('celerik:scheduler:error', (event) => {
  Celerik.notify('error', `Could not ${event.detail.action} the event.`);
});
```

Events: `celerik:scheduler:select` (cancelable), `celerik:scheduler:edit` (cancelable), `celerik:scheduler:delete` (cancelable) and `celerik:scheduler:error`.

### Labels

```php
Scheduler::make('agenda')->locale('fr')->labels([
    'createEvent' => 'Nouvel événement',
    'editEvent' => 'Modifier « {title} »',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'delete' => 'Supprimer',
    'confirmDelete' => 'Supprimer « {title} » ?',
]);
```

Keys: `newEvent`, `confirmDelete`, `createEvent`, `editEvent`, `title`, `allDay`, `start`, `end`, `save`, `cancel`, `delete`, `titleRequired`, `startRequired`, `endBeforeStart`.

## Accessibility

- A named region; events are links reachable with Tab.
- Dragging and selecting days are never the only way: the event dialog and the New event button work from the keyboard (WCAG 2.1.1, 2.5.7). The dialog keeps focus, closes with Escape, announces errors on its fields and returns focus to the event.
- Without JavaScript, the events are listed with `<time>` elements.
