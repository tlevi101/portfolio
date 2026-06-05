<?php

namespace App\Filament\Resources\Educations;

use App\Filament\Resources\Educations\Pages\CreateEducation;
use App\Filament\Resources\Educations\Pages\EditEducation;
use App\Filament\Resources\Educations\Pages\ListEducations;
use App\Filament\Resources\Educations\Schemas\EducationForm;
use App\Filament\Resources\Educations\Tables\EducationsTable;
use App\Models\Education;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EducationResource extends Resource
{
    protected static ?string $model = Education::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Education');
    }

    public static function getModelLabel(): string
    {
        return __('education entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('education entries');
    }

    public static function form(Schema $schema): Schema
    {
        return EducationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEducations::route('/'),
            'create' => CreateEducation::route('/create'),
            'edit' => EditEducation::route('/{record}/edit'),
        ];
    }
}
