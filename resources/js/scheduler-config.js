import { deepMerge } from './core.js';

const DEFAULT_VIEWS = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];

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
    headerToolbar: { start: 'prev,next today', center: 'title', end: views.join(',') },
    timeZone: options.timeZone ?? 'local',
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
    locale: options.locale,
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
// Before creating or deleting, a cancelable event is dispatched on the element
// (`celerik:scheduler:select`, `celerik:scheduler:delete`): call preventDefault() to
// replace the built-in prompt/confirm with your own UI (e.g. a modal form).
export function createChangeHandlers(element, options, deps = {}) {
  const request = deps.request ?? ((url, payload) => sendChange(url, payload, options.csrf));
  const ask = deps.prompt ?? ((message) => window.prompt(message));
  const confirmDelete = deps.confirm ?? ((message) => window.confirm(message));
  const labels = {
    newEvent: 'Title of the new event',
    confirmDelete: 'Delete "{title}"?',
    ...options.labels,
  };

  const emit = (name, detail, cancelable = false) => element.dispatchEvent(
    new CustomEvent(`celerik:scheduler:${name}`, { bubbles: true, cancelable, detail }),
  );
  const fail = (action, detail) => emit('error', { action, ...detail });

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
      const payload = { ...range, title };
      const result = await request(options.createUrl, payload);
      if (result.ok) {
        calendar.addEvent(result.event ?? payload);
      } else {
        fail('create', { event: payload });
      }
    };
  }

  if (options.deleteUrl) {
    handlers.eventClick = async (info) => {
      if (info.event.url) {
        return; // events with a URL are links
      }
      info.jsEvent?.preventDefault();
      const payload = eventPayload(info.event);
      if (!emit('delete', { event: payload }, true)) {
        return;
      }
      if (!confirmDelete(labels.confirmDelete.replace('{title}', info.event.title))) {
        return;
      }
      const result = await request(options.deleteUrl, payload);
      if (result.ok) {
        info.event.remove();
      } else {
        fail('delete', { event: payload });
      }
    };
  }

  return handlers;
}
