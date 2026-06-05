<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $profile->full_name }} — {{ __('Portfolio') }}</title>
    <meta name="description" content="{{ $profile->tagline }}" />
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin />
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&f[]=zodiak@400,700&display=swap" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a class="skip-link" href="#main">{{ __('Skip to content') }}</a>

    <header class="site-header">
        <div class="container header-inner">
            <a class="nameplate" href="#top" aria-label="{{ __('Go to top') }}">
                <strong>{{ $profile->full_name }}</strong>
                <span>{{ $profile->role }}</span>
            </a>

            <nav class="site-nav" aria-label="{{ __('Primary navigation') }}">
                <a href="#projects">{{ __('Projects') }}</a>
                <a href="#experiments">{{ __('Side projects') }}</a>
                <a href="#about">{{ __('About') }}</a>
                <a href="#contact">{{ __('Contact') }}</a>
            </nav>

            <button class="theme-toggle" type="button" data-theme-toggle aria-label="{{ __('Switch theme') }}">
                <span aria-hidden="true">◐</span>
            </button>
        </div>
    </header>

    <main id="main">
        @include('sections.hero', ['profile' => $profile])
        @include('sections.projects', ['selectedProjects' => $selectedProjects])
        @include('sections.side-projects', ['sideProjects' => $sideProjects])
        @include('sections.about', ['profile' => $profile, 'skills' => $skills])
        @include('sections.experience', ['profile' => $profile])
        @include('sections.contact', ['profile' => $profile])
    </main>

    @if ($profile->footer_text)
        <footer class="site-footer">
            <div class="container">{{ $profile->footer_text }}</div>
        </footer>
    @endif

    @livewireScripts
</body>
</html>
