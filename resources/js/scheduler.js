import { Calendar } from '@fullcalendar/core';
import allLocales from '@fullcalendar/core/locales-all';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import { buildSchedulerConfig, createChangeHandlers } from './scheduler-config.js';

// FullCalendar's toolbar icons are <span role="img"> without a name. The buttons around
// them are already named by their title ("Previous week"), so hide the icons from
// assistive technology (WCAG 1.1.1).
function hideDecorativeIcons(element) {
  for (const icon of element.querySelectorAll('.fc-icon[role="img"]:not([aria-hidden])')) {
    icon.setAttribute('aria-hidden', 'true');
  }
}

// Month view cells are labelled by their day number link ("November 3, 2026"), which
// hides the events inside them from the cell's name (WCAG 2.5.3). The link keeps its label.
function unlabelDayCell({ el }) {
  el.removeAttribute('aria-labelledby');
}

// After a change FullCalendar renders the event again, sometimes after the request has
// finished: focus the element it renders next (or the current one), so keyboard users
// do not land at the top of the page.
function eventFocuser(element) {
  let pending = null;
  const find = (id) => [...element.querySelectorAll('[data-celerik-event-id]')]
    .find((el) => el.dataset.celerikEventId === id);

  return {
    focus(id) {
      if (id === undefined || id === null || id === '') {
        return;
      }
      pending = String(id);
      setTimeout(() => {
        if (pending === String(id)) {
          find(pending)?.focus();
        }
      }, 50);
      setTimeout(() => {
        pending = null;
      }, 1000);
    },
    mounted(el, id) {
      if (pending !== null && pending === id) {
        setTimeout(() => el.isConnected && el.focus(), 0);
      }
    },
  };
}

export function initScheduler(element, options) {
  // Replace the server-rendered fallback list with the calendar.
  element.replaceChildren();

  const config = buildSchedulerConfig(options);
  const { datesSet, dayCellDidMount, eventDidMount } = config;
  let calendar = null;
  const focuser = eventFocuser(element);
  const { customButtons, ...handlers } = createChangeHandlers(element, options, {
    calendar: () => calendar,
    focusEvent: focuser.focus,
  });

  calendar = new Calendar(element, {
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    locales: allLocales,
    ...config,
    ...handlers,
    customButtons: { ...config.customButtons, ...customButtons },
    datesSet(info) {
      hideDecorativeIcons(element);
      datesSet?.(info);
    },
    dayCellDidMount(info) {
      unlabelDayCell(info);
      dayCellDidMount?.(info);
    },
    eventDidMount(info) {
      info.el.dataset.celerikEventId = info.event.id;
      focuser.mounted(info.el, info.event.id);
      eventDidMount?.(info);
    },
  });
  calendar.render();
  hideDecorativeIcons(element);

  return calendar;
}
