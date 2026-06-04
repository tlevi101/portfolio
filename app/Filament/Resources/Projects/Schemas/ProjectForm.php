<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectType;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic info')
                    ->schema([
                        TextInput::make('title')->required(),
                        TextInput::make('summary')->required()->columnSpanFull(),
                        \Filament\Forms\Components\Select::make('type')
                            ->options(collect(ProjectType::cases())->mapWithKeys(
                                fn (ProjectType $type): array => [$type->value => $type->label()]
                            ))
                            ->required(),
                        Toggle::make('featured'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Details (for selected projects)')
                    ->schema([
                        TextInput::make('problem')->columnSpanFull(),
                        TextInput::make('role_description')->columnSpanFull(),
                        TextInput::make('outcome')->columnSpanFull(),
                    ]),

                Section::make('Stack & link')
                    ->schema([
                        TagsInput::make('stack')
                            ->placeholder('Add technology')
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
