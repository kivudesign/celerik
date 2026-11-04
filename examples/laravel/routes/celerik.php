<?php

// Add to routes/web.php:  require __DIR__ . '/celerik.php';

declare(strict_types=1);

use App\Http\Controllers\CelerikDemoController;
use Illuminate\Support\Facades\Route;

Route::prefix('celerik')->name('celerik.')->controller(CelerikDemoController::class)->group(static function (): void {
    Route::get('/grid', 'grid')->name('grid');
    Route::get('/grid/data', 'gridData')->name('grid.data');
    Route::get('/chart', 'chart')->name('chart');
    Route::get('/form', 'form')->name('form');
    Route::post('/form', 'submit')->name('form.submit');
});
