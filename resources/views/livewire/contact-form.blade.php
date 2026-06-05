<div>
    @if ($submitted)
        <div class="contact-success">
            <p>{{ __("Thanks for reaching out — I'll get back to you soon.") }}</p>
        </div>
    @else
        <form wire:submit="submit" class="contact-form-fields" novalidate>
            <div style="position: absolute; left: -9999px;" aria-hidden="true">
                <label for="cf-website">Website</label>
                <input
                    id="cf-website"
                    type="text"
                    wire:model="website"
                    tabindex="-1"
                    autocomplete="off"
                >
            </div>

            <div class="form-field">
                <label for="cf-name" class="label">{{ __('Name') }}</label>
                <input
                    id="cf-name"
                    type="text"
                    wire:model="name"
                    autocomplete="name"
                    class="form-input @error('name') form-input--error @enderror"
                >
                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-field">
                <label for="cf-email" class="label">{{ __('Email') }}</label>
                <input
                    id="cf-email"
                    type="email"
                    wire:model="email"
                    autocomplete="email"
                    class="form-input @error('email') form-input--error @enderror"
                >
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-field">
                <label for="cf-message" class="label">{{ __('Message') }}</label>
                <textarea
                    id="cf-message"
                    wire:model="message"
                    rows="5"
                    class="form-input @error('message') form-input--error @enderror"
                ></textarea>
                @error('message')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="button button-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Send message') }}</span>
                <span wire:loading>{{ __('Sending…') }}</span>
            </button>
        </form>
    @endif
</div>
