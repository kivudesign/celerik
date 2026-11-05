// jQuery is a peer dependency: the bundle uses the page's copy instead of shipping its own.
// The Vite build aliases every `import 'jquery'` (ours and the libraries') to this module.
const jQuery = window.jQuery;

if (!jQuery) {
  throw new Error('Celerik: load jQuery before celerik.js.');
}

export default jQuery;
