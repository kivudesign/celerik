// Bootstrap's JS is a peer dependency (the page loads bootstrap.bundle.js), like jQuery.
export function bootstrapComponent(name) {
  const component = window.bootstrap?.[name];
  if (!component) {
    console.warn(`Celerik: load Bootstrap's JS (bootstrap.bundle.js) to use ${name}.`);
  }
  return component ?? null;
}
