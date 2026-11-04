import { buildGridConfig, toDataTablesResponse } from '../../resources/js/grid-config.js';
import { initGrid } from '../../resources/js/grid.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('grid config', () => {
  test('maps PHP columns to DataTables columns', () => {
    const config = buildGridConfig({
      columns: [{ field: 'name', title: 'Name', sortable: false, searchable: true, visible: true }],
      pageSize: 25,
    });

    expect(config.columns[0]).toMatchObject({ data: 'name', title: 'Name', orderable: false, searchable: true });
    expect(config.pageLength).toBe(25);
    expect(config.serverSide).toBeUndefined();
  });

  test('enables server-side mode and converts the Celerik envelope', () => {
    const config = buildGridConfig({ columns: [], url: '/users/data' });
    const raw = JSON.stringify({ data: [{ name: 'Ada' }], meta: { draw: 4, total: 10, filtered: 1 } });

    expect(config.serverSide).toBe(true);
    expect(JSON.parse(config.ajax.dataFilter(raw))).toEqual({
      draw: 4,
      recordsTotal: 10,
      recordsFiltered: 1,
      data: [{ name: 'Ada' }],
    });
  });

  test('raw options from PHP override generated ones', () => {
    expect(buildGridConfig({ columns: [], options: { pageLength: 5, order: [[0, 'desc']] } }))
      .toMatchObject({ pageLength: 5, order: [[0, 'desc']] });
  });

  test('toDataTablesResponse tolerates a missing meta block', () => {
    expect(toDataTablesResponse({ data: [1, 2] })).toEqual({ draw: 0, recordsTotal: 2, recordsFiltered: 2, data: [1, 2] });
  });
});

describe('grid integration (DataTables)', () => {
  test('enhances the PHP-rendered table with paging, search and export buttons', () => {
    const table = loadFixture('grid');
    const grid = initGrid(table, readOptions(table));

    expect(grid.rows().count()).toBe(3);
    expect(table.querySelectorAll('tbody tr')).toHaveLength(2);
    expect(document.querySelector('.dt-search input')).not.toBeNull();
    expect([...document.querySelectorAll('.dt-buttons button')].map((b) => b.textContent.trim()))
      .toEqual(['Copy', 'CSV']);

    grid.search('hopper').draw();
    expect(table.querySelectorAll('tbody tr')).toHaveLength(1);
    expect(table.querySelector('tbody td:nth-child(2)').textContent).toBe('Grace Hopper');

    grid.destroy();
  });
});
