<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Models\Education;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $content */
        $content = json_decode(
            File::get(database_path('content/portfolio-content.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($content) {
            // Profile is a singleton; updateOrCreate leaves avatar_path and
            // cv_path untouched since they are not part of the content file.
            Profile::updateOrCreate(['id' => 1], $content['profile']);

            Skill::query()->delete();
            foreach ($content['skills'] as $skill) {
                Skill::create($skill);
            }

            Project::query()->delete();
            foreach ($content['selected_projects'] as $project) {
                Project::create([...$project, 'type' => ProjectType::Selected]);
            }
            foreach ($content['side_projects'] as $project) {
                Project::create([...$project, 'type' => ProjectType::SideProject]);
            }

            WorkExperience::query()->delete();
            foreach ($content['work_experience'] as $job) {
                WorkExperience::create($job);
            }

            Education::query()->delete();
            foreach ($content['education'] as $education) {
                Education::create($education);
            }
        });
    }
}
