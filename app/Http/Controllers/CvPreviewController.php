<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CvPreviewController extends Controller
{
    /**
     * Serve the CV document the admin's CV form last rendered from its current
     * state. The page writes it to the cache under a key scoped to the signed-in
     * user and the Livewire component id, so one editor can never read another's
     * draft and two open tabs do not overwrite each other.
     */
    public function __invoke(string $token): Response
    {
        $html = Cache::get('cv-preview:'.Auth::id().':'.$token);

        abort_if($html === null, 404);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
