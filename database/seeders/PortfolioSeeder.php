<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Models\Cv;
use App\Models\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{portfolios: array<int, array<string, mixed>>} $content */
        $content = json_decode(
            File::get(database_path('content/portfolio-content.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($content): void {
            foreach ($content['portfolios'] as $entry) {
                $this->seedPortfolio($entry);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function seedPortfolio(array $entry): void
    {
        // updateOrCreate by (slug, locale) leaves avatar_path untouched since it
        // is not part of the content file and is managed via uploads.
        $portfolio = Portfolio::updateOrCreate(
            ['slug' => $entry['slug'], 'locale' => $entry['locale']],
            [
                ...$entry['profile'],
                'label' => $entry['label'],
                'is_default' => $entry['is_default'],
            ],
        );

        $cv = Cv::updateOrCreate(
            ['portfolio_id' => $portfolio->id],
            [
                'label' => $entry['cv']['label'],
                'locale' => $entry['cv']['locale'],
            ],
        );

        $portfolio->update(['cv_id' => $cv->id]);

        $portfolio->skills()->delete();
        foreach ($entry['skills'] as $skill) {
            $portfolio->skills()->create($skill);
        }

        $portfolio->projects()->delete();
        foreach ($entry['selected_projects'] as $project) {
            $portfolio->projects()->create([...$project, 'type' => ProjectType::Selected]);
        }
        foreach ($entry['side_projects'] as $project) {
            $portfolio->projects()->create([...$project, 'type' => ProjectType::SideProject]);
        }

        $cv->workExperiences()->delete();
        foreach ($entry['cv']['work_experience'] as $job) {
            $cv->workExperiences()->create($job);
        }

        $cv->education()->delete();
        foreach ($entry['cv']['education'] as $education) {
            $cv->education()->create($education);
        }
    }
}
