import { deepMerge } from './core.js';
import { openEventDialog } from './scheduler-dialog.js';

const DEFAULT_VIEWS = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];

// Texts of the built-in prompts and of the event dialog; Scheduler::labels() overrides them.
export const DEFAULT_LABELS = {
  newEvent: 'Title of the new event',
  confirmDelete: 'Delete "{title}"?',
  createEvent: 'New event',
  editEvent: 'Edit "{title}"',
  title: 'Title',
  allDay: 'All day',
  start: 'Start',
  end: 'End',
  save: 'Save',
  cancel: 'Cancel',
  delete: 'Delete',
  titleRequired: 'Enter a title.',
  startRequired: 'Enter a start date.',
  endBeforeStart: 'The end must be after the start.',
};

// Toolbar button that opens the event dialog for a new event: selecting days with the
// mouse is not possible from the keyboard.
export const CREATE_BUTTON = 'celerikCreate';

// Celerik endpoints answer {data, meta}; FullCalendar wants a plain array of events.
export function toFullCalendarEvents(payload) {
  if (Array.isArray(payload)) {
    return payload;
  }
  return Array.isArray(payload?.data) ? payload.data : [];
}

export function buildSchedulerConfig(options) {
  const views = options.views ?? DEFAULT_VIEWS;
  const config = {
    initialView: options.view ?? 'dayGridMonth',
    headerToolbar: {
      start: options.createUrl ? `prev,next today ${CREATE_BUTTON}` : 'prev,next today',
      center: 'title',
      end: views.join(','),
    },
    timeZone: options.timeZone ?? 'local',
    // FullCalendar falls back to the first of the bundled locales (Afrikaans) when none is set.
    locale: options.locale ?? 'en',
    height: 'auto',
    editable: Boolean(options.updateUrl),
    selectable: Boolean(options.createUrl),
    selectMirror: Boolean(options.createUrl),
    events: options.url
      ? { url: options.url, success: toFullCalendarEvents }
      : (options.events ?? []),
  };

  const optional = {
    initialDate: options.date,
    firstDay: options.firstDay,
    businessHours: options.businessHours,
    slotMinTime: options.slotMinTime,
    slotMaxTime: options.slotMaxTime,
  };
  for (const [key, value] of Object.entries(optional)) {
    if (value !== undefined && value !== null) {
      config[key] = value;
    }
  }

  return deepMerge(config, options.options);
}

// What the server receives for create, update and delete.
export function eventPayload(event) {
  return {
    id: event.id === '' ? null : event.id,
    title: event.title,
    start: event.startStr,
    end: event.endStr || null,
    allDay: event.allDay,
  };
}

function addDays(date, days) {
  const value = new Date(`${date.slice(0, 10)}T00:00:00Z`);
  value.setUTCDate(value.getUTCDate() + days);
  return value.toISOString().slice(0, 10);
}

// Dialog values for an event. All-day ends are exclusive in FullCalendar ("until the 7th"
// for an event on the 6th): the dialog shows the last day instead.
export function dialogValues(event) {
  const end = event.endStr || '';
  return {
    title: event.title,
    allDay: event.allDay,
    start: event.startStr,
    end: event.allDay && end ? addDays(end, -1) : end,
  };
}

// The dates to give FullCalendar back: the inverse of dialogValues().
export function datesFromDialog(values) {
  if (values.allDay) {
    const start = values.start.slice(0, 10);
    const last = values.end ? values.end.slice(0, 10) : null;
    return { start, end: last && last > start ? addDays(last, 1) : null, allDay: true };
  }
  return { start: values.start, end: values.end || null, allDay: false };
}

// The dialog's error message, or null when the values can be saved.
export function validateDialog(values, labels, { withTitle = false } = {}) {
  if (withTitle && !values.title) {
    return labels.titleRequired;
  }
  if (!values.start) {
    return labels.startRequired;
  }
  if (values.end && values.end < values.start) {
    return labels.endBeforeStart;
  }
  return null;
}

// POSTs a change as JSON with the CSRF header. Resolves to {ok, event}, never rejects.
export async function sendChange(url, payload, csrf) {
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
  if (csrf?.header && csrf?.token) {
    headers[csrf.header] = csrf.token;
  }
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers,
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const body = await response.json().catch(() => null);
    return { ok: response.ok, event: body?.data ?? null };
  } catch {
    return { ok: false, event: null };
  }
}

