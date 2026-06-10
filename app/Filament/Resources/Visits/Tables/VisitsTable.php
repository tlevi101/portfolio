<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Models\Visit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class VisitsTable
{
    private const EVENT_COLORS = [
        'page_view' => 'gray',
        'cv_download' => 'success',
        'click' => 'info',
        'section' => 'warning',
        'duration' => 'primary',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('created_at')
                    ->label(__('Day'))
                    ->date()
                    ->collapsible(),
                Group::make('ip_hash')
                    ->label(__('Visitor'))
                    ->getTitleFromRecordUsing(fn (Visit $record): string => $record->ip_hash ? __('Visitor :code', ['code' => substr($record->ip_hash, 0, 8)]) : '—')
                    ->collapsible(),
            ])
            ->defaultGroup('created_at')
            ->columns([
                TextColumn::make('created_at')->label(__('When'))->dateTime('H:i:s')->sortable(),
                TextColumn::make('ip_hash')
                    ->label(__('Visitor'))
                    ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 8) : '—')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->tooltip(__('Same code = same visitor (by IP)')),
                TextColumn::make('event')
                    ->label(__('Event'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::eventNames()[$state] ?? $state)
                    ->color(fn (string $state): string => self::EVENT_COLORS[$state] ?? 'gray'),
                TextColumn::make('label')
                    ->label(__('Detail'))
                    ->state(fn (Visit $record): ?string => self::describe($record))
                    ->placeholder('—'),
                TextColumn::make('country')->label(__('Country'))->badge()->placeholder('—'),
                IconColumn::make('is_bot')->label(__('Bot'))->boolean()->toggleable(),
                TextColumn::make('slug')->label(__('Version'))->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')->label(__('Lang'))->badge()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('referer')->label(__('Referrer'))->limit(40)->placeholder('—')->toggleable(),
                TextColumn::make('user_agent')->label(__('User agent'))->limit(50)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('Event'))
                    ->options(self::eventNames()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function eventNames(): array
    {
        return [
            'page_view' => __('Page view'),
            'cv_download' => __('CV download'),
            'click' => __('Click'),
            'section' => __('Section view'),
            'duration' => __('Time on page'),
        ];
    }

    /**
     * Human-readable description of what the visitor did, instead of raw codes.
     */
    private static function describe(Visit $visit): ?string
    {
        return match ($visit->event) {
            'click' => self::clickNames()[$visit->label] ?? $visit->label,
            'section' => __('Scrolled to: :section', ['section' => self::sectionNames()[$visit->label] ?? (string) $visit->label]),
            'duration' => __(':time on the page', ['time' => self::formatSeconds((int) $visit->value)]),
            'cv_download' => __('Downloaded the CV'),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private static function clickNames(): array
    {
        return [
            'contact_email' => __('Clicked the email address'),
            'contact_phone' => __('Clicked the phone number'),
            'linkedin' => __('Opened LinkedIn'),
            'github' => __('Opened GitHub'),
            'view_projects' => __('Clicked "View projects"'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function sectionNames(): array
    {
        return [
            'top' => __('Hero'),
            'projects' => __('Projects'),
            'experiments' => __('Side projects'),
            'about' => __('About'),
            'experience' => __('Experience'),
            'contact' => __('Contact'),
        ];
    }

    private static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }
}
