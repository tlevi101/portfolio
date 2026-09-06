<?php

namespace App\Http\Controllers;

use App\Services\CvGeneratorService;
use App\Services\CvPreviewBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CvPreviewController extends Controller
{
    /**
     * Render the CV document from the state the admin's CV form last published.
     * The page writes that state to the cache under a key scoped to the
     * signed-in user and the Livewire component id, so one editor can never
     * read another's draft and two open tabs do not overwrite each other.
     *
     * The rendering happens here rather than in the form's own request so the
     * form stays responsive while it is being typed into.
     */
    public function __invoke(
        string $token,
        CvPreviewBuilder $builder,
        CvGeneratorService $generator,
    ): Response {
        $payload = Cache::get('cv-preview:'.Auth::id().':'.$token);

        abort_if(! is_array($payload), 404);

        $cv = $builder->build((array) ($payload['state'] ?? []), $payload['record_id'] ?? null);

        return response($generator->renderHtml($cv))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
