<?php

namespace App\Console\Commands;

use App\Services\CvSchema;
use Illuminate\Console\Command;

/**
 * Write the CV contract out for the AI tuning skill to reference.
 *
 * The file is generated rather than maintained so it cannot drift from the form
 * that enforces the same limits; a test regenerates it and fails if the copy in
 * the repository is stale.
 */
class GenerateCvSchema extends Command
{
    protected $signature = 'cv:schema {--path= : Where to write the document}';

    protected $description = 'Generate the OpenAPI document describing the CV export format';

    /**
     * Where the tuning skill looks for it.
     */
    public const DEFAULT_PATH = '.claude/skills/cv-tuning/cv.openapi.yaml';

    public function handle(CvSchema $schema): int
    {
        $path = $this->option('path') ?: base_path(self::DEFAULT_PATH);

        if (! is_dir($directory = dirname($path))) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, self::toYaml($schema->toArray()));

        $this->info("CV schema written to {$path}");

        return self::SUCCESS;
    }

    /**
     * A YAML dump of the schema document.
     *
     * Hand-rolled because symfony/yaml is a development-only dependency here and
     * this covers only what `CvSchema` emits: nested maps, lists, and scalars.
     * Strings are always double-quoted, which needs no judgement about when a
     * bare scalar would be misread as a number, a boolean or a date.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function toYaml(array $data, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $lines = [];
        $isList = array_is_list($data);

        foreach ($data as $key => $value) {
            $prefix = $isList ? $indent.'-' : $indent.self::key((string) $key).':';

            if (is_array($value) && $value !== []) {
                // A nested list item opens on the same line as its dash, so the
                // child block is indented relative to the dash, not the parent.
                $lines[] = $prefix;
                $lines[] = rtrim(self::toYaml($value, $depth + 1), "\n");

                continue;
            }

            $lines[] = $prefix.' '.(is_array($value) ? '[]' : self::scalar($value));
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * A mapping key. Left bare when it reads as a plain identifier — which every
     * key in this document does — so the file stays legible to whoever, or
     * whatever, is reading it.
     */
    protected static function key(string $key): string
    {
        $reserved = ['true', 'false', 'null', 'yes', 'no', 'on', 'off', 'y', 'n'];

        return preg_match('/^[A-Za-z_$][A-Za-z0-9_$-]*$/', $key) === 1
            && ! in_array(strtolower($key), $reserved, true)
                ? $key
                : self::scalar($key);
    }

    /**
     * One YAML scalar. Everything that is not a number, a boolean or null comes
     * out double-quoted and escaped.
     */
    protected static function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) || is_float($value) => (string) $value,
            default => json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
