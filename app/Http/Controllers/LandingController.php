<?php

namespace App\Http\Controllers;

use App\Enums\ProjectType;
use App\Models\Portfolio;
use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LandingController extends Controller
{
    public function index(Request $request): View
    {
        $locale = Portfolio::resolveLocale($request->query('lang'));

        return $this->render(Portfolio::default($locale));
    }

    public function show(Request $request, string $slug): View
    {
        $locale = Portfolio::resolveLocale($request->query('lang'));

        $portfolio = Portfolio::query()->where('slug', $slug)->where('locale', $locale)->first()
            ?? Portfolio::query()->where('slug', $slug)->firstOrFail();

        return $this->render($portfolio);
    }

    protected function render(Portfolio $portfolio): View
    {
        App::setLocale($portfolio->locale);

        $selectedProjects = $portfolio->projects()
            ->where('type', ProjectType::Selected)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get();

        $sideProjects = $portfolio->projects()
            ->where('type', ProjectType::SideProject)
            ->orderBy('sort_order')
            ->get();

        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\Skill>> $skills */
        $skills = $portfolio->skills()
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Skill $skill): string => $skill->group->value);

        return view('landing', [
            'profile' => $portfolio,
            'selectedProjects' => $selectedProjects,
            'sideProjects' => $sideProjects,
            'skills' => $skills,
            'availableLocales' => $portfolio->availableLocales(),
            'currentLocale' => $portfolio->locale,
        ]);
    }
}
