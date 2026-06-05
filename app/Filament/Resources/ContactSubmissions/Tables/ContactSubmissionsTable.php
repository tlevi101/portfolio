<?php

namespace App\Filament\Resources\ContactSubmissions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('Email'))->searchable(),
                TextColumn::make('message')->label(__('Message'))->limit(60),
                TextColumn::make('read_at')
                    ->label(__('Read'))
                    ->since()
                    ->placeholder(__('Unread')),
                TextColumn::make('created_at')->label(__('Received'))->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('markRead')
                    ->label(__('Mark as read'))
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record): bool => $record->read_at === null)
                    ->action(fn ($record) => $record->update(['read_at' => now()])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
