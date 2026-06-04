@if (!empty($profile->experience_highlights))
    <section class="section" id="experience">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Experience</span>
                <h2 class="section-title">What I&rsquo;ve been working on.</h2>
            </div>

            <ul class="reveal" style="display: grid; gap: var(--space-4); padding: 0; margin: 0; list-style: none; max-width: 45rem;">
                @foreach ($profile->experience_highlights as $highlight)
                    <li style="display: flex; gap: var(--space-4); color: var(--color-text-muted); padding-bottom: var(--space-4); border-bottom: 1px solid var(--color-divider);">
                        <span style="color: var(--color-primary); flex-shrink: 0;">—</span>
                        {{ $highlight }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
