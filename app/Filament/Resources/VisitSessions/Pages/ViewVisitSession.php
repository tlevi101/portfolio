<?php

namespace App\Filament\Resources\VisitSessions\Pages;

use App\Filament\Resources\Cvs\CvResource;
use App\Filament\Resources\VisitSessions\VisitSessionResource;
use App\Models\Visit;
use App\Models\VisitSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @extends ViewRecord<VisitSession>
 */
class ViewVisitSession extends ViewRecord
{
    protected static string $resource = VisitSessionResource::class;

    public function getTitle(): string
    {
        return __('Visit on :date', ['date' => $this->getRecord()->started_at->format('Y-m-d H:i')]);
    }

    /**
     * The summary, with the timeline of what they actually did below it as a
     * relation manager. Everything here was rolled up as the hits arrived, so
     * none of it costs a query per field.
     */
    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('This visit'))
                ->schema([
                    TextEntry::make('ip_hash')
                        ->label(__('Visitor'))
                        ->formatStateUsing(fn (string $state): string => substr($state, 0, 8))
                        ->badge()
                        ->color('gray')
                        ->helperText(__('A salted hash of the address — the address itself is never stored.')),
                    TextEntry::make('cv.label')
                        ->label(__('Came in through'))
                        ->placeholder(__('Found the site another way'))
                        ->url(fn (VisitSession $record): ?string => $record->cv_id !== null
                            ? CvResource::getUrl('edit', ['record' => $record->cv_id])
                            : null),
                    TextEntry::make('duration_seconds')
                        ->label(__('Time on the site'))
                        ->formatStateUsing(fn (int $state): string => Visit::formatSeconds($state)),
                    TextEntry::make('deepest_section')
                        ->label(__('Got as far as'))
                        ->state(fn (VisitSession $record): ?string => $record->deepestSectionLabel())
                        ->badge()
                        ->color('warning')
                        ->placeholder('—'),
                    TextEntry::make('page_views')->label(__('Page views')),
                    TextEntry::make('clicks')->label(__('Link clicks')),
                    TextEntry::make('cv_downloads')
                        ->label(__('CV downloads'))
                        ->badge()
                        ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                    TextEntry::make('country')->label(__('Country'))->badge()->placeholder('—'),
                    TextEntry::make('referer')
                        ->label(__('Came from'))
                        ->placeholder(__('Typed the address, or a bookmark'))
                        ->columnSpanFull(),
                    TextEntry::make('user_agent')
                        ->label(__('Browser'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(4),
        ]);
    }
}
