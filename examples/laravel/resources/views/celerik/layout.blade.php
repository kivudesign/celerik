<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Celerik UI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@4.0.0/dist/jquery.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    {{-- The theme saved with the theme builder (CelerikThemeController), if any. --}}
    @php($savedTheme = \App\Http\Controllers\CelerikThemeController::saved())
    <x-celerik-assets
        :theme="$savedTheme?->getBase() ?? 'bootstrap'"
        :custom-theme="$savedTheme ? asset('css/celerik-theme.css') : null"
    />
</head>
<body>
<nav class="navbar navbar-expand bg-body-tertiary mb-4" aria-label="Examples">
    <div class="container">
        <span class="navbar-brand">Celerik UI</span>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.grid') }}">Grid</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.chart') }}">Chart</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.form') }}">Form</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.scheduler') }}">Scheduler</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.article') }}">Article</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.ui') }}">Tabs &amp; modal</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('celerik.files') }}">Files</a></li>
            @if (app()->isLocal())
                <li class="nav-item"><a class="nav-link" href="{{ route('celerik.theme') }}">Theme builder</a></li>
            @endif
        </ul>
    </div>
</nav>
<main class="container">
    <h1 class="h3 mb-4">@yield('title')</h1>
    @yield('content')
</main>
</body>
</html>
