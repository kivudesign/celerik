// jQuery UI attaches itself to the page's jQuery; it is only bundled in this chunk.
import 'jquery-ui/dist/jquery-ui.js';
import 'jquery-ui/dist/themes/base/jquery-ui.min.css';
import $ from 'jquery';
import { buildElfinderConfig, elfinderAssets, loadAsset } from './filemanager-config.js';

export async function initFileManager(element, options) {
  const assets = elfinderAssets(options);
  await Promise.all(assets.styles.map((url) => loadAsset('link', url)));
  if (!$.fn.elfinder) {
    await loadAsset('script', assets.script);
  }

  element.replaceChildren();
  return $(element).elfinder(buildElfinderConfig(options)).elfinder('instance');
}
