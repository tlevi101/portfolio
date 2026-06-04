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
                Section::make('Position')
                    ->schema([
                        TextInput::make('company')->required(),
                        TextInput::make('title')->required(),
                        TextInput::make('period')->required()->placeholder('2024 – Present'),
                        TextInput::make('location')->placeholder('Budapest, Hungary'),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ])
                    ->columns(2),

                Section::make('Responsibilities')
                    ->schema([
                        Repeater::make('bullets')
                            ->simple(TextInput::make('item')->label('Bullet point'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
