// Runs in the <head> of every demo page (site/demos/build.php), right after celerik.css and
// before the page renders: ?mode=dark sets Bootstrap's dark mode, and ?theme=material or
// ?theme=tailwind adds that base look's stylesheet after celerik.css.
(function () {
  var params = new URLSearchParams(window.location.search);
  var theme = params.get('theme');
  document.documentElement.setAttribute('data-bs-theme', params.get('mode') === 'dark' ? 'dark' : 'light');

  if (theme === 'material' || theme === 'tailwind') {
    var base = document.querySelector('link[href$="celerik.css"]');
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = base.href.replace(/celerik\.css$/, 'celerik-' + theme + '.css');
    base.after(link);
  }
}());
