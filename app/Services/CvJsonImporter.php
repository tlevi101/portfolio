<?php

namespace App\Services;

use App\Enums\SkillGroup;
use App\Models\Cv;
use Illuminate\Support\Str;

/**
 * Reads a CV document back into the form it was exported from.
 *
 * The inverse of `CvJsonExporter`, and the fussier direction: the form keeps
 * repeater rows keyed by an id of its own, nests a `simple()` repeater's values
 * under `item`, and splits skills across one list per group. All of that has to
 * be rebuilt from a flat document written by something that has never seen the
 * form.
 *
 * Three rules govern what comes back in:
 *
 *  - A field the document does not mention is left as it is, so retuning one
 *    section does not require sending the whole CV back.
 *  - Fields marked `readOnly` are discarded rather than trusted; the form's own
 *    values win.
 *  - `id` is the exception to the first rule. Without it there is no way to tell
 *    whether the document belongs here, so its absence is refused outright
 *    rather than assumed away.
 */
class CvJsonImporter
{
    /**
     * Document fields that are written back, mapped to the form key that holds
     * them. Everything else the schema declares is readOnly and discarded.
     */
    private const WRITABLE = [
        'label' => 'label',
        'role' => 'role',
        'summary' => 'summary',
        'stack_highlights' => 'stack_highlights',
        'skills' => 'skills',
        'experience' => 'workExperiences',
        'projects' => 'projects',
        'education' => 'education',
    ];

    public function __construct(private readonly CvSchema $schema) {}

    /**
     * Merge a document into the form's current state.
     *
     * @param  array<string, mixed>  $current
     */
    public function merge(string $json, Cv $target, array $current): CvImportResult
    {
        $document = $this->decode($json);

        if ($document === null) {
            return CvImportResult::refused([__('That is not valid JSON. Paste the document exactly as it came back, including the outer braces.')]);
        }

        if ($errors = $this->guard($document, $target)) {
            return CvImportResult::refused($errors);
        }

        if ($errors = $this->validate($document)) {
            return CvImportResult::refused($errors);
        }

        return CvImportResult::accepted($this->apply($document, $current));
    }

