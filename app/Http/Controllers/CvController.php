<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\CvGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    public function download(): Response|StreamedResponse
    {
        $profile = Profile::singleton();

        if (! $profile->cv_path || ! Storage::disk('public')->exists($profile->cv_path)) {
            app(CvGeneratorService::class)->generate();
            $profile->refresh();
        }

        $name = Str::of((string) $profile->full_name)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        $filename = ($name !== '' ? $name : 'cv').'_CV.pdf';

        return Storage::disk('public')->response(
            (string) $profile->cv_path,
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
