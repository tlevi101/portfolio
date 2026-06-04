<?php

namespace App\Filament\Pages;

use App\Models\Profile;
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

/**
 * @property Schema $form
 */
class ProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.profile-page';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Profile';

    protected static ?int $navigationSort = 1;

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
                        Tab::make('Hero')
                            ->schema([
                                TextInput::make('full_name')->required(),
                                TextInput::make('role')->required(),
                                TextInput::make('tagline')->required()->columnSpanFull(),
                                TextInput::make('hero_eyebrow'),
                                TextInput::make('location')->required(),
                                Toggle::make('available'),
                                TextInput::make('available_text')->placeholder('Open to work'),
                                FileUpload::make('avatar_path')
                                    ->image()
                                    ->directory('profile')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Projects')
                            ->schema([
                                TextInput::make('projects_heading')->columnSpanFull(),
                                TextInput::make('projects_subheading')->columnSpanFull(),
                            ]),

                        Tab::make('Experiments')
                            ->schema([
                                TextInput::make('experiments_heading')->columnSpanFull(),
                                Textarea::make('experiments_intro')->rows(3)->columnSpanFull(),
                            ]),

                        Tab::make('About')
                            ->schema([
                                TextInput::make('about_heading')->columnSpanFull(),
                                Textarea::make('about')->rows(5)->required()->columnSpanFull(),
                                Repeater::make('experience_highlights')
                                    ->simple(TextInput::make('item')->label('Highlight'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Contact')
                            ->schema([
                                TextInput::make('contact_heading')->columnSpanFull(),
                                Textarea::make('contact_intro')->rows(3)->columnSpanFull(),
                                TextInput::make('email')->email()->required(),
                                TextInput::make('linkedin_url')->url(),
                                TextInput::make('github_url')->url(),
                                FileUpload::make('cv_path')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->directory('cv')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Footer')
                            ->schema([
                                TextInput::make('footer_text')->columnSpanFull(),
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
            ->title('Profile saved')
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
                ->label('Save changes')
                ->submit('save'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Profile';
    }
}
