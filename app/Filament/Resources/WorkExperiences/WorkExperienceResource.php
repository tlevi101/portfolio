<?php

namespace App\Filament\Resources\WorkExperiences;

use App\Filament\Resources\WorkExperiences\Pages\CreateWorkExperience;
use App\Filament\Resources\WorkExperiences\Pages\EditWorkExperience;
use App\Filament\Resources\WorkExperiences\Pages\ListWorkExperiences;
use App\Filament\Resources\WorkExperiences\Schemas\WorkExperienceForm;
use App\Filament\Resources\WorkExperiences\Tables\WorkExperiencesTable;
use App\Models\WorkExperience;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkExperienceResource extends Resource
{
    protected static ?string $model = WorkExperience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Work Experience');
    }

    public static function getModelLabel(): string
    {
        return __('work experience');
    }

    public static function getPluralModelLabel(): string
    {
        return __('work experiences');
    }

    public static function form(Schema $schema): Schema
    {
        return WorkExperienceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkExperiencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkExperiences::route('/'),
            'create' => CreateWorkExperience::route('/create'),
            'edit' => EditWorkExperience::route('/{record}/edit'),
        ];
    }
}
