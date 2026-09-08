<?php

namespace App\Services;

/**
 * The outcome of reading a tuning document back in: either the form state to
 * fill and the job it was tuned for, or the reasons it was refused. Never both,
 * and never a partial fill — a document that is wrong in one field is not
 * trustworthy in the others, and half an application is worse than none.
 */
readonly class CvImportResult
{
    /**
     * @param  array<string, mixed>|null  $state
     * @param  array<int, string>  $errors
     * @param  array<string, mixed>|null  $jobApplication
     */
    private function __construct(
        public ?array $state,
        public array $errors,
        public ?array $jobApplication = null,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>|null  $jobApplication
     */
    public static function accepted(array $state, ?array $jobApplication = null): self
    {
        return new self($state, [], $jobApplication);
    }

    /**
     * @param  array<int, string>  $errors
     */
    public static function refused(array $errors): self
    {
        return new self(null, array_values($errors));
    }

    public function failed(): bool
    {
        return $this->state === null;
    }
}
