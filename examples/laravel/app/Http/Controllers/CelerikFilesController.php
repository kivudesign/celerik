<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Celerik\Ui\Components\FileManager;
use Celerik\Ui\FileManager\FileManagerConfig;
use Celerik\Ui\FileManager\FileManagerConnector;
use Celerik\Ui\Upload\UploadRules;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * File manager (elFinder). Both routes are protected by the "manage-files" gate:
 * define it for your users (see the README). The files live in storage/app/private/files,
 * outside public/, and are only served through the connector.
 */
final class CelerikFilesController extends Controller
{
    public function show(): View
    {
        Gate::authorize('manage-files');

        return view('celerik.files', [
            'fileManager' => FileManager::make('files')
                ->title('Shared files')
                ->url(route('celerik.files.connector'))
                ->assetsUrl(asset('vendor/elfinder'))
                ->csrf(csrf_token()),
        ]);
    }

    public function connector(): never
    {
        $root = storage_path('app/private/files');
        if (!is_dir($root)) {
            mkdir($root, 0700, true);
        }

        FileManagerConnector::run(
            FileManagerConfig::local($root, UploadRules::images()->withType('application/pdf', ['pdf']))
                ->alias('Shared files'),
            authorized: Gate::allows('manage-files'),
        );
    }
}
