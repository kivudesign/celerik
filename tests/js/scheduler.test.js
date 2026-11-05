import {
  buildSchedulerConfig,
  CREATE_BUTTON,
  createChangeHandlers,
  datesFromDialog,
  dialogValues,
  eventPayload,
  sendChange,
  toFullCalendarEvents,
  validateDialog,
  DEFAULT_LABELS,
} from '../../resources/js/scheduler-config.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

const fcEvent = (props = {}) => ({
  id: '7',
  title: 'Kick-off',
  startStr: '2026-11-05T09:00:00Z',
  endStr: '2026-11-05T10:00:00Z',
  allDay: false,
  url: '',
  remove: jest.fn(),
  ...props,
});

describe('scheduler config', () => {
  test('maps the PHP options to FullCalendar', () => {
    const element = loadFixture('scheduler');
    const config = buildSchedulerConfig(readOptions(element));

    expect(config).toMatchObject({
      initialView: 'listWeek',
      initialDate: '2026-11-05',
      timeZone: 'UTC',
      firstDay: 1,
      editable: true,
      selectable: false,
      headerToolbar: { end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
    });
    expect(config.events.map((e) => e.title)).toEqual(['Kick-off', 'Design review', 'Holiday']);
    expect(config.locale).toBe('en'); // not FullCalendar's first bundled locale (Afrikaans)
  });

  test('remote events unwrap the {data, meta} envelope', () => {
    const config = buildSchedulerConfig({ url: '/events' });

    expect(config.events.url).toBe('/events');
    expect(config.events.success({ data: [{ title: 'a' }], meta: {} })).toEqual([{ title: 'a' }]);
    expect(toFullCalendarEvents([{ title: 'b' }])).toEqual([{ title: 'b' }]);
    expect(toFullCalendarEvents({ unexpected: true })).toEqual([]);
  });

  test('raw options from PHP override generated ones', () => {
    expect(buildSchedulerConfig({ options: { height: 600, nowIndicator: true } }))
      .toMatchObject({ height: 600, nowIndicator: true });
  });

  test('eventPayload sends what the server needs', () => {
    expect(eventPayload(fcEvent({ id: '', endStr: '' }))).toEqual({
      id: null, title: 'Kick-off', start: '2026-11-05T09:00:00Z', end: null, allDay: false,
    });
  });
});

describe('sendChange', () => {
  afterEach(() => { delete global.fetch; });

  test('POSTs JSON with the CSRF header and returns the saved event', async () => {
    global.fetch = jest.fn().mockResolvedValue({ ok: true, json: async () => ({ data: { id: 9 } }) });

    const result = await sendChange('/events', { title: 'x' }, { header: 'X-CSRF-TOKEN', token: 'tok' });

    expect(result).toEqual({ ok: true, event: { id: 9 } });
    const [url, init] = global.fetch.mock.calls[0];
    expect(url).toBe('/events');
    expect(init).toMatchObject({ method: 'POST', credentials: 'same-origin', body: '{"title":"x"}' });
    expect(init.headers).toMatchObject({ 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'tok' });
  });

  test('never rejects: HTTP and network errors resolve to ok=false', async () => {
    global.fetch = jest.fn().mockResolvedValue({ ok: false, json: async () => { throw new Error('not json'); } });
    expect(await sendChange('/events', {})).toEqual({ ok: false, event: null });

    global.fetch = jest.fn().mockRejectedValue(new TypeError('offline'));
    expect(await sendChange('/events', {})).toEqual({ ok: false, event: null });
  });
});

describe('change handlers', () => {
  let element;
  beforeEach(() => {
    document.body.innerHTML = '<div id="cal"></div>';
    element = document.getElementById('cal');
  });

  test('only registers handlers for the configured endpoints', () => {
    expect(Object.keys(createChangeHandlers(element, {}))).toEqual([]);
    expect(Object.keys(createChangeHandlers(element, { updateUrl: '/u' })))
      .toEqual(['eventDrop', 'eventResize', 'eventClick']);
    expect(Object.keys(createChangeHandlers(element, { createUrl: '/c', deleteUrl: '/d' })))
      .toEqual(['select', 'customButtons', 'eventClick']);
  });

  test('a rejected move is rolled back and reported', async () => {
    const request = jest.fn().mockResolvedValue({ ok: false });
    const errors = jest.fn();
    element.addEventListener('celerik:scheduler:error', errors);
    const info = { event: fcEvent(), revert: jest.fn() };

    await createChangeHandlers(element, { updateUrl: '/u' }, { request }).eventDrop(info);

    expect(request).toHaveBeenCalledWith('/u', expect.objectContaining({ id: '7', start: '2026-11-05T09:00:00Z' }));
    expect(info.revert).toHaveBeenCalled();
    expect(errors.mock.calls[0][0].detail).toMatchObject({ action: 'update', event: { id: '7' } });
  });

  test('an accepted resize is kept', async () => {
    const info = { event: fcEvent(), revert: jest.fn() };

    await createChangeHandlers(element, { updateUrl: '/u' }, { request: async () => ({ ok: true }) }).eventResize(info);

    expect(info.revert).not.toHaveBeenCalled();
  });

  test('selecting a range creates an event with the saved data from the server', async () => {
    const calendar = { unselect: jest.fn(), addEvent: jest.fn() };
    const request = jest.fn().mockResolvedValue({ ok: true, event: { id: 12, title: 'Retro' } });
    const handlers = createChangeHandlers(element, { createUrl: '/c' }, { request, prompt: () => '  Retro  ' });

    await handlers.select({ startStr: '2026-11-05T09:00', endStr: '2026-11-05T10:00', allDay: false, view: { calendar } });

    expect(calendar.unselect).toHaveBeenCalled();
    expect(request).toHaveBeenCalledWith('/c', {
      start: '2026-11-05T09:00', end: '2026-11-05T10:00', allDay: false, title: 'Retro',
    });
    expect(calendar.addEvent).toHaveBeenCalledWith({ id: 12, title: 'Retro' });
  });

  test('creation stops on an empty title or when the select event is cancelled', async () => {
    const calendar = { unselect: jest.fn(), addEvent: jest.fn() };
    const request = jest.fn();
    const info = { startStr: 'a', endStr: 'b', allDay: true, view: { calendar } };

    await createChangeHandlers(element, { createUrl: '/c' }, { request, prompt: () => '   ' }).select(info);

    const prompt = jest.fn();
    element.addEventListener('celerik:scheduler:select', (event) => event.preventDefault());
    await createChangeHandlers(element, { createUrl: '/c' }, { request, prompt }).select(info);

    expect(prompt).not.toHaveBeenCalled();
    expect(request).not.toHaveBeenCalled();
    expect(calendar.addEvent).not.toHaveBeenCalled();
  });

  test('clicking deletes after confirmation; links and refusals are left alone', async () => {
    const request = jest.fn().mockResolvedValue({ ok: true });
    const confirm = jest.fn().mockReturnValueOnce(false).mockReturnValueOnce(true);
    const handlers = createChangeHandlers(element, { deleteUrl: '/d', labels: { confirmDelete: 'Remove {title}?' } }, { request, confirm });

    const link = fcEvent({ url: '/meetings/7' });
    await handlers.eventClick({ event: link, jsEvent: { preventDefault: jest.fn() } });
    expect(confirm).not.toHaveBeenCalled();

    const event = fcEvent();
    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(event.remove).not.toHaveBeenCalled();

    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(confirm).toHaveBeenLastCalledWith('Remove Kick-off?');
    expect(request).toHaveBeenCalledWith('/d', expect.objectContaining({ id: '7' }));
    expect(event.remove).toHaveBeenCalled();
  });

  const editable = (props = {}) => fcEvent({ start: 'S', end: 'E', setDates: jest.fn(), ...props });

  test('with updateUrl, clicking (or Enter on) an event opens the dialog and saves new dates', async () => {
    const request = jest.fn().mockResolvedValue({ ok: true });
    const focusEvent = jest.fn();
    const dialog = jest.fn().mockResolvedValue({
      action: 'save', title: 'Kick-off', start: '2026-11-06T10:00', end: '2026-11-06T11:30', allDay: false,
    });
    const event = editable();
    event.setDates.mockImplementation((start, end) => Object.assign(event, { startStr: start, endStr: end }));

    const handlers = createChangeHandlers(element, { updateUrl: '/u' }, { request, dialog, focusEvent });
    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });

    expect(dialog).toHaveBeenCalledWith(expect.objectContaining({ heading: 'Edit "Kick-off"', canDelete: false }));
    expect(event.setDates).toHaveBeenCalledWith('2026-11-06T10:00', '2026-11-06T11:30', { allDay: false });
    expect(request).toHaveBeenCalledWith('/u', expect.objectContaining({ id: '7', start: '2026-11-06T10:00' }));
    expect(focusEvent).toHaveBeenCalledWith('7');
  });

  test('a refused dialog change is rolled back and reported; cancel changes nothing', async () => {
    const request = jest.fn().mockResolvedValue({ ok: false });
    const errors = jest.fn();
    element.addEventListener('celerik:scheduler:error', errors);
    const dialog = jest.fn()
      .mockResolvedValueOnce({ action: 'save', start: '2026-11-06', end: '', allDay: true })
      .mockResolvedValueOnce(null);
    const event = editable();
    const handlers = createChangeHandlers(element, { updateUrl: '/u' }, { request, dialog });

    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(event.setDates).toHaveBeenLastCalledWith('S', 'E', { allDay: false });
    expect(errors.mock.calls[0][0].detail.action).toBe('update');

    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(request).toHaveBeenCalledTimes(1);
  });

  test('the dialog offers Delete with deleteUrl, and the edit event can replace it', async () => {
    const request = jest.fn().mockResolvedValue({ ok: true });
    const confirm = jest.fn(() => true);
    const dialog = jest.fn().mockResolvedValue({ action: 'delete' });
    const event = editable();
    const handlers = createChangeHandlers(element, { updateUrl: '/u', deleteUrl: '/d' }, { request, confirm, dialog });

    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(dialog).toHaveBeenCalledWith(expect.objectContaining({ canDelete: true }));
    expect(request).toHaveBeenCalledWith('/d', expect.objectContaining({ id: '7' }));
    expect(event.remove).toHaveBeenCalled();

    element.addEventListener('celerik:scheduler:edit', (e) => e.preventDefault());
    dialog.mockClear();
    await handlers.eventClick({ event, jsEvent: { preventDefault: jest.fn() } });
    expect(dialog).not.toHaveBeenCalled();
  });

  test('the New event button creates an event from the dialog', async () => {
    const request = jest.fn().mockResolvedValue({ ok: true, event: { id: '9', title: 'Retro' } });
    const calendar = {
      getDate: () => new Date('2026-11-05T00:00:00Z'),
      formatIso: () => '2026-11-05T00:00:00Z',
      addEvent: jest.fn((data) => data),
    };
    const focusEvent = jest.fn();
    const dialog = jest.fn().mockResolvedValue({
      action: 'save', title: 'Retro', start: '2026-11-05T15:00', end: '2026-11-05T16:00', allDay: false,
    });
    const handlers = createChangeHandlers(
      element,
      { createUrl: '/c', labels: { createEvent: 'Nouvel événement' } },
      { request, dialog, focusEvent, calendar: () => calendar },
    );

    expect(handlers.customButtons[CREATE_BUTTON].text).toBe('Nouvel événement');
    await handlers.customButtons[CREATE_BUTTON].click();

    expect(dialog).toHaveBeenCalledWith(expect.objectContaining({
      withTitle: true,
      values: { title: '', start: '2026-11-05T09:00', end: '2026-11-05T10:00', allDay: false },
    }));
    expect(request).toHaveBeenCalledWith('/c', { title: 'Retro', start: '2026-11-05T15:00', end: '2026-11-05T16:00', allDay: false });
    expect(calendar.addEvent).toHaveBeenCalledWith({ id: '9', title: 'Retro' });
    expect(focusEvent).toHaveBeenCalledWith('9');
  });
});

