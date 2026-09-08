<?php

namespace App\Services;

/**
 * The outcome of reading a CV document back in: either form state to fill, or
 * the reasons it was refused. Never both, and never a partial fill — a document
 * that is wrong in one field is not trustworthy in the others.
 */
readonly class CvImportResult
{
    /**
     * @param  array<string, mixed>|null  $state
     * @param  array<int, string>  $errors
     */
    private function __construct(
        public ?array $state,
        public array $errors,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     */
    public static function accepted(array $state): self
    {
        return new self($state, []);
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
