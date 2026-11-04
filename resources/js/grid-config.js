import { deepMerge } from './core.js';

// Celerik endpoints answer {data, meta}; DataTables expects its own envelope.
export function toDataTablesResponse(payload) {
  const data = Array.isArray(payload?.data) ? payload.data : [];
  const meta = payload?.meta ?? {};
  return {
    draw: meta.draw ?? 0,
    recordsTotal: meta.total ?? data.length,
    recordsFiltered: meta.filtered ?? meta.total ?? data.length,
    data,
  };
}

export function buildGridConfig(options) {
  const config = {
    columns: (options.columns ?? []).map((column) => ({
      data: column.field,
      name: column.field,
      title: column.title,
      orderable: column.sortable !== false,
      searchable: column.searchable !== false,
      visible: column.visible !== false,
      className: column.className ?? '',
      width: column.width ?? null,
      defaultContent: '',
    })),
    pageLength: options.pageSize ?? 10,
    lengthMenu: options.pageSizes ?? [10, 25, 50, 100],
    searching: options.searchable !== false,
    autoWidth: false,
  };

  if (options.url) {
    config.serverSide = true;
    config.processing = true;
    config.ajax = {
      url: options.url,
      dataFilter: (raw) => JSON.stringify(toDataTablesResponse(JSON.parse(raw))),
    };
  }

  if (options.export?.length) {
    config.layout = { topStart: ['pageLength', { buttons: options.export }] };
  }

  return deepMerge(config, options.options);
}
