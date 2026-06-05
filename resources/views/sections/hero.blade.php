<section class="section pt-12 min-[860px]:pt-24" id="top">
    <div class="shell grid items-center gap-10 min-[860px]:grid-cols-[1.25fr_0.85fr] min-[860px]:gap-12">
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
                @if ($profile->cv_path)
                    <a class="button button-primary" href="{{ route('cv.download') }}" target="_blank" rel="noopener noreferrer">
                        {{ __('Download CV') }}
                    </a>
                @endif
                <a class="button button-secondary" href="#projects">{{ __('View projects') }}</a>
            </div>
        </div>

        <aside class="reveal mx-auto w-full max-w-[20rem] min-[860px]:mx-0 min-[860px]:max-w-[22rem] min-[860px]:justify-self-end" aria-label="{{ __('Profile summary') }}">
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
