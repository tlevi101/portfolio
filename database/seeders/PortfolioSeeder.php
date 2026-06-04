<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Enums\SkillGroup;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        Profile::firstOrCreate(['id' => 1], [
            'full_name' => 'Torma Levente',
            'role' => 'Laravel + React Full-Stack Developer',
            'tagline' => 'Building practical web applications end to end.',
            'hero_eyebrow' => 'Portfolio / job search / 2026',
            'available' => true,
            'available_text' => 'Open to work',
            'location' => 'Budapest, Hungary',
            'projects_heading' => 'A few things worth opening first.',
            'projects_subheading' => 'Selected professional work, ordered by what shows the most.',
            'experiments_heading' => 'Smaller work, different energy.',
            'experiments_intro' => 'Outside of client and production work, I also build small side projects and experiments to explore ideas, tools, and different kinds of software.',
            'about_heading' => 'More interested in useful software than clever noise.',
            'about' => 'Full-stack developer with a backend-leaning mindset, comfortable moving from database and API design to React interfaces. Enjoys building maintainable features, improving existing systems, and turning business requirements into clean, usable products.',
            'experience_highlights' => [
                'Built and maintained business web applications with Laravel and React',
                'Worked across backend and frontend features end to end',
                'Comfortable joining existing codebases and improving them incrementally',
            ],
            'contact_heading' => 'Open to hearing about good work.',
            'contact_intro' => 'If you have a role or project that fits, reach out directly.',
            'email' => 'leventetorma3@gmail.com',
            'footer_text' => 'Torma Levente — Laravel + React full-stack developer',
        ]);

        Project::firstOrCreate(
            ['title' => 'Main Professional Project'],
            [
                'type' => ProjectType::Selected,
                'featured' => true,
                'summary' => 'A business web application built and maintained end to end.',
                'problem' => 'Replace with one clear sentence about the problem solved.',
                'role_description' => 'Replace with your actual contribution and ownership.',
                'outcome' => 'Replace with a result, improvement, or measurable impact.',
                'stack' => ['Laravel', 'React', 'MySQL', 'Docker'],
                'sort_order' => 1,
            ]
        );

        Project::firstOrCreate(
            ['title' => 'Second Professional Project'],
            [
                'type' => ProjectType::Selected,
                'featured' => false,
                'summary' => 'Placeholder summary for a strong secondary project.',
                'stack' => ['PHP', 'React', 'REST API'],
                'sort_order' => 2,
            ]
        );

        Project::firstOrCreate(
            ['title' => 'Third Professional Project'],
            [
                'type' => ProjectType::Selected,
                'featured' => false,
                'summary' => 'Placeholder summary showing a different kind of responsibility.',
                'stack' => ['Laravel', 'Livewire', 'Alpine.js'],
                'sort_order' => 3,
            ]
        );

        Project::firstOrCreate(
            ['title' => 'Snake Game in C# WPF'],
            [
                'type' => ProjectType::SideProject,
                'featured' => false,
                'summary' => 'A desktop Snake game — curiosity outside the browser.',
                'stack' => ['C#', 'WPF', 'Game logic'],
                'sort_order' => 1,
            ]
        );

        $skills = [
            [SkillGroup::Backend, 'Laravel', 1],
            [SkillGroup::Backend, 'PHP', 2],
            [SkillGroup::Backend, 'MySQL', 3],
            [SkillGroup::Backend, 'REST API design', 4],
            [SkillGroup::Frontend, 'React', 1],
            [SkillGroup::Frontend, 'JavaScript / TypeScript', 2],
            [SkillGroup::Frontend, 'HTML & CSS', 3],
            [SkillGroup::Tools, 'Git', 1],
            [SkillGroup::Tools, 'Docker', 2],
            [SkillGroup::Tools, 'Linux', 3],
            [SkillGroup::Tools, 'CI/CD', 4],
            [SkillGroup::Other, 'C#', 1],
            [SkillGroup::Other, 'WPF', 2],
        ];

        foreach ($skills as [$group, $name, $order]) {
            Skill::firstOrCreate(
                ['group' => $group, 'name' => $name],
                ['sort_order' => $order]
            );
        }
    }
}
