<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Cv;
use App\Models\JobApplication;
use Illuminate\Support\Carbon;

/**
 * Records the job half of a tuning document.
 *
 * Kept apart from `CvJsonImporter` because the two halves land in different
 * places: the CV is merged into unsaved form state for the admin to look at
 * before committing to it, while the application is a record of something that
 * has already happened and is written straight away.
 */
class JobApplicationImporter
{
    /**
     * Create or update the application a document describes.
     *
     * An `id` that belongs to this CV updates that record. An `id` that belongs
     * to some other CV — the usual cause being a document exported from a
     * master before the variant was cut — starts a new record on this one
     * rather than moving an application between CVs behind the user's back.
     *
     * @param  array<string, mixed>  $document
     */
    public function apply(array $document, Cv $cv): JobApplication
    {
        $application = $this->existing($document, $cv) ?? new JobApplication([
            'cv_id' => $cv->getKey(),
            // Freshly imported means freshly sent; everything after that is the
            // admin's to move along by hand.
            'status' => ApplicationStatus::Pending,
        ]);

        $application->fill($this->attributes($document));
        $application->cv_id = $cv->getKey();
        $application->applied_at ??= now();
        $application->save();

        return $application;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function existing(array $document, Cv $cv): ?JobApplication
    {
        $id = $document['id'] ?? null;

        if (! is_int($id)) {
            return null;
        }

        return JobApplication::query()->whereKey($id)->where('cv_id', $cv->getKey())->first();
    }

    /**
     * The document's fields as model attributes.
     *
     * Only what the document mentions is written, so a second pass that says
     * nothing about, say, the salary notes does not erase them.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    protected function attributes(array $document): array
    {
        $attributes = [];

        foreach (['company', 'title', 'method', 'method_detail', 'source_url', 'location', 'experience_level', 'job_ad', 'notes'] as $field) {
            if (array_key_exists($field, $document)) {
                $attributes[$field] = $this->text($document[$field]);
            }
        }

        if (array_key_exists('required_years', $document)) {
            $attributes['required_years'] = is_numeric($document['required_years']) ? (int) $document['required_years'] : null;
        }

        if (array_key_exists('required_skills', $document)) {
            $attributes['required_skills'] = $this->requiredSkills($document['required_skills']);
        }

        if (array_key_exists('applied_at', $document)) {
            $attributes['applied_at'] = $this->date($document['applied_at']);
        }

        return $attributes;
    }

    /**
     * @return array<int, array{name: string, years: int|null}>
     */
    protected function requiredSkills(mixed $skills): array
    {
        return collect(is_array($skills) ? $skills : [])
            ->filter(fn ($skill): bool => is_array($skill) && filled($skill['name'] ?? null))
            ->map(fn (array $skill): array => [
                'name' => (string) $skill['name'],
                'years' => isset($skill['years']) && is_numeric($skill['years']) ? (int) $skill['years'] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * A date the document gave, or null if it gave something that is not one.
     * An unparseable date is better dropped than allowed to become "today" and
     * pass for a fact.
     */
    protected function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function text(mixed $value): ?string
    {
        return is_scalar($value) && filled($value) ? (string) $value : null;
    }
}
