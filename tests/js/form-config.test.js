import { boundValidators, compareValues } from '../../resources/js/form-config.js';

describe('bounds for numbers, dates and times', () => {
  test('numbers compare as numbers, ISO dates and times as text', () => {
    expect(compareValues('10', '9')).toBeGreaterThan(0);
    expect(compareValues('-1.5', '.5')).toBeLessThan(0);
    expect(compareValues('09:30', '10:00')).toBeLessThan(0);
    expect(compareValues('2026-11-05T09:30', '2026-11-05T09:30')).toBe(0);
    expect(compareValues('2026-12-01', '2026-11-30')).toBeGreaterThan(0);
  });

  test('min, max, range and after accept empty values and bounds themselves', () => {
    expect(boundValidators.min('', '08:00')).toBe(true);
    expect(boundValidators.min('08:00', '08:00')).toBe(true);
    expect(boundValidators.min('07:59', '08:00')).toBe(false);
    expect(boundValidators.max('18:01', '18:00')).toBe(false);
    expect(boundValidators.range('12:00', ['08:00', '18:00'])).toBe(true);
    expect(boundValidators.range('5', ['8', '18'])).toBe(false);
    expect(boundValidators.after('2026-03-01', '2026-03-01')).toBe(true);
    expect(boundValidators.after('2026-02-28', '2026-03-01')).toBe(false);
    expect(boundValidators.after('2026-02-28', '')).toBe(true);
  });
});
