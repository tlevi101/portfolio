<section class="section" id="experiments">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">{{ __('Side projects') }}</span>
            @if ($profile->experiments_heading ?? false)
                <h2 class="section-title">{{ $profile->experiments_heading }}</h2>
            @endif
            @if ($profile->experiments_intro ?? false)
                <p class="section-text">{{ $profile->experiments_intro }}</p>
            @endif
        </div>

        <div class="project-grid">
            @foreach ($sideProjects as $project)
                <article class="card reveal">
                    <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-3);">
                        {{ $project->title }}
                    </h3>
                    <p style="color: var(--color-text-muted); margin-bottom: var(--space-4);">
                        {{ $project->summary }}
                    </p>
                    <ul class="tag-list">
                        @foreach ($project->stack as $tech)
                            <li class="tag">{{ $tech }}</li>
                        @endforeach
                    </ul>
                    @if ($project->url)
                        <a class="text-link" href="{{ $project->url }}" target="_blank" rel="noopener noreferrer" style="display: block; margin-top: var(--space-4);">
                            {{ __('View ↗') }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
