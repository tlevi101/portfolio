<?php

namespace App\Filament\Resources\WorkExperiences\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Position'))
                    ->schema([
                        TextInput::make('company')->label(__('Company'))->required(),
                        TextInput::make('title')->label(__('Job title'))->required(),
                        TextInput::make('period')->label(__('Period'))->required()->placeholder(__('2024 – Present')),
                        TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary')),
                        TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),
                    ])
                    ->columns(2),

                Section::make(__('Responsibilities'))
                    ->schema([
                        Repeater::make('bullets')
                            ->hiddenLabel()
                            ->simple(TextInput::make('item')->label(__('Bullet point')))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
