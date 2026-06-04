<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $profile = Profile::singleton();

        $selectedProjects = Project::query()
            ->where('type', ProjectType::Selected)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get();

        $sideProjects = Project::query()
            ->where('type', ProjectType::SideProject)
            ->orderBy('sort_order')
            ->get();

        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Skill>> $skills */
        $skills = Skill::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Skill $skill): string => $skill->group->value);

        return view('landing', compact('profile', 'selectedProjects', 'sideProjects', 'skills'));
    }
}
