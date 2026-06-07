<?php

namespace App\Filament\Resources\Cvs\Schemas;

use App\Models\Portfolio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CvForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('CV settings'))
                    ->schema([
                        Select::make('portfolio_id')
                            ->label(__('Portfolio'))
                            ->relationship('portfolio', 'label')
                            ->getOptionLabelFromRecordUsing(
                                fn (Portfolio $record): string => "{$record->label} ({$record->slug} / {$record->locale})"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('The CV inherits identity, skills and selected projects from this portfolio.')),
                        TextInput::make('label')->label(__('Label')),
                        Select::make('locale')
                            ->label(__('Language'))
                            ->options(Portfolio::LOCALES)
                            ->required()
                            ->default(config('app.locale')),
                    ])
                    ->columns(2),

                Section::make(__('Work experience'))
                    ->schema([
                        Repeater::make('workExperiences')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                TextInput::make('company')->label(__('Company'))->required(),
                                TextInput::make('title')->label(__('Job title'))->required(),
                                TextInput::make('period')->label(__('Period'))->placeholder(__('2024 – Present')),
                                TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary')),
                                Repeater::make('bullets')
                                    ->label(__('Responsibilities'))
                                    ->simple(TextInput::make('item')->label(__('Bullet point')))
                                    ->columnSpanFull(),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['company'] ?? null)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Education'))
                    ->schema([
                        Repeater::make('education')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                TextInput::make('school')->label(__('School'))->required()->columnSpanFull(),
                                TextInput::make('degree')->label(__('Degree')),
                                TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary')),
                                TextInput::make('start_year')->label(__('Start year'))->placeholder('2020'),
                                TextInput::make('graduation_year')->label(__('Graduation year'))->placeholder('2023'),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['school'] ?? null)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
