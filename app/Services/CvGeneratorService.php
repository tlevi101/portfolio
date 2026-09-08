<?php

namespace App\Services;

use App\Enums\SkillGroup;
use App\Models\Cv;
use App\Models\CvProject;
use App\Models\CvSkill;
use App\Models\Education;
use App\Models\WorkExperience;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CvGeneratorService
{
    /**
     * Data-URI cache for the current request, so the admin live preview does not
     * re-read and re-encode the same avatar on every keystroke.
     *
     * @var array<string, string|null>
     */
    private array $dataUriCache = [];

    /**
     * Render the CV to a PDF and store it on the public disk.
     */
    public function generateFor(Cv $cv): string
    {
        $pdf = $this->htmlToPdf($this->renderHtml($cv));

        $path = "cv/{$cv->id}-{$cv->locale}.pdf";

        Storage::disk('public')->put($path, $pdf);

        $cv->updateQuietly(['cv_path' => $path]);

        return $path;
    }

    /**
     * The CV as a standalone HTML document. Also used by the admin live preview,
     * which renders it straight into an iframe — the PDF and the preview are
     * therefore always the same markup.
     */
    public function renderHtml(Cv $cv): string
    {
        $previous = App::getLocale();

        App::setLocale($cv->locale ?: config('app.locale'));

        try {
            return view('cv.template', $this->viewData($cv))->render();
        } finally {
            App::setLocale($previous);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(Cv $cv): array
    {
        $portfolioUrl = $cv->portfolioUrl();
        // The QR and the link are tracked; the text under them is not, so the
        // reader is never shown a tracking code.
        $trackedUrl = $cv->trackedPortfolioUrl();

        return [
            'cv' => $cv,
            'workExperiences' => $this->ordered($cv->workExperiences),
            'educations' => $this->ordered($cv->education),
            'skillsByGroup' => $this->ordered($cv->skills)
                ->groupBy(fn (CvSkill $skill): string => $skill->group->value)
                ->sortBy(fn (Collection $skills, string $group): int => SkillGroup::from($group)->sortIndex()),
            'projects' => $this->ordered($cv->projects),
            'stackHighlights' => collect($cv->stack_highlights ?? [])->filter()->values(),
            'languages' => collect($cv->languages ?? [])->filter(fn (array $language): bool => filled($language['name'] ?? null)),
            'portfolioUrl' => $portfolioUrl,
            'trackedUrl' => $trackedUrl,
            'qr' => $trackedUrl !== null ? $this->qrDataUri($trackedUrl) : null,
            'avatar' => $this->avatarDataUri($cv->avatar_path),
        ];
    }

    /**
     * Sort in PHP rather than SQL: the live preview hands us unsaved in-memory
     * collections built from the form state, which have no query to order.
     *
     * @template TModel of WorkExperience|Education|CvSkill|CvProject
     *
     * @param  Collection<int, TModel>  $records
     * @return Collection<int, TModel>
     */
    protected function ordered(Collection $records): Collection
    {
        return $records->sortBy(fn ($record): int => (int) $record->sort_order)->values();
    }

    protected function qrDataUri(string $target): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => 6,
            'imageTransparent' => false,
        ])))->render($target);
    }

    /**
     * Chrome renders from a `Page.setDocumentContent` string, which has no base
     * URL to resolve relative asset paths against, so images must be inlined.
     */
    protected function avatarDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (array_key_exists($path, $this->dataUriCache)) {
            return $this->dataUriCache[$path];
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $this->dataUriCache[$path] = null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return $this->dataUriCache[$path] = 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
    }

    /**
     * Print the document with headless Chrome.
     *
     * The page size and margins come from the stylesheet's `@page` rule
     * (`preferCSSPageSize`), and header/footer printing is off, so Chrome adds
     * no URL or page-number chrome of its own to the sheet.
     */
    protected function htmlToPdf(string $html): string
    {
        $timeout = (int) config('cv.chrome_timeout');

        $browser = $this->launchBrowser();

        try {
            $page = $browser->createPage();
            $page->setHtml($html, $timeout);

            return $page->pdf([
                'printBackground' => true,
                'displayHeaderFooter' => false,
                'preferCSSPageSize' => true,
                'marginTop' => 0,
                'marginBottom' => 0,
                'marginLeft' => 0,
                'marginRight' => 0,
            ])->getRawBinary($timeout);
        } finally {
            $browser->close();
        }
    }

    protected function launchBrowser(): Browser
    {
        $binary = (string) config('cv.chrome_binary');

        if (! is_executable($binary)) {
            throw new RuntimeException(
                "Chrome was not found at [{$binary}]. Install google-chrome-stable (or chromium) ".
                'on this host, or point CHROME_BINARY at the executable.'
            );
        }

        return (new BrowserFactory($binary))->createBrowser([
            'headless' => true,
            // php-fpm and the queue worker run unprivileged in a container where
            // Chrome's sandbox is unavailable; /dev/shm is small there too.
            'noSandbox' => true,
            'startupTimeout' => (int) ceil(config('cv.chrome_timeout') / 1000),
            'envVariables' => ['HOME' => $this->chromeHome()],
            'customFlags' => [
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--font-render-hinting=none',
            ],
        ]);
    }

    /**
     * A writable home directory for the Chrome subprocess.
     *
     * Chrome's crash handler sets itself up from $HOME before it ever looks at
     * --user-data-dir, and php-fpm runs as a user whose home (/var/www) is not
     * writable — where Chrome dies on startup with
     * "chrome_crashpad_handler: --database is required". Neither
     * --disable-crash-reporter nor --crash-dumps-dir avoids it; only a writable
     * HOME does.
     *
     * The directory is per OS user because Chrome creates its dotfiles at 0700:
     * a home shared between the CLI user and the web server user locks out
     * whichever of them renders second.
     */
    protected function chromeHome(): string
    {
        $base = storage_path('framework/chrome');
        $path = $base.'/'.(function_exists('posix_geteuid') ? posix_geteuid() : 'shared');

        foreach ([$base, $path] as $directory) {
            if (is_dir($directory)) {
                continue;
            }

            @mkdir($directory, 0775, true);
            // mkdir's mode is masked by the creating process's umask, and the
            // CLI and php-fpm do not share one; the base has to stay writable
            // for both so each can create its own home under it.
            @chmod($directory, 0775);
        }

        return is_writable($path) ? $path : sys_get_temp_dir();
    }
}
