<?php

namespace App\Livewire;

use App\Models\ContactSubmission;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ContactForm extends Component
{
    #[Validate('required|string|min:2|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:10|max:2000')]
    public string $message = '';

    /**
     * Honeypot field. Hidden from real users; bots tend to fill every input.
     * A non-empty value marks the submission as spam.
     */
    public string $website = '';

    public bool $submitted = false;

    public function submit(): void
    {
        // Silently accept-and-drop submissions that trip the honeypot, so bots
        // can't distinguish a rejected attempt from a successful one.
        if ($this->website !== '') {
            $this->submitted = true;
            $this->reset('name', 'email', 'message', 'website');

            return;
        }

        $this->validate();

        $rateLimitKey = 'contact-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $this->addError('message', __('Too many messages sent. Please try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($rateLimitKey),
            ]));

            return;
        }

        RateLimiter::hit($rateLimitKey, decaySeconds: 3600);

        ContactSubmission::create([
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
        ]);

        $this->submitted = true;
        $this->reset('name', 'email', 'message');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.contact-form');
    }
}
