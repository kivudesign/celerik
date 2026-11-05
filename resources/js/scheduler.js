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

export function initScheduler(element, options) {
  // Replace the server-rendered fallback list with the calendar.
  element.replaceChildren();

  const config = buildSchedulerConfig(options);
  const datesSet = config.datesSet;

  const calendar = new Calendar(element, {
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    locales: allLocales,
    ...config,
    ...createChangeHandlers(element, options),
    datesSet(info) {
      hideDecorativeIcons(element);
      datesSet?.(info);
    },
  });
  calendar.render();
  hideDecorativeIcons(element);

  return calendar;
}
