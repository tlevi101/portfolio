@if (!empty($profile->experience_highlights))
    <section class="section" id="experience">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">{{ __('Experience') }}</span>
                <h2 class="section-title">{{ __("What I've been working on.") }}</h2>
            </div>

            <ul class="reveal grid gap-4 p-0 m-0 list-none max-w-[45rem]">
                @foreach ($profile->experience_highlights as $highlight)
                    <li class="flex gap-4 text-muted pb-4 border-b border-rule">
                        <span class="text-primary shrink-0">—</span>
                        {{ $highlight }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
