import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css';
import '../scss/themes/bootstrap.scss';

import { autoInit, init, register } from './core.js';
import { initChart } from './chart.js';
import { initForm } from './form.js';
import { initGrid } from './grid.js';

register('grid', initGrid);
register('chart', initChart);
register('form', initForm);

const Celerik = { register, init };
window.Celerik = Celerik;

autoInit();

export default Celerik;
