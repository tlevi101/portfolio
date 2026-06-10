<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Services\CvGeneratorService;
use App\Services\VisitRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $visits->record($request, 'cv_download', slug: $portfolio->slug, locale: $portfolio->locale, referer: $request->headers->get('referer'));

        App::setLocale($portfolio->locale);

        if (! $cv->cv_path || ! Storage::disk('public')->exists($cv->cv_path)) {
            app(CvGeneratorService::class)->generateFor($cv);
            $cv->refresh();
        }

        $name = Str::of((string) $portfolio->full_name)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        $filename = ($name !== '' ? $name : 'cv').'_CV.pdf';

        return Storage::disk('public')->response(
            (string) $cv->cv_path,
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store, max-age=0',
            ]
        );
    }
}
