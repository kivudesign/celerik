import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css';
import '../scss/themes/bootstrap.scss';

import { autoInit, init, register } from './core.js';

// Each library is loaded on demand, only on pages that contain that component.
register('grid', async (element, options) => (await import('./grid.js')).initGrid(element, options));
register('chart', async (element, options) => (await import('./chart.js')).initChart(element, options));
register('form', async (element, options) => (await import('./form.js')).initForm(element, options));

const Celerik = { register, init };
window.Celerik = Celerik;

autoInit();

export default Celerik;
