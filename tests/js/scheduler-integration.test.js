import axe from 'axe-core';
import { initScheduler } from '../../resources/js/scheduler.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('scheduler integration (FullCalendar)', () => {
  test('replaces the fallback list with a calendar showing the events', () => {
    const element = loadFixture('scheduler');
    expect(element.querySelector('.celerik-scheduler__fallback')).not.toBeNull();

    const calendar = initScheduler(element, readOptions(element));

    expect(element.querySelector('.celerik-scheduler__fallback')).toBeNull();
    expect(element.classList.contains('fc')).toBe(true);
    expect(calendar.view.type).toBe('listWeek');
    expect(calendar.getEvents().map((event) => event.title)).toEqual(['Kick-off', 'Design review', 'Holiday']);
    expect([...element.querySelectorAll('.fc-list-event-title')].map((td) => td.textContent))
      .toEqual(['Kick-off', 'Design review', 'Holiday']);
    calendar.destroy();
  });

  test.each(['listWeek', 'dayGridMonth', 'timeGridWeek'])('%s view has no WCAG 2.1 A/AA violations', async (view) => {
    const element = loadFixture('scheduler');
    const calendar = initScheduler(element, readOptions(element));
    calendar.changeView(view);

    const results = await axe.run(document.body, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
      rules: { 'color-contrast': { enabled: false } },
    });

    expect(results.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`)).toEqual([]);
    expect(element.querySelectorAll('.fc-icon[role="img"]:not([aria-hidden="true"])')).toHaveLength(0);
    calendar.destroy();
  }, 30000); // axe needs several seconds on the month and week grids in jsdom

  test('a user datesSet callback from PHP options still runs', () => {
    const element = loadFixture('scheduler');
    const datesSet = jest.fn();
    const calendar = initScheduler(element, { ...readOptions(element), options: { datesSet } });

    calendar.next();

    expect(datesSet).toHaveBeenCalledTimes(2);
    calendar.destroy();
  });
});
