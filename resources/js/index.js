import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css';
import '../scss/themes/bootstrap.scss';

import { autoInit, init, register } from './core.js';

// Each library is loaded on demand, only on pages that contain that component.
register('grid', async (element, options) => (await import('./grid.js')).initGrid(element, options));
register('chart', async (element, options) => (await import('./chart.js')).initChart(element, options));
register('form', async (element, options) => (await import('./form.js')).initForm(element, options));
register('scheduler', async (element, options) => (await import('./scheduler.js')).initScheduler(element, options));
register('modal', async (element, options) => (await import('./modal.js')).initModal(element, options));
register('tabs', async (element, options) => (await import('./tabs.js')).initTabs(element, options));
register('notification', async (element, options) => (await import('./notification.js')).initNotification(element, options));

// Shows a toast from JavaScript, e.g. after an AJAX request: Celerik.notify('success', 'Saved.').
// The message is always treated as text.
async function notify(type, message) {
  return (await import('./notification.js')).notify(type, message);
}

const Celerik = { register, init, notify };
window.Celerik = Celerik;

autoInit();

export default Celerik;