    /**
     * Read the JSON out of whatever it arrived wrapped in — a fenced code block,
     * a sentence of preamble, a byte order mark — and forgive a trailing comma.
     *
     * @return array<string, mixed>|null
     */
    protected function decode(string $input): ?array
    {
        $text = trim(str_replace("\u{FEFF}", '', $input));

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $text, $matches) === 1) {
            $text = $matches[1];
        }

        // Prose either side of the document is common enough to be worth
        // stepping over; anything between the outer braces has to stand alone.
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $text = substr($text, $start, $end - $start + 1);
        $text = (string) preg_replace('/,(\s*[}\]])/', '$1', $text);

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Refuse a document that belongs to another CV.
     *
     * A document exported from a master is valid for any variant cut from it —
     * exporting first and creating the variant afterwards is a natural order to
     * work in, and every variant starts life as a copy of its master.
     *
     * @param  array<string, mixed>  $document
     * @return array<int, string>
     */
    protected function guard(array $document, Cv $target): array
    {
        $id = $document['id'] ?? null;

        if (! is_int($id)) {
            return [__('The document has no "id", so there is no way to tell which CV it belongs to. Export the CV again and retune that document.')];
        }

        if ($id !== $target->getKey() && $id !== $target->parent_id) {
            $source = Cv::query()->find($id);

            return [__('This document belongs to :source, but you are editing :target. Open that CV, or one of its variants, to import it.', [
                'source' => $source->label ?? __('CV #:id', ['id' => $id]),
                'target' => $target->label ?? __('CV #:id', ['id' => $target->getKey()]),
            ])];
        }

        if (isset($document['locale']) && $document['locale'] !== $target->locale) {
            return [__('The document is written in ":document" but this CV is in ":target".', [
                'document' => (string) $document['locale'],
                'target' => (string) $target->locale,
            ])];
        }

        return [];
    }

    /**
     * Check the document against the contract it was written to.
     *
     * @param  array<string, mixed>  $document
     * @return array<int, string>
     */
    protected function validate(array $document): array
    {
        $schemas = $this->schema->toArray()['components']['schemas'];
        $properties = $schemas['Cv']['properties'];
        $errors = [];

        foreach ($document as $key => $value) {
            if (! array_key_exists($key, $properties)) {
                // A renamed key would otherwise be dropped in silence, leaving
                // the section it was meant to replace untouched.
                $errors[] = __('Unknown field ":field". The document must use the field names in the schema.', ['field' => (string) $key]);

                continue;
            }

            if (! isset(self::WRITABLE[$key])) {
                continue;
            }

            $errors = [...$errors, ...$this->check($value, $properties[$key], $schemas, (string) $key)];
        }

        return $errors;
    }

    /**
     * One value against one schema definition, recursing into arrays, objects
     * and `$ref`s.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $schemas
     * @return array<int, string>
     */
    protected function check(mixed $value, array $definition, array $schemas, string $path): array
    {
        if (isset($definition['$ref'])) {
            $definition = $schemas[basename((string) $definition['$ref'])] ?? [];
        }

        if ($value === null) {
            return [];
        }

        return match ($definition['type'] ?? null) {
            'string' => $this->checkString($value, $definition, $path),
            'array' => $this->checkArray($value, $definition, $schemas, $path),
            'object' => $this->checkObject($value, $definition, $schemas, $path),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    protected function checkString(mixed $value, array $definition, string $path): array
    {
        if (! is_string($value)) {
            return [__(':field must be text.', ['field' => $path])];
        }

        $errors = [];
        $limit = $definition['maxLength'] ?? null;

        if (is_int($limit) && mb_strlen($value) > $limit) {
            $errors[] = __(':field is :length characters; the limit is :limit.', [
                'field' => $path,
                'length' => mb_strlen($value),
                'limit' => $limit,
            ]);
        }

        if (isset($definition['enum']) && ! in_array($value, $definition['enum'], true)) {
            $errors[] = __(':field must be one of: :allowed.', [
                'field' => $path,
                'allowed' => implode(', ', $definition['enum']),
            ]);
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $schemas
     * @return array<int, string>
     */
    protected function checkArray(mixed $value, array $definition, array $schemas, string $path): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return [__(':field must be a list.', ['field' => $path])];
        }

        $errors = [];

        foreach ($value as $index => $item) {
            $errors = [...$errors, ...$this->check($item, $definition['items'] ?? [], $schemas, "{$path}[{$index}]")];
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $schemas
     * @return array<int, string>
     */
    protected function checkObject(mixed $value, array $definition, array $schemas, string $path): array
    {
        if (! is_array($value) || array_is_list($value)) {
            return [__(':field must be an object.', ['field' => $path])];
        }

        $errors = [];
        $properties = $definition['properties'] ?? [];

        foreach ($value as $key => $item) {
            if (! array_key_exists($key, $properties)) {
                $errors[] = __('Unknown field ":field".', ['field' => "{$path}.{$key}"]);

                continue;
            }

            $errors = [...$errors, ...$this->check($item, $properties[$key], $schemas, "{$path}.{$key}")];
        }

        return $errors;
    }

    /**
     * Write the document's writable fields over the form's current state.
     *
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    protected function apply(array $document, array $current): array
    {
        $state = $current;

        foreach (['label', 'role', 'summary'] as $field) {
            if (array_key_exists($field, $document)) {
                $state[$field] = $document[$field];
            }
        }

        if (array_key_exists('stack_highlights', $document)) {
            $state['stack_highlights'] = array_values((array) $document['stack_highlights']);
        }

        if (isset($document['skills']) && is_array($document['skills'])) {
            foreach (SkillGroup::cases() as $group) {
                if (! array_key_exists($group->value, $document['skills'])) {
                    continue;
                }

                $state['skills'.$group->value] = $this->repeater(
                    array_map(
                        fn ($name): array => ['group' => $group->value, 'name' => (string) $name],
                        (array) $document['skills'][$group->value],
                    ),
                );
            }
        }

        if (array_key_exists('experience', $document)) {
            $state['workExperiences'] = $this->repeater(array_map(
                fn ($row): array => [
                    ...$this->fields($row, ['company', 'title', 'period', 'location']),
                    'bullets' => $this->repeater(array_map(
                        fn ($bullet): array => ['item' => (string) $bullet],
                        array_values((array) (is_array($row) ? ($row['bullets'] ?? []) : [])),
                    )),
                ],
                array_values((array) $document['experience']),
            ));
        }

        if (array_key_exists('projects', $document)) {
            $state['projects'] = $this->repeater(array_map(
                fn ($row): array => [
                    ...$this->fields($row, ['title']),
                    'stack' => array_values((array) (is_array($row) ? ($row['stack'] ?? []) : [])),
                ],
                array_values((array) $document['projects']),
            ));
        }

        if (array_key_exists('education', $document)) {
            $state['education'] = $this->repeater(array_map(
                fn ($row): array => $this->fields($row, ['school', 'degree', 'start_year', 'graduation_year', 'location']),
                array_values((array) $document['education']),
            ));
        }

        return $state;
    }

    /**
     * A row reduced to the fields the form holds.
     *
     * A value the document leaves out arrives as null rather than being guessed
     * at. Where the form requires the field, its own validation says so on save,
     * which is a better place to hear it than a database error.
     *
     * @param  array<int, string>  $fields
     * @return array<string, string|null>
     */
    protected function fields(mixed $row, array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $value = is_array($row) ? ($row[$field] ?? null) : null;
            $values[$field] = is_scalar($value) ? (string) $value : null;
        }

        return $values;
    }

    /**
     * Key a list of rows the way a repeater holds them.
     *
     * The keys are new every time: the document carries no row ids, so there is
     * nothing to match existing rows against. Saving therefore replaces the
     * CV's rows rather than updating them, which is invisible in the CV itself —
     * nothing refers to those rows from outside it.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    protected function repeater(array $rows): array
    {
        $keyed = [];

        foreach ($rows as $row) {
            $keyed[(string) Str::uuid()] = $row;
        }

        return $keyed;
    }
}
