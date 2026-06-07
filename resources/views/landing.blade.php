<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('theme') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    {{-- Apply the theme before first paint to avoid a light→dark flash, and
         mirror it to a cookie so server-rendered (wire:navigate) pages match. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('theme');
                var theme = (stored === 'light' || stored === 'dark')
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                document.cookie = 'theme=' + theme + '; path=/; max-age=31536000; SameSite=Lax';
            } catch (e) {}
        })();
    </script>
    <title>{{ $profile->full_name }} — {{ __('Portfolio') }}</title>
    <meta name="description" content="{{ $profile->tagline }}" />
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}" />
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin />
    <link rel="stylesheet" href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600,700&f[]=zodiak@400,700&display=swap" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a class="skip-link" href="#main">{{ __('Skip to content') }}</a>

    <header class="site-header">
        <div class="shell header-inner">
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

            <div class="header-actions">
                @if (($availableLocales ?? collect())->count() > 1)
                    <nav class="lang-switch" aria-label="{{ __('Language') }}">
                        @foreach ($availableLocales as $locale)
                            <a
                                href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
                                wire:navigate
                                lang="{{ $locale }}"
                                @class(['is-active' => $locale === ($currentLocale ?? app()->getLocale())])
                                @if ($locale === ($currentLocale ?? app()->getLocale())) aria-current="true" @endif
                            >{{ strtoupper($locale) }}</a>
                        @endforeach
                    </nav>
                @endif

                <button class="theme-toggle" type="button" data-theme-toggle aria-label="{{ __('Switch theme') }}">
                    <span aria-hidden="true">◐</span>
                </button>
            </div>
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
            <div class="shell">{{ $profile->footer_text }}</div>
        </footer>
    @endif

    @livewireScripts
</body>
</html>
