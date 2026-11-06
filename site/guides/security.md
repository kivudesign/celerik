<!--@include: ../../SECURITY.md-->

## CSRF and Content Security Policy

- Components that post JSON (Scheduler, uploads, File Manager, ThemeBuilder) send the CSRF token in the `X-CSRF-TOKEN` header. Laravel checks it by itself; in [Symfony](../guide/symfony#csrf) and [plain PHP](../guide/plain-php#forms-and-csrf) check it in the endpoint.
- Components write no inline scripts, so a strict [Content Security Policy](../guide/csp) works with a nonce.
- Data endpoints only accept declared fields: see [Data endpoints](../guide/data#security).
