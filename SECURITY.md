# Security Policy

## Reporting Vulnerabilities

- Please report security issues via GitHub Security Advisories.
- Do not disclose vulnerabilities publicly until fixed.

## Supported Versions

- Only the latest stable release is supported for security patches.

## Uploads and rich text

`UploadHandler` already:
- reads the real MIME type from the file content (finfo) and checks that the extension matches it;
- refuses executable and script formats (PHP, HTML, SVG, XML, shell, binaries…) and double extensions such as `photo.php.jpg`;
- stores files under random names, and keeps the client name for display only.

Your application must also:
- keep the temporary and final upload directories **outside the web root**, and serve files through a controller that checks access;
- protect the upload endpoints with CSRF (the `X-CSRF-TOKEN` header) and authorization;
- run `UploadHandler::purge()` from a scheduled task to delete unclaimed temporary uploads;
- sanitize editor HTML on the server with `EditorHtml::sanitize()` before storing or displaying it.

## File manager

`FileManagerConfig` already:
- serves files only through the connector (no public URL), and never displays SVG or HTML inline;
- checks uploads like `UploadHandler` (real MIME type, matching extension, no executable or script formats, no double extensions);
- refuses dotfiles and forbidden extensions for every new name (upload, mkdir, rename), and hides existing dotfiles;
- disables archive, extract, netmount and chmod for good, and mkfile, put, zipdl and resize unless `allow()`ed;
- relies on elFinder's CSRF token for every command that changes files.

Your application must also:
- **authorize every connector request**: `FileManagerConnector::run($config, authorized: ...)` has no default, and the page itself should be protected too;
- keep the root folder **outside the web root**, and use a separate root per tenant or user when files are private;
- send the framework's CSRF token as well (`FileManager::csrf()`), and keep `studio-42/elfinder` up to date (`composer audit`): older elFinder versions had path-traversal and remote-code-execution vulnerabilities;
- allow `style-src-attr 'unsafe-inline'` in its CSP for the File Manager page only, if it uses a strict policy.

## Best Practices

- Follow secure coding guidelines.
- Validate all inputs.
- Keep dependencies updated.
