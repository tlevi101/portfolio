<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\CvGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
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

        return Storage::disk('public')->response(
            (string) $profile->cv_path,
            'cv.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
