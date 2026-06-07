<?php

namespace App\Services;

use App\Enums\ProjectType;
use App\Models\Cv;
use App\Models\Education;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class CvGeneratorService
{
    /**
     * Render and store the PDF for a specific CV, inheriting identity, skills
     * and selected projects from its portfolio and using its own work
     * experience and education entries.
     */
    public function generateFor(Cv $cv): string
    {
        $portfolio = $cv->portfolio;

        if ($portfolio === null) {
            throw new \RuntimeException("CV #{$cv->id} has no linked portfolio.");
        }

        /** @var Collection<int, WorkExperience> $workExperiences */
        $workExperiences = $cv->workExperiences()->orderBy('sort_order')->get();

        /** @var Collection<int, Education> $educations */
        $educations = $cv->education()->orderBy('sort_order')->get();

        /** @var Collection<int, Skill> $skills */
        $skills = $portfolio->skills()->orderBy('sort_order')->get();

        $skillsByGroup = $skills->groupBy(fn (Skill $s): string => $s->group->value);

        /** @var Collection<int, Project> $selectedProjects */
        $selectedProjects = $portfolio->projects()
            ->where('type', ProjectType::Selected)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get();

        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => true,
            'scale' => 6,
            'imageTransparent' => false,
        ]);
        $qrTarget = $portfolio->portfolio_url ?: route('portfolio.show', $portfolio->slug);
        $qr = (new QRCode($options))->render($qrTarget);

        $avatar = null;
        if ($portfolio->avatar_path && Storage::disk('public')->exists($portfolio->avatar_path)) {
            $mime = 'image/jpeg';
            $ext = strtolower(pathinfo($portfolio->avatar_path, PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $mime = 'image/png';
            } elseif ($ext === 'webp') {
                $mime = 'image/webp';
            }
            $avatar = 'data:'.$mime.';base64,'.base64_encode(
                (string) Storage::disk('public')->get($portfolio->avatar_path)
            );
        }

        App::setLocale($cv->locale ?: $portfolio->locale);

        $html = view('cv.template', [
            'profile' => $portfolio,
            'workExperiences' => $workExperiences,
            'educations' => $educations,
            'skillsByGroup' => $skillsByGroup,
            'selectedProjects' => $selectedProjects,
            'qr' => $qr,
            'avatar' => $avatar,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $path = "cv/{$portfolio->slug}-{$portfolio->locale}.pdf";

        Storage::disk('public')->put($path, $pdf->output());

        $cv->updateQuietly(['cv_path' => $path]);

        return $path;
    }
}
