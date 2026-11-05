import { openEventDialog } from '../../resources/js/scheduler-dialog.js';
import { DEFAULT_LABELS, validateDialog } from '../../resources/js/scheduler-config.js';

describe('event dialog', () => {
  const open = (overrides = {}) => openEventDialog({
    heading: 'Edit "<b>Kick-off</b>"',
    values: { title: 'Kick-off', start: '2026-11-05T09:00:00Z', end: '2026-11-05T10:00:00Z', allDay: false },
    labels: DEFAULT_LABELS,
    validate: (values) => validateDialog(values, DEFAULT_LABELS),
    ...overrides,
  });
  const dialog = () => document.querySelector('dialog.celerik-scheduler-dialog');
  const input = (text) => document.getElementById(
    [...dialog().querySelectorAll('label')].find((label) => label.textContent === text).htmlFor,
  );
  const button = (name) => [...dialog().querySelectorAll('button')].find((element) => element.textContent === name);

  beforeEach(() => {
    document.body.innerHTML = '<button id="opener">Kick-off</button>';
    document.getElementById('opener').focus();
  });

  test('is a labelled dialog with text-only content and local date-time inputs', () => {
    open();
    expect(dialog().getAttribute('aria-labelledby')).toBe(dialog().querySelector('h2').id);
    expect(dialog().querySelector('h2').textContent).toBe('Edit "<b>Kick-off</b>"');
    expect(dialog().querySelector('h2 b')).toBeNull();
    expect(input('Start').type).toBe('datetime-local');
    expect(input('Start').value).toBe('2026-11-05T09:00');
    expect(input('End').value).toBe('2026-11-05T10:00');
    expect(document.activeElement).toBe(input('Start'));
    expect(button('Delete')).toBeUndefined();
  });

  test('switching to all day keeps the dates', () => {
    open();
    input('All day').click();
    expect(input('Start').type).toBe('date');
    expect(input('Start').value).toBe('2026-11-05');
  });

  test('invalid values show an alert on the field and keep the dialog open', async () => {
    const result = open();
    input('End').value = '2026-11-05T08:00';
    button('Save').click();

    const alert = dialog().querySelector('[role="alert"]');
    expect(alert.textContent).toBe(DEFAULT_LABELS.endBeforeStart);
    expect(input('End').getAttribute('aria-invalid')).toBe('true');
    expect(input('End').getAttribute('aria-describedby')).toBe(alert.id);
    expect(document.activeElement).toBe(input('End'));

    input('End').value = '2026-11-05T11:00';
    button('Save').click();
    await expect(result).resolves.toEqual({
      action: 'save', title: 'Kick-off', start: '2026-11-05T09:00', end: '2026-11-05T11:00', allDay: false,
    });
    expect(dialog()).toBeNull();
    expect(document.activeElement.id).toBe('opener');
  });

  test('Cancel resolves null, Delete resolves the delete action', async () => {
    const cancelled = open({ canDelete: true });
    button('Cancel').click();
    await expect(cancelled).resolves.toBeNull();

    const deleted = open({ canDelete: true });
    button('Delete').click();
    await expect(deleted).resolves.toEqual({ action: 'delete' });
  });
});
