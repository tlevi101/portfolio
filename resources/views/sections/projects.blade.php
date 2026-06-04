@php
    $featured = $selectedProjects->firstWhere('featured', true);
    $rest = $selectedProjects->where('featured', false);
@endphp

<section class="section" id="projects">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Selected projects</span>
            @if ($profile->projects_heading ?? false)
                <h2 class="section-title">{{ $profile->projects_heading }}</h2>
            @endif
            @if ($profile->projects_subheading ?? false)
                <p class="section-text">{{ $profile->projects_subheading }}</p>
            @endif
        </div>

        <div style="display: grid; gap: var(--space-4);">
            @if ($featured)
                <article class="card-featured reveal">
                    <div class="feature-meta">
                        <span>Featured work</span>
                        @foreach ($featured->stack as $tech)
                            <span>{{ $tech }}</span>
                        @endforeach
                    </div>

                    <div class="feature-grid">
                        <div style="display: grid; gap: var(--space-4);">
                            <h3 style="font-size: clamp(1.6rem, 3vw, 2.4rem); line-height: 1.08;">
                                {{ $featured->title }}
                            </h3>
                            <p style="color: var(--color-text-muted);">{{ $featured->summary }}</p>
                        </div>

                        <ul class="micro-list">
                            @if ($featured->problem)
                                <li><strong>Problem:</strong> {{ $featured->problem }}</li>
                            @endif
                            @if ($featured->role_description)
                                <li><strong>Role:</strong> {{ $featured->role_description }}</li>
                            @endif
                            @if ($featured->outcome)
                                <li><strong>Outcome:</strong> {{ $featured->outcome }}</li>
                            @endif
                        </ul>
                    </div>

                    <ul class="tag-list" aria-label="Technology stack">
                        @foreach ($featured->stack as $tech)
                            <li class="tag">{{ $tech }}</li>
                        @endforeach
                    </ul>

                    @if ($featured->url)
                        <a class="text-link" href="{{ $featured->url }}" target="_blank" rel="noopener noreferrer">
                            Open project ↗
                        </a>
                    @endif
                </article>
            @endif

            @if ($rest->isNotEmpty())
                <div class="project-grid">
                    @foreach ($rest as $project)
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
                                    View ↗
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
