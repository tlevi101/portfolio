<?php

namespace App\Filament\Resources\Educations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EducationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Institution'))
                    ->schema([
                        TextInput::make('school')->label(__('School'))->required()->columnSpanFull(),
                        TextInput::make('degree')->label(__('Degree')),
                        TextInput::make('graduation_year')->label(__('Graduation year'))->placeholder('2022'),
                        TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary')),
                        TextInput::make('sort_order')->label(__('Sort order'))->numeric()->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}
