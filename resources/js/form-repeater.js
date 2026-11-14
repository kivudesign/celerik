// Repeaters (Repeater::make()): add, remove and move rows. PHP renders the rows and a
// <template> of an empty row whose key is a placeholder; a new row gets the next free key.
// Keys are never renumbered, so ids, labels and error messages keep matching their fields.

const PLACEHOLDER = '__index__';
const FOCUSABLE = 'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])';

// The first field of a row, or the visible search box of a searchable select.
function firstField(row) {
  const field = row.querySelector(FOCUSABLE);
  return field?.tomselect?.control_input ?? field;
}

export function initRepeaters(form, parsley, enhance) {
  for (const root of form.querySelectorAll('[data-celerik-repeater]')) {
    initRepeater(root, parsley, enhance);
  }
}

function initRepeater(root, parsley, enhance) {
  const config = JSON.parse(root.getAttribute('data-celerik-repeater'));
  const list = root.querySelector('.celerik-repeater-items');
  const template = root.querySelector('template');
  const add = root.querySelector('[data-celerik-repeater-add]');
  const rows = () => [...list.children];

  // Server keys may be any names: new rows count on from the largest number.
  let next = rows().reduce((max, row) => {
    const key = row.getAttribute('data-celerik-key');
    return /^\d+$/.test(key) ? Math.max(max, Number(key) + 1) : max;
  }, 0);

  // Row names ("Item 2") follow the position; buttons follow the limits.
  const update = () => {
    const all = rows();
    all.forEach((row, index) => {
      row.querySelector('.celerik-repeater-label').textContent = config.item.replace('%d', String(index + 1));
      const up = row.querySelector('[data-celerik-repeater-move="up"]');
      const down = row.querySelector('[data-celerik-repeater-move="down"]');
      if (up) {
        up.disabled = index === 0;
        down.disabled = index === all.length - 1;
      }
      row.querySelector('[data-celerik-repeater-remove]').disabled = all.length <= config.min;
    });
    add.disabled = config.max !== null && all.length >= config.max;
    // A repeater error ("at least one line") is stale once the rows change.
    root.querySelector(':scope > .celerik-server-error')?.remove();
    parsley.refresh();
  };

  add.addEventListener('click', async () => {
    list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll(PLACEHOLDER, String(next)));
    next += 1;
    const row = list.lastElementChild;
    update();
    await enhance(row);
    firstField(row)?.focus();
  });

  list.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    const row = button?.closest('.celerik-repeater-item');
    if (!row || button.disabled) {
      return;
    }

    if (button.matches('[data-celerik-repeater-remove]')) {
      // Focus goes to the row that takes its place, the one before it, or Add.
      const neighbour = row.nextElementSibling ?? row.previousElementSibling;
      for (const select of row.querySelectorAll('select')) {
        select.tomselect?.destroy();
      }
      row.remove();
      update();
      (neighbour ? firstField(neighbour) : add).focus();
      return;
    }

    const direction = button.getAttribute('data-celerik-repeater-move');
    if (direction === 'up' && row.previousElementSibling) {
      list.insertBefore(row, row.previousElementSibling);
    } else if (direction === 'down' && row.nextElementSibling) {
      list.insertBefore(row.nextElementSibling, row);
    }
    update();
    // Keep focus on the button, or on the other one at the top or the bottom.
    const other = row.querySelector(`[data-celerik-repeater-move="${direction === 'up' ? 'down' : 'up'}"]`);
    (button.disabled ? other : button).focus();
  });
}
