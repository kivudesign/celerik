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

## Best Practices

- Follow secure coding guidelines.
- Validate all inputs.
- Keep dependencies updated.
