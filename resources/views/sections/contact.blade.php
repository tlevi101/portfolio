<section class="section" id="contact">
    <div class="container contact-grid">
        <article class="card reveal">
            <span class="eyebrow">Contact</span>
            @if ($profile->contact_heading ?? false)
                <h2 style="font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3.4rem); letter-spacing: -0.03em; text-wrap: balance; margin-top: var(--space-3);">
                    {{ $profile->contact_heading }}
                </h2>
            @endif
            @if ($profile->contact_intro ?? false)
                <p style="color: var(--color-text-muted); margin-top: var(--space-4);">
                    {{ $profile->contact_intro }}
                </p>
            @endif

            <ul class="contact-list" style="margin-top: var(--space-6);">
                <li>
                    <span class="label">Email</span>
                    <a class="text-link" href="mailto:{{ $profile->email }}">{{ $profile->email }}</a>
                </li>
                @if ($profile->linkedin_url)
                    <li>
                        <span class="label">LinkedIn</span>
                        <a class="text-link" href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener noreferrer">
                            {{ parse_url($profile->linkedin_url, PHP_URL_HOST) . parse_url($profile->linkedin_url, PHP_URL_PATH) }}
                        </a>
                    </li>
                @endif
                @if ($profile->github_url)
                    <li>
                        <span class="label">GitHub</span>
                        <a class="text-link" href="{{ $profile->github_url }}" target="_blank" rel="noopener noreferrer">
                            {{ parse_url($profile->github_url, PHP_URL_HOST) . parse_url($profile->github_url, PHP_URL_PATH) }}
                        </a>
                    </li>
                @endif
                @if ($profile->cv_path)
                    <li>
                        <span class="label">CV</span>
                        <a class="text-link" href="{{ route('cv.download') }}" target="_blank" rel="noopener noreferrer">
                            Download resume
                        </a>
                    </li>
                @endif
            </ul>
        </article>

        <article class="card reveal">
            <span class="eyebrow">Send a message</span>
            <div style="margin-top: var(--space-5);">
                <livewire:contact-form />
            </div>
        </article>
    </div>
</section>
