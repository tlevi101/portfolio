{{--
    Live preview of the CV.

    The iframe loads the very same HTML document the PDF is printed from, so
    what is on screen is what comes out of the printer. Its `src` carries the
    render hash, which the page updates whenever the form state changes — so the
    frame reloads itself without any imperative refresh code.

    The styling here is deliberately self-contained: the admin panel ships
    Filament's own stylesheet, which carries no general-purpose utility classes
    for this to lean on.

    @var string $url        Preview endpoint for this page's Livewire component.
    @var bool   $standalone Whether this instance fills a modal rather than the
                            sidebar (affects the chrome around the paper only).
--}}
@php
    $standalone ??= false;
@endphp

@once
    <style>
        /* A4 at 96dpi. The document is rendered at its true size and scaled
           down to fit, so line breaks match the printed sheet exactly. */
        .cv-preview {
            --cv-page-width: 794px;
            --cv-page-height: 1123px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cv-preview__bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .cv-preview__title {
            font-weight: 500;
            color: var(--gray-950, #09090b);
        }

        .cv-preview__zoom {
            font-size: 0.75rem;
            color: var(--gray-500, #71717a);
            font-variant-numeric: tabular-nums;
        }

        .cv-preview__open {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgb(var(--primary-600, 202 138 4));
            text-decoration: none;
        }

        .cv-preview__open:hover {
            text-decoration: underline;
        }

        .cv-preview__scroller {
            overflow: auto;
            border-radius: 0.75rem;
            background: var(--gray-100, #f4f4f5);
            box-shadow: inset 0 0 0 1px rgb(0 0 0 / 0.05);
            padding: 0.75rem;
        }

        .cv-preview__viewport {
            position: relative;
            width: 100%;
            overflow: hidden;
        }

        .cv-preview__frame {
            width: var(--cv-page-width);
            height: var(--cv-page-height);
            border: 0;
            display: block;
            transform-origin: top left;
            background: #ffffff;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.12), 0 8px 24px rgb(0 0 0 / 0.08);
        }

        .dark .cv-preview__title { color: #ffffff; }
        .dark .cv-preview__scroller { background: #18181b; box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.1); }

        /* Too narrow to sit beside the form: the header's eye action opens the
           same preview in a modal instead. */
        @media (max-width: 1279px) {
            .cv-preview-pane { display: none; }
        }
    </style>
@endonce

<div
    class="cv-preview"
    x-data="{
        scale: 1,
        fit() {
            const width = this.$refs.viewport.clientWidth;
            this.scale = Math.min(1, width / 794);
            this.$refs.frame.style.transform = `scale(${this.scale})`;
            this.sizeToContent();
        },
        sizeToContent() {
            let height = 1123;

            try {
                const doc = this.$refs.frame.contentDocument;
                if (doc) {
                    // Round up to whole A4 pages so a two-page CV reads as two sheets.
                    const pages = Math.max(1, Math.ceil(doc.documentElement.scrollHeight / 1123));
                    height = pages * 1123;
                }
            } catch (e) {
                // Same-origin, so this should not happen; fall back to one page.
            }

            this.$refs.frame.style.height = `${height}px`;
            this.$refs.viewport.style.height = `${Math.ceil(height * this.scale)}px`;
        },
    }"
    x-init="
        $nextTick(() => fit());
        new ResizeObserver(() => fit()).observe($refs.viewport);
    "
>
    <div class="cv-preview__bar">
        <span>
            <span class="cv-preview__title">{{ __('Live preview') }}</span>
            <span class="cv-preview__zoom" x-text="` ${Math.round(scale * 100)}%`"></span>
        </span>

        <a class="cv-preview__open" href="{{ $url }}" target="_blank" rel="noopener">
            {{ __('Open in a new tab') }}
        </a>
    </div>

    <div
        class="cv-preview__scroller"
        style="{{ $standalone ? 'max-height: 78vh' : 'max-height: calc(100vh - 16rem)' }}"
    >
        <div class="cv-preview__viewport" x-ref="viewport">
            <iframe
                class="cv-preview__frame"
                x-ref="frame"
                :src="`{{ $url }}?v=${$wire.cvPreviewHash}`"
                x-on:load="fit()"
                title="{{ __('Live preview') }}"
                loading="eager"
            ></iframe>
        </div>
    </div>
</div>
