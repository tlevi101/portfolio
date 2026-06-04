<?php

namespace App\Services;

use App\Models\Education;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CvGeneratorService
{
    public function generate(): string
    {
        $profile = Profile::singleton();

        /** @var Collection<int, WorkExperience> $workExperiences */
        $workExperiences = WorkExperience::orderBy('sort_order')->get();

        /** @var Collection<int, Education> $educations */
        $educations = Education::orderBy('sort_order')->get();

        /** @var Collection<int, Skill> $skills */
        $skills = Skill::orderBy('sort_order')->get();

        $skillsByGroup = $skills->groupBy(fn (Skill $s): string => $s->group->value);

        /** @var Collection<int, Project> $selectedProjects */
        $selectedProjects = Project::where('type', 'Selected')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get();

        $qr = null;
        if ($profile->portfolio_url) {
            $options = new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
                'outputBase64' => true,
                'scale' => 6,
                'imageTransparent' => false,
            ]);
            $qr = (new QRCode($options))->render($profile->portfolio_url);
        }

        $avatar = null;
        if ($profile->avatar_path && Storage::disk('public')->exists($profile->avatar_path)) {
            $mime = 'image/jpeg';
            $ext = strtolower(pathinfo($profile->avatar_path, PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $mime = 'image/png';
            } elseif ($ext === 'webp') {
                $mime = 'image/webp';
            }
            $avatar = 'data:' . $mime . ';base64,' . base64_encode(
                (string) Storage::disk('public')->get($profile->avatar_path)
            );
        }

        $html = view('cv.template', [
            'profile' => $profile,
            'workExperiences' => $workExperiences,
            'educations' => $educations,
            'skillsByGroup' => $skillsByGroup,
            'selectedProjects' => $selectedProjects,
            'qr' => $qr,
            'avatar' => $avatar,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        Storage::disk('public')->put('cv/cv.pdf', $pdf->output());

        Profile::singleton()->update(['cv_path' => 'cv/cv.pdf']);

        return 'cv/cv.pdf';
    }
}
