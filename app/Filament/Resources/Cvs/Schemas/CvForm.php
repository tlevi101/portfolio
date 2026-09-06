<?php

namespace App\Filament\Resources\Cvs\Schemas;

use App\Enums\SkillGroup;
use App\Models\Portfolio;
use App\Services\ImageOptimizer;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CvForm
{
    /**
     * Free-text columns are varchar(500); the PDF has finite room, so the form
     * enforces the same ceiling rather than letting the layout decide.
     */
    public const TEXT_LIMIT = 500;

    /**
     * How long typing has to pause before the state is pushed to the server.
     * Livewire's own default for a live input is 150ms, which is inside the
     * rhythm of ordinary typing.
     */
    public const LIVE_DEBOUNCE = '600ms';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('CV')
                    ->persistTabInQueryString()
                    ->tabs([
                        self::settingsTab(),
                        self::headerTab(),
                        self::contactTab(),
                        self::skillsTab(),
                        self::experienceTab(),
                        self::projectsTab(),
                        self::educationTab(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function settingsTab(): Tab
    {
        return Tab::make(__('Settings'))
            ->schema([
                self::live(
                    Select::make('portfolio_id')
                        ->label(__('Portfolio'))
                        ->relationship('portfolio', 'label')
                        ->getOptionLabelFromRecordUsing(
                            fn (Portfolio $record): string => "{$record->label} ({$record->slug} / {$record->locale})"
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('Only decides which site serves this CV. The content below is the CV\'s own.'))
                ),
                self::live(TextInput::make('label')->label(__('Label'))->maxLength(self::TEXT_LIMIT)),
                self::live(
                    Select::make('locale')
                        ->label(__('Language'))
                        ->options(Portfolio::LOCALES)
                        ->required()
                        ->default(config('app.locale'))
                ),
            ])
            ->columns(2);
    }

    protected static function headerTab(): Tab
    {
        return Tab::make(__('Header'))
            ->schema([
                self::live(TextInput::make('full_name')->label(__('Full name'))->required()->maxLength(self::TEXT_LIMIT)),
                self::live(TextInput::make('role')->label(__('Role'))->maxLength(self::TEXT_LIMIT)),
                self::live(
                    self::richText(RichEditor::make('summary'))
                        ->label(__('Profile summary'))
                        ->helperText(__('Two lines at most, written in the present tense.'))
                        ->maxLength(self::TEXT_LIMIT)
                        ->columnSpanFull()
                ),
                self::live(
                    TagsInput::make('stack_highlights')
                        ->label(__('Core stack'))
                        ->placeholder(__('Add technology'))
                        ->helperText(__('The chip row above Experience. 5–6 items, swapped per job ad.'))
                        ->columnSpanFull()
                ),
                FileUpload::make('avatar_path')
                    ->label(__('Avatar'))
                    ->image()
                    ->disk('public')
                    ->directory('profile')
                    ->live()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $optimized = app(ImageOptimizer::class)->optimizeToJpeg($file->get());
                        $path = 'profile/'.Str::ulid()->toString().'.jpg';
                        Storage::disk('public')->put($path, $optimized);

                        return $path;
                    })
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function contactTab(): Tab
    {
        return Tab::make(__('Contact'))
            ->schema([
                self::live(TextInput::make('email')->label(__('Email'))->email()->maxLength(self::TEXT_LIMIT)),
                self::live(TextInput::make('phone')->label(__('Phone'))->maxLength(self::TEXT_LIMIT)),
                self::live(TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary'))->maxLength(self::TEXT_LIMIT)),
                self::live(TextInput::make('linkedin_url')->label(__('LinkedIn URL'))->url()->maxLength(self::TEXT_LIMIT)),
                self::live(TextInput::make('github_url')->label(__('GitHub URL'))->url()->maxLength(self::TEXT_LIMIT)),
                self::live(
                    TextInput::make('portfolio_url')
                        ->label(__('Portfolio URL'))
                        ->url()
                        ->maxLength(self::TEXT_LIMIT)
                        ->helperText(__('The QR code target. Falls back to the linked portfolio\'s address when empty.'))
                ),
                self::liveRepeater(Repeater::make('languages'))
                    ->label(__('Languages'))
                    ->schema([
                        TextInput::make('name')->label(__('Language'))->required()->maxLength(self::TEXT_LIMIT),
                        Select::make('level')
                            ->label(__('Level'))
                            ->options([
                                'Native' => __('Native'),
                                'C2' => 'C2',
                                'C1' => 'C1',
                                'B2' => 'B2',
                                'B1' => 'B1',
                                'A2' => 'A2',
                                'A1' => 'A1',
                            ])
                            ->required(),
                    ])
                    ->reorderableWithButtons()
                    ->itemLabel(fn (array $state): ?string => filled(Arr::get($state, 'name'))
                        ? trim(Arr::get($state, 'name').' — '.Arr::get($state, 'level', ''))
                        : null)
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function skillsTab(): Tab
    {
        return Tab::make(__('Skills'))
            ->schema(
                collect(SkillGroup::cases())
                    ->map(fn (SkillGroup $group): Repeater => self::skillGroupRepeater($group))
                    ->all()
            );
    }

    /**
     * One repeater per group, each scoped to its own slice of the relation, so
     * a group can be added to and reordered on its own instead of everything
     * sharing a single list with a group dropdown on every row.
     */
    protected static function skillGroupRepeater(SkillGroup $group): Repeater
    {
        return self::liveRepeater(Repeater::make('skills'.$group->value))
            ->label(__($group->label()))
            ->relationship(
                'skills',
                modifyQueryUsing: fn (Builder $query): Builder => $query->where('group', $group->value),
            )
            ->schema([
                Hidden::make('group')->default($group->value),
                TextInput::make('name')
                    ->hiddenLabel()
                    ->placeholder(__('Skill name'))
                    ->required()
                    ->maxLength(self::TEXT_LIMIT),
            ])
            ->orderColumn('sort_order')
            ->reorderableWithButtons()
            ->addActionLabel(__('Add skill'))
            ->grid(2)
            ->columnSpanFull();
    }

    protected static function experienceTab(): Tab
    {
        return Tab::make(__('Work experience'))
            ->schema([
                self::liveRepeater(Repeater::make('workExperiences'))
                    ->hiddenLabel()
                    ->relationship()
                    ->schema([
                        TextInput::make('company')->label(__('Company'))->required()->maxLength(self::TEXT_LIMIT),
                        TextInput::make('title')->label(__('Job title'))->required()->maxLength(self::TEXT_LIMIT),
                        TextInput::make('period')->label(__('Period'))->placeholder(__('2024 – Present'))->maxLength(self::TEXT_LIMIT),
                        TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary'))->maxLength(self::TEXT_LIMIT),
                        Repeater::make('bullets')
                            ->label(__('Responsibilities'))
                            ->simple(
                                Textarea::make('item')
                                    ->label(__('Bullet point'))
                                    ->rows(2)
                                    ->autosize()
                                    ->maxLength(self::TEXT_LIMIT)
                            )
                            ->columnSpanFull(),
                    ])
                    ->orderColumn('sort_order')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['company'] ?? null)
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected static function projectsTab(): Tab
    {
        return Tab::make(__('Projects'))
            ->schema([
                self::liveRepeater(Repeater::make('projects'))
                    ->hiddenLabel()
                    ->relationship()
                    ->schema([
                        TextInput::make('title')->label(__('Title'))->required()->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                        TagsInput::make('stack')->label(__('Stack'))->placeholder(__('Add technology'))->columnSpanFull(),
                    ])
                    ->orderColumn('sort_order')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    protected static function educationTab(): Tab
    {
        return Tab::make(__('Education'))
            ->schema([
                self::liveRepeater(Repeater::make('education'))
                    ->hiddenLabel()
                    ->relationship()
                    ->schema([
                        TextInput::make('school')->label(__('School'))->required()->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                        TextInput::make('degree')->label(__('Degree'))->maxLength(self::TEXT_LIMIT),
                        TextInput::make('location')->label(__('Location'))->placeholder(__('Budapest, Hungary'))->maxLength(self::TEXT_LIMIT),
                        TextInput::make('start_year')->label(__('Start year'))->placeholder('2020'),
                        TextInput::make('graduation_year')->label(__('Graduation year'))->placeholder('2023'),
                    ])
                    ->orderColumn('sort_order')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['school'] ?? null)
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Bold/italic/link only. The CV is a typeset document, so headings, colours
     * and tables from a full editor would fight the template's own styling.
     *
     * @template TField of RichEditor
     *
     * @param  TField  $field
     * @return TField
     */
    public static function richText(RichEditor $field): RichEditor
    {
        return $field->toolbarButtons([['bold', 'italic', 'link'], ['undo', 'redo']]);
    }

    /**
     * Push edits to the server as they are typed so the preview pane can keep
     * up, without a round trip on every single keystroke.
     *
     * `skipRenderAfterStateUpdated()` is what keeps the form usable while that
     * happens: without it every push re-renders the whole schema and Livewire
     * morphs the result over the DOM the user is typing into. The preview does
     * not need any of that HTML — it reloads off the `$cvPreviewHash` property,
     * which travels in the snapshot either way.
     *
     * @template TField of Field
     *
     * @param  TField  $field
     * @return TField
     */
    protected static function live(Field $field): Field
    {
        return $field
            ->live(debounce: self::LIVE_DEBOUNCE)
            ->skipRenderAfterStateUpdated();
    }

    /**
     * The same treatment for a repeater. Its rows inherit the binding from it,
     * so this is what puts a debounce on the inputs inside — and the skip
     * covers them too, because an update to a row bubbles up to the repeater.
     *
     * Adding, removing and reordering rows are actions rather than state
     * updates, so those still re-render as usual.
     */
    protected static function liveRepeater(Repeater $repeater): Repeater
    {
        return $repeater
            ->live(debounce: self::LIVE_DEBOUNCE)
            ->skipRenderAfterStateUpdated();
    }
}
