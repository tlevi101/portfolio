<section class="section" id="about">
    <div class="container about-grid">
        <article class="card reveal">
            <span class="eyebrow">{{ __('About') }}</span>
            @if ($profile->about_heading ?? false)
                <h2 style="font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3.4rem); letter-spacing: -0.03em; text-wrap: balance; margin-top: var(--space-3);">
                    {{ $profile->about_heading }}
                </h2>
            @endif
            <p style="color: var(--color-text-muted); margin-top: var(--space-5);">{{ $profile->about }}</p>
        </article>

        <article class="card reveal">
            <span class="eyebrow">{{ __('Skills & stack') }}</span>
            <div class="definition-list" style="margin-top: var(--space-5);">
                @foreach ($skills as $groupValue => $groupSkills)
                    <div>
                        <span class="label">{{ $groupValue }}</span>
                        <p style="color: var(--color-text-muted);">
                            {{ $groupSkills->pluck('name')->join(', ') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</section>
