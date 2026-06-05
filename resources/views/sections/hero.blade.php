<section class="section pt-12 md:pt-24" id="top">
    <div class="container grid items-center gap-10 md:grid-cols-[1.4fr_0.6fr] md:gap-12">
        <div class="reveal">
            @if ($profile->hero_eyebrow)
                <span class="text-xs uppercase tracking-[0.12em] text-faint">{{ $profile->hero_eyebrow }}</span>
            @endif

            <h1 class="mt-3 max-w-[12ch] text-balance font-display text-display leading-[1.02] tracking-[-0.03em]">
                {{ $profile->full_name }}
            </h1>

            <p class="mt-4 text-lead font-medium text-ink">
                {{ $profile->role }}
            </p>

            <p class="mt-3 max-w-xl text-muted">
                {{ $profile->tagline }}
            </p>

            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a class="button button-primary" href="#contact">{{ __('Contact me') }}</a>
                <a class="button button-secondary" href="#projects">{{ __('View projects') }}</a>
                @if ($profile->cv_path)
                    <a class="button button-link" href="{{ route('cv.download') }}" target="_blank" rel="noopener noreferrer">
                        {{ __('Download CV') }}
                    </a>
                @endif
            </div>
        </div>

        <aside class="reveal mx-auto w-full max-w-[17rem] md:mx-0 md:justify-self-end" aria-label="{{ __('Profile summary') }}">
            <div class="portrait-block">
                <div class="portrait" role="img" aria-label="{{ __('Profile photo of :name', ['name' => $profile->full_name]) }}">
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
