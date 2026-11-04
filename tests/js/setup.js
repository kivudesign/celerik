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

// jsdom has no canvas backend; axe-core probes it and only needs a null context.
HTMLCanvasElement.prototype.getContext = () => null;
