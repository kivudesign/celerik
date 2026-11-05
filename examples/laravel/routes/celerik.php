<?php

// Add to routes/web.php:  require __DIR__ . '/celerik.php';

declare(strict_types=1);

use App\Http\Controllers\CelerikArticleController;
use App\Http\Controllers\CelerikCalendarController;
use App\Http\Controllers\CelerikDemoController;
use App\Http\Controllers\CelerikUiController;
use Illuminate\Support\Facades\Route;

Route::prefix('celerik')->name('celerik.')->controller(CelerikDemoController::class)->group(static function (): void {
    Route::get('/grid', 'grid')->name('grid');
    Route::get('/grid/data', 'gridData')->name('grid.data');
    Route::get('/chart', 'chart')->name('chart');
    Route::get('/form', 'form')->name('form');
    Route::post('/form', 'submit')->name('form.submit');
});

Route::prefix('celerik/scheduler')
    ->name('celerik.scheduler')
    ->controller(CelerikCalendarController::class)
    ->group(static function (): void {
        Route::get('/', 'show');
        Route::get('/events', 'events')->name('.events');
        Route::post('/events/create', 'create')->name('.create');
        Route::post('/events/update', 'update')->name('.update');
        Route::post('/events/delete', 'delete')->name('.delete');
    });

Route::prefix('celerik/article')
    ->name('celerik.article')
    ->controller(CelerikArticleController::class)
    ->group(static function (): void {
        Route::get('/', 'show');
        Route::post('/', 'submit')->name('.submit');
        Route::match(['POST', 'DELETE'], '/upload/{field}', 'upload')
            ->whereIn('field', ['cover', 'attachments'])
            ->name('.upload');
    });

Route::prefix('celerik/ui')
    ->name('celerik.ui')
    ->controller(CelerikUiController::class)
    ->group(static function (): void {
        Route::get('/', 'show');
        Route::post('/subscribe', 'subscribe')->name('.subscribe');
    });
