{{--
    The CV as JSON, ready to hand to an AI.

    Two panes rather than one blob: the document changes every time and is
    copied constantly, while the schema is the same file the tuning skill
    already carries and is only needed when pasting somewhere that has no skill
    loaded.

    Self-contained styling, as with the preview — Filament's stylesheet carries
    no general-purpose utilities to lean on.

    @var string $data   The exported CV document, pretty-printed JSON.
    @var string $schema The generated OpenAPI document, or a hint if it is missing.
--}}
@once
    <style>
        .cv-export { display: flex; flex-direction: column; gap: 0.75rem; }

        .cv-export__tabs { display: flex; gap: 0.5rem; }

        .cv-export__tab {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            border: 0;
            cursor: pointer;
            background: transparent;
            color: var(--gray-500, #71717a);
        }

        .cv-export__tab[aria-selected="true"] {
            background: var(--gray-100, #f4f4f5);
            color: var(--gray-950, #09090b);
        }

        .cv-export__hint { font-size: 0.75rem; color: var(--gray-500, #71717a); margin: 0; }

        .cv-export__area {
            width: 100%;
            height: 55vh;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200, #e4e4e7);
            background: var(--gray-50, #fafafa);
            color: var(--gray-950, #09090b);
            resize: vertical;
            white-space: pre;
            overflow-wrap: normal;
            overflow-x: auto;
        }

        .cv-export__copy {
            align-self: flex-start;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            border: 0;
            cursor: pointer;
            color: #ffffff;
            background: rgb(var(--primary-600, 202 138 4));
        }

        .dark .cv-export__tab[aria-selected="true"] { background: #27272a; color: #ffffff; }
        .dark .cv-export__area { background: #18181b; border-color: #3f3f46; color: #fafafa; }
    </style>
@endonce

<div
    class="cv-export"
    x-data="{
        pane: 'data',
        copied: false,
        copy() {
            const text = this.$refs[this.pane].value;

            // The admin is served over http on a LAN address often enough that
            // the clipboard API is unavailable; falling back keeps the button
            // honest rather than failing silently.
            const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
                return;
            }

            this.$refs[this.pane].select();
            document.execCommand('copy');
            done();
        },
    }"
>
    <div class="cv-export__tabs" role="tablist">
        <button type="button" class="cv-export__tab" role="tab"
                :aria-selected="pane === 'data'" x-on:click="pane = 'data'">
            {{ __('CV document') }}
        </button>
        <button type="button" class="cv-export__tab" role="tab"
                :aria-selected="pane === 'schema'" x-on:click="pane = 'schema'">
            {{ __('Schema') }}
        </button>
    </div>

    <p class="cv-export__hint" x-show="pane === 'data'">
        {{ __('The CV as it stands in the form, unsaved edits included.') }}
    </p>
    <p class="cv-export__hint" x-show="pane === 'schema'" x-cloak>
        {{ __('Only needed when pasting somewhere that has no CV tuning skill loaded.') }}
    </p>

    <textarea class="cv-export__area" x-ref="data" x-show="pane === 'data'"
              readonly spellcheck="false">{{ $data }}</textarea>

    <textarea class="cv-export__area" x-ref="schema" x-show="pane === 'schema'" x-cloak
              readonly spellcheck="false">{{ $schema }}</textarea>

    <button type="button" class="cv-export__copy" x-on:click="copy()">
        <span x-show="! copied">{{ __('Copy') }}</span>
        <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
    </button>
</div>
