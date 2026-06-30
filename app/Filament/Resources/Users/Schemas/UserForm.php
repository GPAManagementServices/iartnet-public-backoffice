<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class UserForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label('Ruolo')
                    ->options([
                        'super_admin' => 'Super Administrator',
                        'supervisor_academy' => 'Supervisore Academy',
                        'editor' => 'Editor',
                    ])
                    ->default('editor')
                    ->required(),

                DateTimePicker::make('email_verified_at')
                    ->label('Email verificata il')
                    ->nullable(),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    // obbligatoria solo in create, opzionale in edit
                    ->required(fn (string $operation): bool => $operation === 'create')
                    // se il campo è vuoto in edit, non tocca la colonna nel DB
                    ->dehydrated(fn ($state) => filled($state))
                    // se è pieno, la criptiamo prima di salvare
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null),
            ]);
    }
}
