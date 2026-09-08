<?php

namespace App\Filament\Resources\JobApplications\Schemas;

use App\Enums\ApplicationMethod;
use App\Enums\ApplicationStatus;
use App\Enums\ExperienceLevel;
use App\Models\Cv;
use App\Models\JobApplication;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

/**
 * Almost everything here is optional, and the form says so by not marking it
 * required. Job ads leave most of this out, and an admin that insists on a
 * seniority or a years figure only teaches you to make one up.
 */
class JobApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Application'))
                ->description(__('Where it went, and where it got to.'))
                ->schema([
                    TextInput::make('company')
                        ->label(__('Company'))
                        ->required()
                        ->maxLength(JobApplication::TEXT_LIMIT),
                    TextInput::make('title')
                        ->label(__('Advertised role'))
                        ->maxLength(JobApplication::TEXT_LIMIT),
                    Select::make('cv_id')
                        ->label(__('CV sent'))
                        ->options(fn (): array => Cv::query()
                            ->orderBy('label')
                            ->pluck('label', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('Visitors are attributed through this CV\'s printed link.')),
                    Select::make('status')
                        ->label(__('Status'))
                        ->options(ApplicationStatus::options())
                        ->default(ApplicationStatus::Pending->value)
                        ->required(),
                    Select::make('method')
                        ->label(__('Sent through'))
                        ->options(ApplicationMethod::options())
                        ->live(),
                    TextInput::make('method_detail')
                        ->label(__('Which system'))
                        ->placeholder('Greenhouse, Teamtailor, …')
                        ->maxLength(JobApplication::TEXT_LIMIT)
                        // Only worth asking once the channel is one that has a
                        // name of its own.
                        ->visible(fn (Get $get): bool => ApplicationMethod::tryFrom((string) $get('method'))?->needsDetail() ?? false),
                    DatePicker::make('applied_at')
                        ->label(__('Applied on'))
                        ->default(now()),
                    TextInput::make('source_url')
                        ->label(__('Link to the ad'))
                        ->url()
                        ->maxLength(JobApplication::URL_LIMIT)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('What the ad asks for'))
                ->description(__('Only what it actually states. Anything it leaves out stays empty.'))
                ->schema([
                    TextInput::make('location')
                        ->label(__('Location'))
                        ->placeholder(__('Remote, or Hybrid — Budapest'))
                        ->maxLength(JobApplication::TEXT_LIMIT),
                    Select::make('experience_level')
                        ->label(__('Seniority'))
                        ->options(ExperienceLevel::options())
                        ->placeholder(__('Not stated')),
                    TextInput::make('required_years')
                        ->label(__('Years of experience'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->placeholder(__('Not stated')),
                    Repeater::make('required_skills')
                        ->label(__('Required skills'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Skill'))
                                ->required()
                                ->maxLength(JobApplication::TEXT_LIMIT),
                            TextInput::make('years')
                                ->label(__('Years'))
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(50)
                                ->placeholder(__('Not stated')),
                        ])
                        ->addActionLabel(__('Add skill'))
                        ->reorderableWithButtons()
                        ->itemLabel(fn (array $state): ?string => Arr::get($state, 'name'))
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Section::make(__('The ad'))
                ->description(__('Kept verbatim — postings come down, and then there is no way to tell what was applied for.'))
                ->collapsed(fn (?JobApplication $record): bool => $record !== null)
                ->schema([
                    Textarea::make('job_ad')
                        ->hiddenLabel()
                        ->rows(14)
                        ->autosize()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label(__('Notes'))
                        ->rows(3)
                        ->autosize()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
