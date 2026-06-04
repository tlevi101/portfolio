<?php

namespace App\Filament\Resources\Skills\Schemas;

use App\Enums\SkillGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('group')
                    ->options(collect(SkillGroup::cases())->mapWithKeys(
                        fn (SkillGroup $group): array => [$group->value => $group->label()]
                    ))
                    ->required(),
                TextInput::make('name')->required(),
                TextInput::make('sort_order')->numeric()->default(0),
            ])
            ->columns(2);
    }
}
