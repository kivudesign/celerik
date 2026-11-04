# Plain PHP example

Grid (server-side, 250 rows), Chart and Form without any framework.

```bash
composer install
npm install && npm run build
php -S localhost:8000 examples/plain/index.php
```

Open <http://localhost:8000>.

- `/people.json` is the Grid endpoint. It accepts DataTables parameters and the simple format, for example `/people.json?page=2&per_page=5&sort=-name&search=paris`.
- The form posts to `/contact`. Server-side errors are shown with Bootstrap feedback, and the CSRF token is checked with `hash_equals`.
- Bootstrap and jQuery come from a CDN here. Celerik's own bundle is served from `public/` under `/assets/`.
