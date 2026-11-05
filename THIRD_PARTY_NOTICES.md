# Third-Party Notices

Celerik UI is MIT licensed (see [LICENSE.md](LICENSE.md)). The prebuilt bundle in `public/` includes the following libraries, each under its own license:

| Library | Version | License | Bundled in |
|---|---|---|---|
| [DataTables](https://datatables.net) (`datatables.net`, `datatables.net-bs5`) | 2.x | MIT | `chunks/grid-*.js`, `celerik.css` |
| [DataTables Buttons](https://datatables.net/extensions/buttons/) (`datatables.net-buttons`, `-bs5`) | 3.x | MIT | `chunks/grid-*.js`, `celerik.css` |
| [Chart.js](https://www.chartjs.org) | 4.x | MIT | `chunks/chart-*.js` |
| [Parsley.js](https://parsleyjs.org) | 2.9 | MIT | `chunks/form-*.js` |
| [Inputmask](https://github.com/RobinHerbots/Inputmask) | 5.x | MIT | `chunks/form-*.js` |
| [FullCalendar](https://fullcalendar.io) (`core`, `daygrid`, `timegrid`, `list`, `interaction`) | 6.x | MIT | `chunks/scheduler-*.js` |
| [Preact](https://preactjs.com) (FullCalendar dependency) | 10.x | MIT | `chunks/scheduler-*.js` |
| [FilePond](https://pqina.nl/filepond/) and its `file-validate-type`, `file-validate-size`, `image-preview` plugins | 4.x | MIT | `chunks/upload-*.js`, `chunks/upload-*.css` |
| [Quill](https://quilljs.com) | 2.x | BSD-3-Clause | `chunks/editor-*.js`, `chunks/editor-*.css` |
| [Parchment](https://github.com/slab/parchment) (Quill dependency) | 3.x | BSD-3-Clause | `chunks/editor-*.js` |
| [quill-delta](https://github.com/slab/delta), [eventemitter3](https://github.com/primus/eventemitter3), [lodash-es](https://lodash.com) (Quill dependencies) | – | MIT | `chunks/editor-*.js` |
| [Notyf](https://carlosroso.com/notyf/) | 3.x | MIT | `chunks/notification-*.js`, `chunks/notification-*.css` |
| [jQuery UI](https://jqueryui.com) (for elFinder) | 1.14 | MIT | `chunks/filemanager-*.js`, `chunks/filemanager-*.css`, `chunks/ui-icons_*.png` |

## Peer dependencies (not bundled, loaded by the application)

| Library | Version | License |
|---|---|---|
| [Bootstrap](https://getbootstrap.com) (CSS; `bootstrap.bundle.js` for Modal and Tabs) | 5.3 | MIT |
| [jQuery](https://jquery.com) | 3.7 | MIT |
| [elFinder](https://github.com/Studio-42/elFinder) (`studio-42/elfinder`, for the File Manager: connector, JS, CSS, images) | 2.1 | BSD-3-Clause |

Full license texts are in each package under `node_modules/<package>/LICENSE` after `npm install`. They must be kept with any redistribution of the files in `public/`.

Update this file whenever a library is added to the bundle. Only MIT, Apache 2.0, BSD or ISC libraries may be bundled (see [Components.md](Components.md#licensing-notes)).
