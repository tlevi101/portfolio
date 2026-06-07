<section class="section" id="about">
    <div class="shell about-grid">
        <article class="card reveal">
            <span class="eyebrow">{{ __('About') }}</span>
            @if ($profile->about_heading ?? false)
                <h2 class="section-title mt-3">
                    {{ $profile->about_heading }}
                </h2>
            @endif
            <p class="text-muted mt-5">{{ $profile->about }}</p>
        </article>

        <article class="card reveal">
            <span class="eyebrow">{{ __('Skills & stack') }}</span>
            <div class="definition-list mt-5">
                @foreach ($skills as $groupValue => $groupSkills)
                    <div>
                        <span class="label">{{ __($groupValue) }}</span>
                        <p class="text-muted">
                            {{ $groupSkills->pluck('name')->join(', ') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</section>
