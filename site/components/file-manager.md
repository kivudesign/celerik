# File manager

A file manager on [elFinder 2.1](https://studio-42.github.io/elFinder/), locked down: one local folder outside the web root, mandatory authorization, content-checked uploads and CSRF protection.

It needs a server, so there is no live demo on this site: see `examples/plain/files.php` in the repository.

## Setup

```bash
composer require studio-42/elfinder
php artisan vendor:publish --tag=celerik-elfinder   # Laravel: elFinder's assets in public/vendor/elfinder
```

In Symfony or plain PHP, serve `vendor/studio-42/elfinder/{css,img,js,sounds}` under a URL, for example `/elfinder/`.

```php
use Celerik\Ui\Components\FileManager;
use Celerik\Ui\FileManager\{FileManagerConfig, FileManagerConnector};
use Celerik\Ui\Upload\UploadRules;

// The page
$fileManager = FileManager::make('files')
    ->title('Shared files')
    ->url('/files/connector')
    ->assetsUrl('/elfinder')
    ->csrf($csrfToken);

// GET|POST /files/connector
FileManagerConnector::run(
    FileManagerConfig::local('/var/app/files', UploadRules::images()->withType('application/pdf', ['pdf']))
        ->alias('Shared files'),
    authorized: $user->can('manage-files'),
);
```

## Options

| Method | Use |
|---|---|
| `url($connector)`, `assetsUrl($url)` | The connector, and elFinder's assets. |
| `title($title)`, `height($px)`, `lang('fr')` | The region's accessible name, its height, the interface language. |
| `csrf($token, $header = 'X-CSRF-TOKEN')` | Your framework's token; elFinder adds its own on top. |
| `FileManagerConfig::local($root, $rules)` | The folder and the accepted uploads. |
| `alias($name)`, `readOnly()` | The root's name; browse and download only. |
| `allow('mkfile', 'put', 'zipdl', 'resize')` | Optional commands. Archive, extract, netmount and chmod can never be enabled. |

`FileManagerConnector::run()` has no default for `authorized`: decide who may use it.

## Security

- One local folder: keep it outside the web root, and use one root per tenant or user when files are private.
- Uploads are checked by content with the same rules as `UploadHandler`; renaming a file to a dangerous extension is refused.
- Dotfiles (`.htaccess`, `.env`) are hidden and can be neither read nor written.
- Under a CSP, elFinder also needs `style-src-attr 'unsafe-inline'` (see [CSP](../guide/csp)).

## Accessibility

The region is named, the selected folder has enough contrast, the folder-tree arrows have 24 px hit areas, and elFinder's keyboard shortcuts work. elFinder does not expose its file list and folder tree as ARIA widgets, so screen readers announce little structure: if screen-reader users must manage files, also give them a plain list and upload form.
