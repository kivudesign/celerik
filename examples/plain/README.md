# Plain PHP example

Grid (server-side, 250 rows), Chart, Scheduler, Form, an article form with a rich text editor and uploads, and Tabs, a Modal and Notifications, without any framework.

```bash
composer install
npm install && npm run build
php -S localhost:8000 examples/plain/index.php
```

Open <http://localhost:8000>.

- `/people.json` is the Grid endpoint. It accepts DataTables parameters and the simple format, for example `/people.json?page=2&per_page=5&sort=-name&search=paris`.
- The Scheduler loads `/events.json?start=…&end=…` and posts changes as JSON to `/events/create`, `/events/update` and `/events/delete` with the `X-CSRF-TOKEN` header (see `calendar.php`). Events are kept in the session.
- The article form (`article.php`) uploads files to `/upload/cover` and `/upload/attachments` (FilePond: POST returns an id, DELETE reverts). On submit, `/article` claims the ids, sanitizes the editor HTML with `EditorHtml::sanitize()`, and shows the result. Files are stored in the system temp directory, outside the web root.
- `ui.php`: Tabs remember the selected tab in the URL hash. The newsletter form lives in a Modal: on a validation error the page reopens the modal (`open()`); on success it redirects and shows a Notification from the session flash.
- `files.php`: a File Manager on a folder in the system temp directory. The connector is `/files/connector`, and elFinder's assets are served from `vendor/studio-42/elfinder` under `/elfinder/`. This demo only authorizes requests from this computer.
- The form posts to `/contact`. Server-side errors are shown with Bootstrap feedback, and the CSRF token is checked with `hash_equals`.
- Bootstrap and jQuery come from a CDN here. Celerik's own bundle is served from `public/` under `/assets/`.
