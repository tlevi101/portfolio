<?php

namespace App\Filament\Resources\Cvs\RelationManagers;

use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Filament\Resources\JobApplications\Schemas\JobApplicationForm;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The jobs this CV was sent for.
 *
 * Normally one — a variant is cut per job ad — so this is usually a single row
 * confirming where the CV went and whether anyone opened it.
 */
class JobApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'jobApplications';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Applications');
    }

    public function form(Schema $schema): Schema
    {
        return JobApplicationForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company')
            ->emptyStateHeading(__('Not sent anywhere yet'))
            ->emptyStateDescription(__('An "Import from AI" that carries a job ad records one here automatically.'))
            ->defaultSort('applied_at', 'desc')
            ->columns([
                TextColumn::make('company')
                    ->label(__('Company'))
                    ->description(fn (JobApplication $record): ?string => $record->title)
                    ->searchable(),
                TextColumn::make('status')->label(__('Status'))->badge(),
                TextColumn::make('method')
                    ->label(__('Sent through'))
                    ->state(fn (JobApplication $record): ?string => $record->methodLabel())
                    ->placeholder('—'),
                TextColumn::make('applied_at')->label(__('Applied'))->date('Y-m-d')->placeholder('—'),
                TextColumn::make('visit_sessions_count')
                    ->label(__('Visits'))
                    ->counts(['visitSessions' => fn ($query) => $query->where('is_bot', false)])
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()->label(__('Record an application')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('Open'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(fn (JobApplication $record): string => JobApplicationResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
