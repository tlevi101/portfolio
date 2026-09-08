<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use App\Models\Visit;
use App\Models\Visitor;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @extends ViewRecord<Visitor>
 */
class ViewVisitor extends ViewRecord
{
    protected static string $resource = VisitorResource::class;

    public function getTitle(): string
    {
        return __('Visitor :code', ['code' => substr($this->getRecord()->ip_hash, 0, 8)]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('This visitor'))
                ->description(__('Identified only by a salted hash of their address. Nothing is stored on their device, and the address itself is never written down.'))
                ->schema([
                    TextEntry::make('country')->label(__('Country'))->badge()->placeholder('—'),
                    TextEntry::make('returning')
                        ->label(__('Returning'))
                        ->state(fn (Visitor $record): string => $record->isReturning() ? __('Yes') : __('No'))
                        ->badge()
                        ->color(fn (Visitor $record): string => $record->isReturning() ? 'success' : 'gray'),
                    TextEntry::make('first_seen_at')->label(__('First seen'))->dateTime('Y-m-d H:i'),
                    TextEntry::make('last_seen_at')->label(__('Last seen'))->dateTime('Y-m-d H:i'),
                    TextEntry::make('total_seconds')
                        ->label(__('Time on site'))
                        ->state(fn (Visitor $record): string => Visit::formatSeconds(
                            (int) $record->visits()->where('event', 'duration')->sum('value'),
                        )),
                    TextEntry::make('is_bot')
                        ->label(__('Looks automated'))
                        ->state(fn (Visitor $record): string => $record->is_bot ? __('Yes') : __('No'))
                        ->badge()
                        ->color(fn (Visitor $record): string => $record->is_bot ? 'gray' : 'success'),
                ])
                ->columns(3),
        ]);
    }
}
