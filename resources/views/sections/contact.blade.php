<section class="section" id="contact">
    <div class="shell">
        <article class="card reveal max-w-[38rem]">
            <span class="eyebrow">{{ __('Contact') }}</span>
            @if ($profile->contact_heading ?? false)
                <h2 class="section-title mt-3">
                    {{ $profile->contact_heading }}
                </h2>
            @endif
            @if ($profile->contact_intro ?? false)
                <p class="text-muted mt-4">
                    {{ $profile->contact_intro }}
                </p>
            @endif

            <ul class="contact-list mt-6">
                <li>
                    <span class="label">{{ __('Name') }}</span>
                    <span>{{ $profile->full_name }}</span>
                </li>
                @if ($profile->phone)
                    <li>
                        <span class="label">{{ __('Phone') }}</span>
                        <a class="text-link" href="tel:{{ preg_replace('/[^+0-9]/', '', $profile->phone) }}">{{ $profile->phone }}</a>
                    </li>
                @endif
                <li>
                    <span class="label">{{ __('Email') }}</span>
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
                @if ($profile->cv)
                    <li>
                        <span class="label">CV</span>
                        <a class="text-link" href="{{ route('cv.download', ['slug' => $profile->slug, 'lang' => $profile->locale]) }}" target="_blank" rel="noopener noreferrer">
                            {{ __('Download resume') }}
                        </a>
                    </li>
                @endif
            </ul>
        </article>
    </div>
</section>
