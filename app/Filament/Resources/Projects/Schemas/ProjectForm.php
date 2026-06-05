<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Basic info'))
                    ->schema([
                        TextInput::make('title')->label(__('Title'))->required(),
                        TextInput::make('summary')->label(__('Summary'))->required()->columnSpanFull(),
                        Select::make('type')
                            ->label(__('Type'))
                            ->options(collect(ProjectType::cases())->mapWithKeys(
                                fn (ProjectType $type): array => [$type->value => $type->label()]
                            ))
                            ->required(),
                        Toggle::make('featured')->label(__('Featured')),
                        TextInput::make('sort_order')
                            ->label(__('Sort order'))
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make(__('Details (for selected projects)'))
                    ->schema([
                        TextInput::make('problem')->label(__('Problem'))->columnSpanFull(),
                        TextInput::make('role_description')->label(__('Role description'))->columnSpanFull(),
                        TextInput::make('outcome')->label(__('Outcome'))->columnSpanFull(),
                    ]),

                Section::make(__('Stack & link'))
                    ->schema([
                        TagsInput::make('stack')
                            ->label(__('Stack'))
                            ->placeholder(__('Add technology'))
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label(__('Link'))
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