describe('event dialog values', () => {
  test('all-day ends are shown inclusive and saved exclusive', () => {
    const values = dialogValues(fcEvent({ allDay: true, startStr: '2026-11-06', endStr: '2026-11-08' }));
    expect(values).toEqual({ title: 'Kick-off', allDay: true, start: '2026-11-06', end: '2026-11-07' });
    expect(datesFromDialog(values)).toEqual({ start: '2026-11-06', end: '2026-11-08', allDay: true });
    expect(datesFromDialog({ ...values, end: '2026-11-06' })).toEqual({ start: '2026-11-06', end: null, allDay: true });
    expect(datesFromDialog({ allDay: false, start: '2026-11-06T09:00', end: '' }))
      .toEqual({ start: '2026-11-06T09:00', end: null, allDay: false });
  });

  test('validation: title when creating, a start, and an end after the start', () => {
    const labels = DEFAULT_LABELS;
    expect(validateDialog({ title: '', start: '2026-11-06' }, labels, { withTitle: true })).toBe(labels.titleRequired);
    expect(validateDialog({ title: '', start: '' }, labels)).toBe(labels.startRequired);
    expect(validateDialog({ start: '2026-11-06T10:00', end: '2026-11-06T09:00' }, labels)).toBe(labels.endBeforeStart);
    expect(validateDialog({ start: '2026-11-06T10:00', end: '' }, labels)).toBeNull();
  });
});
