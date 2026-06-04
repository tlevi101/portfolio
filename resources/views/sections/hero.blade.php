<section class="section hero" id="top" style="padding-top: clamp(3rem, 10vw, 7rem);">
    <div class="container hero-layout">
        <div class="hero-copy reveal">
            @if ($profile->hero_eyebrow)
                <span class="eyebrow">{{ $profile->hero_eyebrow }}</span>
            @endif

            <h1 style="font-size: clamp(3rem, 7vw, 6.3rem); max-width: 9ch; text-wrap: balance;">
                {{ $profile->full_name }}
            </h1>

            <p style="color: var(--color-text-muted); font-size: var(--text-lg); max-width: 42rem;">
                {{ $profile->tagline }}
            </p>

            <div class="hero-actions">
                <a class="button button-primary" href="#contact">Contact me</a>
                <a class="button button-secondary" href="#projects">View projects</a>
                @if ($profile->cv_path)
                    <a class="button button-link" href="{{ route('cv.download') }}" target="_blank" rel="noopener noreferrer">
                        Download CV
                    </a>
                @endif
            </div>
        </div>

        <aside class="hero-aside reveal" aria-label="Profile summary">
            <div class="portrait-block">
                <div class="portrait" role="img" aria-label="Profile photo of {{ $profile->full_name }}">
                    @if ($profile->avatar_path)
                        <img src="{{ Storage::url($profile->avatar_path) }}" alt="{{ $profile->full_name }}" />
                    @endif
                    @if ($profile->available && $profile->available_text)
                        <div class="portrait-badge">{{ $profile->available_text }}</div>
                    @endif
                </div>
                <p class="quick-note">{{ $profile->location }}</p>
            </div>
        </aside>
    </div>
</section>
