<?php

namespace App\Http\Controllers;

use App\Enums\VisitEvent;
use App\Models\Portfolio;
use App\Services\CvGeneratorService;
use App\Services\VisitRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    public function download(Request $request, string $slug, VisitRecorder $visits): Response|StreamedResponse
    {
        $locale = Portfolio::resolveLocale($request->query('lang'));

        $portfolio = Portfolio::query()->where('slug', $slug)->where('locale', $locale)->first()
            ?? Portfolio::query()->where('slug', $slug)->firstOrFail();

        $cv = $portfolio->cv ?? $portfolio->cvs()->first();

        abort_if($cv === null, 404);

        $visits->record($request, VisitEvent::CvDownload->value, slug: $portfolio->slug, locale: $portfolio->locale, referer: $request->headers->get('referer'));

        App::setLocale($cv->locale ?: $portfolio->locale);

        if (! $cv->cv_path || ! Storage::disk('public')->exists($cv->cv_path)) {
            app(CvGeneratorService::class)->generateFor($cv);
            $cv->refresh();
        }

        return Storage::disk('public')->response(
            (string) $cv->cv_path,
            $cv->downloadFilename(),
            [
                'Content-Type' => 'application/pdf',
                // Belt and braces against phone browsers and download managers
                // holding on to an older build; the `v` parameter on the link
                // (Portfolio::cvDownloadUrl) is what actually forces a refetch.
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}
