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
                Section::make('Institution')
                    ->schema([
                        TextInput::make('school')->required()->columnSpanFull(),
                        TextInput::make('degree'),
                        TextInput::make('graduation_year')->placeholder('2022'),
                        TextInput::make('location')->placeholder('Budapest, Hungary'),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}
