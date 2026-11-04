# Third-Party Notices

Celerik UI is MIT licensed (see [LICENSE.md](LICENSE.md)). The prebuilt bundle in `public/` includes the following libraries, each under its own license:

| Library | Version | License | Bundled in |
|---|---|---|---|
| [DataTables](https://datatables.net) (`datatables.net`, `datatables.net-bs5`) | 2.x | MIT | `celerik.js`, `celerik.css` |
| [DataTables Buttons](https://datatables.net/extensions/buttons/) (`datatables.net-buttons`, `-bs5`) | 3.x | MIT | `celerik.js`, `celerik.css` |
| [Chart.js](https://www.chartjs.org) | 4.x | MIT | `celerik.js` |
| [Parsley.js](https://parsleyjs.org) | 2.9 | MIT | `celerik.js` |
| [Inputmask](https://github.com/RobinHerbots/Inputmask) | 5.x | MIT | `celerik.js` |

## Peer dependencies (not bundled, loaded by the application)

| Library | Version | License |
|---|---|---|
| [Bootstrap](https://getbootstrap.com) | 5.3 | MIT |
| [jQuery](https://jquery.com) | 3.7 | MIT |

Full license texts are in each package under `node_modules/<package>/LICENSE` after `npm install`. They must be kept with any redistribution of `public/celerik.js`.

Update this file whenever a library is added to the bundle (see [doc/Rules.md](doc/Rules.md#licensing)).
