<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use App\Services\CvGeneratorService;
use App\Services\ImageOptimizer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property Schema $form
 */
class ProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.profile-page';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Profile');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(Profile::singleton()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Profile')
                    ->tabs([
                        Tab::make(__('Hero'))
                            ->schema([
                                TextInput::make('full_name')->label(__('Full name'))->required(),
                                TextInput::make('role')->label(__('Role'))->required(),
                                TextInput::make('tagline')->label(__('Tagline'))->required()->columnSpanFull(),
                                TextInput::make('hero_eyebrow')->label(__('Hero eyebrow')),
                                TextInput::make('location')->label(__('Location'))->required(),
                                TextInput::make('phone')->label(__('Phone')),
                                TextInput::make('portfolio_url')->label(__('Portfolio URL'))->url(),
                                Toggle::make('available')->label(__('Available')),
                                TextInput::make('available_text')->label(__('Available text'))->placeholder(__('Open to work')),
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
                                TextInput::make('projects_heading')->label(__('Projects heading'))->columnSpanFull(),
                                TextInput::make('projects_subheading')->label(__('Projects subheading'))->columnSpanFull(),
                            ]),

                        Tab::make(__('Experiments'))
                            ->schema([
                                TextInput::make('experiments_heading')->label(__('Experiments heading'))->columnSpanFull(),
                                Textarea::make('experiments_intro')->label(__('Experiments intro'))->rows(3)->columnSpanFull(),
                            ]),

                        Tab::make(__('About'))
                            ->schema([
                                TextInput::make('about_heading')->label(__('About heading'))->columnSpanFull(),
                                Textarea::make('about')->label(__('About'))->rows(5)->required()->columnSpanFull(),
                                Repeater::make('experience_highlights')
                                    ->label(__('Experience highlights'))
                                    ->simple(TextInput::make('item')->label(__('Highlight')))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('Contact'))
                            ->schema([
                                TextInput::make('contact_heading')->label(__('Contact heading'))->columnSpanFull(),
                                Textarea::make('contact_intro')->label(__('Contact intro'))->rows(3)->columnSpanFull(),
                                TextInput::make('email')->label(__('Email'))->email()->required(),
                                TextInput::make('linkedin_url')->label(__('LinkedIn URL'))->url(),
                                TextInput::make('github_url')->label(__('GitHub URL'))->url(),
                            ])
                            ->columns(2),

                        Tab::make(__('Footer'))
                            ->schema([
                                TextInput::make('footer_text')->label(__('Footer text'))->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        Profile::singleton()->fill($data)->save();

        Notification::make()
            ->title(__('Profile saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save changes'))
                ->submit('save'),
        ];
    }

    public function regenerateCv(): void
    {
        app(CvGeneratorService::class)->generate();

        Notification::make()
            ->title(__('CV regenerated'))
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateCv')
                ->label(__('Regenerate CV'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action('regenerateCv'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Profile');
    }
}
