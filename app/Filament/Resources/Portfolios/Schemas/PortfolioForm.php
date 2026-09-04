<?php

namespace App\Filament\Resources\Portfolios\Schemas;

use App\Enums\ProjectType;
use App\Enums\SkillGroup;
use App\Models\Portfolio;
use App\Services\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PortfolioForm
{
    /**
     * Free-text columns are varchar(500); mirror that in the form so a value is
     * rejected with a message rather than truncated on the way into the column.
     */
    private const TEXT_LIMIT = 500;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Portfolio')
                    ->tabs([
                        Tab::make(__('Settings'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('Label'))
                                    ->required()
                                    ->maxLength(self::TEXT_LIMIT)
                                    ->helperText(__('Admin-only name, e.g. "Full-stack" or "Java Junior".')),
                                Select::make('locale')
                                    ->label(__('Language'))
                                    ->options(Portfolio::LOCALES)
                                    ->required()
                                    ->default(config('app.locale')),
                                TextInput::make('slug')
                                    ->label(__('Slug'))
                                    ->required()
                                    ->maxLength(200)
                                    ->helperText(__('Used in the URL: /{slug}'))
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('locale', $get('locale')),
                                    ),
                                Toggle::make('is_default')
                                    ->label(__('Default'))
                                    ->helperText(__('The default portfolio renders at / for its language.')),
                                Select::make('cv_id')
                                    ->label(__('Download CV'))
                                    ->relationship(
                                        name: 'cv',
                                        titleAttribute: 'label',
                                        modifyQueryUsing: fn (Builder $query, ?Portfolio $record): Builder => $query->where('portfolio_id', $record?->id),
                                    )
                                    ->helperText(__('Served by the "Download CV" button. Create the CV first, then select it here.'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('Hero'))
                            ->schema([
                                TextInput::make('full_name')->label(__('Full name'))->required()->maxLength(self::TEXT_LIMIT),
                                TextInput::make('role')->label(__('Role'))->required()->maxLength(self::TEXT_LIMIT),
                                Textarea::make('tagline')
                                    ->label(__('Tagline'))
                                    ->required()
                                    ->rows(3)
                                    ->autosize()
                                    ->maxLength(self::TEXT_LIMIT)
                                    ->helperText(__('Plain text: it is also used as the page\'s meta description.'))
                                    ->columnSpanFull(),
                                TextInput::make('hero_eyebrow')->label(__('Hero eyebrow'))->maxLength(self::TEXT_LIMIT),
                                TextInput::make('location')->label(__('Location'))->required()->maxLength(self::TEXT_LIMIT),
                                TextInput::make('phone')->label(__('Phone'))->maxLength(self::TEXT_LIMIT),
                                TextInput::make('portfolio_url')->label(__('Portfolio URL'))->url()->maxLength(self::TEXT_LIMIT),
                                Toggle::make('available')->label(__('Available')),
                                TextInput::make('available_text')->label(__('Available text'))->placeholder(__('Open to work'))->maxLength(self::TEXT_LIMIT),
                                FileUpload::make('avatar_path')
                                    ->label(__('Avatar'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('profile')
                                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                        $optimized = app(ImageOptimizer::class)->optimizeToJpeg($file->get());
                                        $path = 'profile/'.Str::ulid()->toString().'.jpg';
                                        Storage::disk('public')->put($path, $optimized);

                                        return $path;
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('Projects'))
                            ->schema([
                                TextInput::make('projects_heading')->label(__('Projects heading'))->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                self::richText(RichEditor::make('projects_subheading'))
                                    ->label(__('Projects subheading'))
                                    ->columnSpanFull(),
                                Repeater::make('selectedProjects')
                                    ->label(__('Selected projects'))
                                    ->relationship(
                                        'projects',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', ProjectType::Selected->value),
                                    )
                                    ->schema([
                                        Hidden::make('type')->default(ProjectType::Selected->value),
                                        TextInput::make('title')->label(__('Title'))->required()->maxLength(self::TEXT_LIMIT),
                                        Toggle::make('featured')->label(__('Featured')),
                                        self::richText(RichEditor::make('summary'))->label(__('Summary'))->required()->columnSpanFull(),
                                        self::richText(RichEditor::make('problem'))->label(__('Problem'))->columnSpanFull(),
                                        self::richText(RichEditor::make('role_description'))->label(__('Role description'))->columnSpanFull(),
                                        self::richText(RichEditor::make('outcome'))->label(__('Outcome'))->columnSpanFull(),
                                        TagsInput::make('stack')->label(__('Stack'))->placeholder(__('Add technology'))->columnSpanFull(),
                                        TextInput::make('url')->label(__('Link'))->url()->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Experiments'))
                            ->schema([
                                TextInput::make('experiments_heading')->label(__('Experiments heading'))->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                self::richText(RichEditor::make('experiments_intro'))
                                    ->label(__('Experiments intro'))
                                    ->columnSpanFull(),
                                Repeater::make('sideProjects')
                                    ->label(__('Side projects'))
                                    ->relationship(
                                        'projects',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', ProjectType::SideProject->value),
                                    )
                                    ->schema([
                                        Hidden::make('type')->default(ProjectType::SideProject->value),
                                        TextInput::make('title')->label(__('Title'))->required()->maxLength(self::TEXT_LIMIT),
                                        self::richText(RichEditor::make('summary'))->label(__('Summary'))->required()->columnSpanFull(),
                                        TagsInput::make('stack')->label(__('Stack'))->placeholder(__('Add technology'))->columnSpanFull(),
                                        TextInput::make('url')->label(__('Link'))->url()->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('About'))
                            ->schema([
                                TextInput::make('about_heading')->label(__('About heading'))->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                self::richText(RichEditor::make('about'))->label(__('About'))->required()->columnSpanFull(),
                                Repeater::make('experience_highlights')
                                    ->label(__('Experience highlights'))
                                    // Textarea rather than a rich editor: a
                                    // `simple()` repeater hands its child the
                                    // whole row as state, which the rich
                                    // editor's TipTap cast cannot read.
                                    ->simple(
                                        Textarea::make('item')
                                            ->label(__('Highlight'))
                                            ->rows(2)
                                            ->autosize()
                                            ->maxLength(self::TEXT_LIMIT)
                                    )
                                    ->columnSpanFull(),
                                Repeater::make('skills')
                                    ->label(__('Skills'))
                                    ->relationship()
                                    ->schema([
                                        Select::make('group')
                                            ->label(__('Group'))
                                            ->options(collect(SkillGroup::cases())->mapWithKeys(
                                                fn (SkillGroup $group): array => [$group->value => __($group->label())]
                                            ))
                                            ->required(),
                                        TextInput::make('name')->label(__('Skill name'))->required()->maxLength(self::TEXT_LIMIT),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->reorderableWithButtons()
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Contact'))
                            ->schema([
                                TextInput::make('contact_heading')->label(__('Contact heading'))->maxLength(self::TEXT_LIMIT)->columnSpanFull(),
                                self::richText(RichEditor::make('contact_intro'))
                                    ->label(__('Contact intro'))
                                    ->columnSpanFull(),
                                TextInput::make('email')->label(__('Email'))->email()->required()->maxLength(self::TEXT_LIMIT),
                                TextInput::make('linkedin_url')->label(__('LinkedIn URL'))->url()->maxLength(self::TEXT_LIMIT),
                                TextInput::make('github_url')->label(__('GitHub URL'))->url()->maxLength(self::TEXT_LIMIT),
                            ])
                            ->columns(2),

                        Tab::make(__('Footer'))
                            ->schema([
                                self::richText(RichEditor::make('footer_text'))
                                    ->label(__('Footer text'))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Bold, italic and links only. The page's own typography owns everything
     * else, so a full editor would just let the two fight.
     *
     * @template TField of RichEditor
     *
     * @param  TField  $field
     * @return TField
     */
    protected static function richText(RichEditor $field): RichEditor
    {
        return $field->toolbarButtons([['bold', 'italic', 'link'], ['undo', 'redo']]);
    }
}