// FullCalendar callbacks for the endpoints that are configured. Every change is sent to
// the server first; the calendar keeps it only if the server accepts it.
//
// With updateUrl(), Enter, Space or a click on an event opens a dialog to change its dates
// (and to delete it, with deleteUrl()). With createUrl(), a "New event" toolbar button opens
// the dialog for a new event. Dragging and selecting days still work, but are never the
// only way (WCAG 2.1.1, 2.5.7).
//
// Before creating, editing or deleting, a cancelable event is dispatched on the element
// (`celerik:scheduler:select`, `celerik:scheduler:edit`, `celerik:scheduler:delete`): call
// preventDefault() to replace the built-in prompt, dialog or confirm with your own UI.
export function createChangeHandlers(element, options, deps = {}) {
  const request = deps.request ?? ((url, payload) => sendChange(url, payload, options.csrf));
  const ask = deps.prompt ?? ((message) => window.prompt(message));
  const confirmDelete = deps.confirm ?? ((message) => window.confirm(message));
  const dialog = deps.dialog ?? openEventDialog;
  const focusEvent = deps.focusEvent ?? (() => {});
  const labels = { ...DEFAULT_LABELS, ...options.labels };

  const emit = (name, detail, cancelable = false) => element.dispatchEvent(
    new CustomEvent(`celerik:scheduler:${name}`, { bubbles: true, cancelable, detail }),
  );
  const fail = (action, detail) => emit('error', { action, ...detail });

  const create = async (calendar, payload) => {
    const result = await request(options.createUrl, payload);
    if (result.ok) {
      focusEvent(calendar.addEvent(result.event ?? payload)?.id);
    } else {
      fail('create', { event: payload });
    }
  };

  const remove = async (event) => {
    const payload = eventPayload(event);
    if (!emit('delete', { event: payload }, true)) {
      return;
    }
    if (!confirmDelete(labels.confirmDelete.replace('{title}', event.title))) {
      return;
    }
    const result = await request(options.deleteUrl, payload);
    if (result.ok) {
      event.remove();
    } else {
      fail('delete', { event: payload });
    }
  };

  const edit = async (event) => {
    if (!emit('edit', { event: eventPayload(event) }, true)) {
      return;
    }
    const result = await dialog({
      heading: labels.editEvent.replace('{title}', event.title),
      values: dialogValues(event),
      labels,
      canDelete: Boolean(options.deleteUrl),
      validate: (values) => validateDialog(values, labels),
    });
    if (result?.action === 'delete') {
      await remove(event);
      return;
    }
    if (result?.action !== 'save') {
      return;
    }
    const before = { start: event.start, end: event.end, allDay: event.allDay };
    const dates = datesFromDialog(result);
    event.setDates(dates.start, dates.end, { allDay: dates.allDay });
    const payload = eventPayload(event);
    const response = await request(options.updateUrl, payload);
    if (!response.ok) {
      event.setDates(before.start, before.end, { allDay: before.allDay });
      fail('update', { event: payload });
    }
    focusEvent(event.id);
  };

  const handlers = {};

  if (options.updateUrl) {
    const update = async (info) => {
      const payload = eventPayload(info.event);
      const result = await request(options.updateUrl, payload);
      if (!result.ok) {
        info.revert();
        fail('update', { event: payload });
      }
    };
    handlers.eventDrop = update;
    handlers.eventResize = update;
  }

  if (options.createUrl) {
    handlers.select = async (info) => {
      const calendar = info.view.calendar;
      calendar.unselect();
      const range = { start: info.startStr, end: info.endStr, allDay: info.allDay };
      if (!emit('select', range, true)) {
        return;
      }
      const title = ask(labels.newEvent)?.trim();
      if (!title) {
        return;
      }
      await create(calendar, { ...range, title });
    };

    handlers.customButtons = {
      [CREATE_BUTTON]: {
        text: labels.createEvent,
        click: async () => {
          const calendar = deps.calendar?.();
          if (!calendar) {
            return;
          }
          const day = calendar.formatIso(calendar.getDate()).slice(0, 10);
          const range = { start: `${day}T09:00`, end: `${day}T10:00`, allDay: false };
          if (!emit('select', range, true)) {
            return;
          }
          const result = await dialog({
            heading: labels.createEvent,
            values: { title: '', ...range },
            labels,
            withTitle: true,
            validate: (values) => validateDialog(values, labels, { withTitle: true }),
          });
          if (result?.action === 'save') {
            await create(calendar, { title: result.title, ...datesFromDialog(result) });
          }
        },
      },
    };
  }

  if (options.updateUrl || options.deleteUrl) {
    handlers.eventClick = async (info) => {
      if (info.event.url) {
        return; // events with a URL are links
      }
      info.jsEvent?.preventDefault();
      await (options.updateUrl ? edit(info.event) : remove(info.event));
    };
  }

  return handlers;
}
