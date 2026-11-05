// jsdom does not implement matchMedia, which Inputmask reads at import time.
if (!window.matchMedia) {
  window.matchMedia = (query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener() {},
    removeListener() {},
    addEventListener() {},
    removeEventListener() {},
    dispatchEvent: () => false,
  });
}

// FilePond checks these at import time and refuses to run without them.
if (!window.URL.createObjectURL) {
  window.URL.createObjectURL = () => 'blob:jsdom';
  window.URL.revokeObjectURL = () => {};
}
if (!window.CSS?.supports) {
  window.CSS = { ...window.CSS, supports: () => true };
}

// jsdom has no canvas backend; axe-core probes it and only needs a null context.
HTMLCanvasElement.prototype.getContext = () => null;
