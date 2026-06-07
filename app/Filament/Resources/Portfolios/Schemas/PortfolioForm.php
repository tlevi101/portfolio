<?php

namespace App\Filament\Resources\Portfolios\Schemas;

use App\Enums\ProjectType;
use App\Enums\SkillGroup;
use App\Models\Portfolio;
use App\Services\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PortfolioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Portfolio')
                    ->tabs([
                        Tab::make(__('Settings'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('Label'))
                                    ->required()
                                    ->helperText(__('Admin-only name, e.g. "Full-stack" or "Java Junior".')),
                                Select::make('locale')
                                    ->label(__('Language'))
                                    ->options(Portfolio::LOCALES)
                                    ->required()
                                    ->default(config('app.locale')),
                                TextInput::make('slug')
                                    ->label(__('Slug'))
                                    ->required()
                                    ->helperText(__('Used in the URL: /{slug}'))
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('locale', $get('locale')),
                                    ),
                                Toggle::make('is_default')
                                    ->label(__('Default'))
                                    ->helperText(__('The default portfolio renders at / for its language.')),
                                Select::make('cv_id')
                                    ->label(__('Download CV'))
                                    ->relationship(
                                        name: 'cv',
                                        titleAttribute: 'label',
                                        modifyQueryUsing: fn (Builder $query, ?Portfolio $record): Builder => $query->where('portfolio_id', $record?->id),
                                    )
                                    ->helperText(__('Served by the "Download CV" button. Create the CV first, then select it here.'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('Hero'))
                            ->schema([
                                TextInput::make('full_name')->label(__('Full name'))->required(),
                                TextInput::make('role')->label(__('Role'))->required(),
                                TextInput::make('tagline')->label(__('Tagline'))->required()->columnSpanFull(),
                                TextInput::make('hero_eyebrow')->label(__('Hero eyebrow')),
                                TextInput::make('location')->label(__('Location'))->required(),
                                TextInput::make('phone')->label(__('Phone')),
                                TextInput::make('portfolio_url')->label(__('Portfolio URL'))->url(),
                                Toggle::make('available')->label(__('Available')),
                                TextInput::make('available_text')->label(__('Available text'))->placeholder(__('Open to work')),
                                FileUpload::make('avatar_path')
                                    ->label(__('Avatar'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('profile')
                                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                        $optimized = app(ImageOptimizer::class)->optimizeToJpeg($file->get());
                                        $path = 'profile/'.Str::ulid()->toString().'.jpg';
                                        Storage::disk('public')->put($path, $optimized);

                                        return $path;
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('Projects'))
                            ->schema([
                                TextInput::make('projects_heading')->label(__('Projects heading'))->columnSpanFull(),
                                TextInput::make('projects_subheading')->label(__('Projects subheading'))->columnSpanFull(),
                                Repeater::make('selectedProjects')
                                    ->label(__('Selected projects'))
                                    ->relationship(
                                        'projects',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', ProjectType::Selected->value),
                                    )
                                    ->schema([
                                        Hidden::make('type')->default(ProjectType::Selected->value),
                                        TextInput::make('title')->label(__('Title'))->required(),
                                        Toggle::make('featured')->label(__('Featured')),
                                        TextInput::make('summary')->label(__('Summary'))->required()->columnSpanFull(),
                                        TextInput::make('problem')->label(__('Problem'))->columnSpanFull(),
                                        TextInput::make('role_description')->label(__('Role description'))->columnSpanFull(),
                                        TextInput::make('outcome')->label(__('Outcome'))->columnSpanFull(),
                                        TagsInput::make('stack')->label(__('Stack'))->placeholder(__('Add technology'))->columnSpanFull(),
                                        TextInput::make('url')->label(__('Link'))->url()->columnSpanFull(),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Experiments'))
                            ->schema([
                                TextInput::make('experiments_heading')->label(__('Experiments heading'))->columnSpanFull(),
                                Textarea::make('experiments_intro')->label(__('Experiments intro'))->rows(3)->columnSpanFull(),
                                Repeater::make('sideProjects')
                                    ->label(__('Side projects'))
                                    ->relationship(
                                        'projects',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', ProjectType::SideProject->value),
                                    )
                                    ->schema([
                                        Hidden::make('type')->default(ProjectType::SideProject->value),
                                        TextInput::make('title')->label(__('Title'))->required(),
                                        TextInput::make('summary')->label(__('Summary'))->required()->columnSpanFull(),
                                        TagsInput::make('stack')->label(__('Stack'))->placeholder(__('Add technology'))->columnSpanFull(),
                                        TextInput::make('url')->label(__('Link'))->url()->columnSpanFull(),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('About'))
                            ->schema([
                                TextInput::make('about_heading')->label(__('About heading'))->columnSpanFull(),
                                Textarea::make('about')->label(__('About'))->rows(5)->required()->columnSpanFull(),
                                Repeater::make('experience_highlights')
                                    ->label(__('Experience highlights'))
                                    ->simple(TextInput::make('item')->label(__('Highlight')))
                                    ->columnSpanFull(),
                                Repeater::make('skills')
                                    ->label(__('Skills'))
                                    ->relationship()
                                    ->schema([
                                        Select::make('group')
                                            ->label(__('Group'))
                                            ->options(collect(SkillGroup::cases())->mapWithKeys(
                                                fn (SkillGroup $group): array => [$group->value => $group->label()]
                                            ))
                                            ->required(),
                                        TextInput::make('name')->label(__('Skill name'))->required(),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Contact'))
                            ->schema([
                                TextInput::make('contact_heading')->label(__('Contact heading'))->columnSpanFull(),
                                Textarea::make('contact_intro')->label(__('Contact intro'))->rows(3)->columnSpanFull(),
                                TextInput::make('email')->label(__('Email'))->email()->required(),
                                TextInput::make('linkedin_url')->label(__('LinkedIn URL'))->url(),
                                TextInput::make('github_url')->label(__('GitHub URL'))->url(),
                            ])
                            ->columns(2),

                        Tab::make(__('Footer'))
                            ->schema([
                                TextInput::make('footer_text')->label(__('Footer text'))->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
