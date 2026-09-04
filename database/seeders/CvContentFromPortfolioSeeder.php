<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Models\Cv;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CvContentFromPortfolioSeeder extends Seeder
{
    /**
     * How many chips the highlighted stack bar is seeded with.
     */
    private const STACK_HIGHLIGHT_LIMIT = 6;

    /**
     * Copy the content a CV used to inherit from its portfolio into the CV's own
     * columns and tables, so the two can be edited separately from here on.
     *
     * Every step is skipped when the CV already has its own value, which makes
     * this safe to re-run: it only ever fills gaps, it never overwrites an edit.
     */
    public function run(): void
    {
        Cv::withoutEvents(function (): void {
            DB::transaction(function (): void {
                Cv::query()->with('portfolio')->each(function (Cv $cv): void {
                    if ($cv->portfolio === null) {
                        return;
                    }

                    $this->copyIdentity($cv, $cv->portfolio);
                    $this->copySkills($cv, $cv->portfolio);
                    $this->copyProjects($cv, $cv->portfolio);
                });
            });
        });
    }

    /**
     * Fill only the identity columns that are still empty on the CV.
     */
    protected function copyIdentity(Cv $cv, Portfolio $portfolio): void
    {
        $inherited = [
            'full_name' => $portfolio->full_name,
            'role' => $portfolio->role,
            'summary' => $portfolio->tagline,
            'email' => $portfolio->email,
            'phone' => $portfolio->phone,
            'location' => $portfolio->location,
            'linkedin_url' => $portfolio->linkedin_url,
            'github_url' => $portfolio->github_url,
            'portfolio_url' => $portfolio->portfolio_url,
            'avatar_path' => $portfolio->avatar_path,
            'languages' => $portfolio->languages,
        ];

        $fill = [];

        foreach ($inherited as $column => $value) {
            if (blank($cv->{$column}) && filled($value)) {
                $fill[$column] = $value;
            }
        }

        if (blank($cv->stack_highlights)) {
            $highlights = $this->deriveStackHighlights($portfolio);

            if ($highlights !== []) {
                $fill['stack_highlights'] = $highlights;
            }
        }

        if ($fill !== []) {
            $cv->forceFill($fill)->saveQuietly();
        }
    }

    protected function copySkills(Cv $cv, Portfolio $portfolio): void
    {
        if ($cv->skills()->exists()) {
            return;
        }

        $portfolio->skills()->orderBy('sort_order')->get()
            ->each(fn (Skill $skill) => $cv->skills()->create([
                'group' => $skill->group->value,
                'name' => $skill->name,
                'sort_order' => $skill->sort_order,
            ]));
    }

    protected function copyProjects(Cv $cv, Portfolio $portfolio): void
    {
        if ($cv->projects()->exists()) {
            return;
        }

        $portfolio->projects()
            ->where('type', ProjectType::Selected)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get()
            ->values()
            ->each(fn (Project $project, int $index) => $cv->projects()->create([
                'title' => $project->title,
                'stack' => $project->stack,
                'sort_order' => $index,
            ]));
    }

    /**
     * Seed the highlighted stack bar with something usable rather than leaving
     * it blank: the featured project's stack first, topped up from the skills
     * list. The owner is expected to rewrite it per job ad.
     *
     * @return array<int, string>
     */
    protected function deriveStackHighlights(Portfolio $portfolio): array
    {
        $fromFeatured = $portfolio->projects()
            ->where('type', ProjectType::Selected)
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->get()
            ->flatMap(fn (Project $project): array => $project->stack ?? []);

        $fromSkills = $portfolio->skills()
            ->orderBy('sort_order')
            ->pluck('name');

        return $fromFeatured
            ->merge($fromSkills)
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->take(self::STACK_HIGHLIGHT_LIMIT)
            ->values()
            ->all();
    }
}
