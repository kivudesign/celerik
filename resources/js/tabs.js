import { bootstrapComponent } from './bootstrap-global.js';

// The tab whose panel matches the URL hash. The hash is only compared with the
// component's own targets, never used to build a selector.
export function tabForHash(element, hash) {
  return [...element.querySelectorAll('[role="tab"][data-bs-target]')]
    .find((tab) => tab.getAttribute('data-bs-target') === hash) ?? null;
}

// Bootstrap switches panels and handles arrow-key navigation; this adds the optional
// "remember the selected tab in the URL hash".
export function initTabs(element, options) {
  const Tab = bootstrapComponent('Tab');
  if (!Tab) {
    return null;
  }

  if (options.rememberInHash) {
    const fromHash = tabForHash(element, window.location.hash);
    if (fromHash) {
      Tab.getOrCreateInstance(fromHash).show();
    }
    element.addEventListener('shown.bs.tab', (event) => {
      const target = event.target.getAttribute('data-bs-target');
      if (target) {
        window.history.replaceState(null, '', target);
      }
    });
  }

  return element;
}
