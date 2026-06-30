<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Filament 3: solo ?string, icona come stringa
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    // Filament 3: usa Form invece di Schema
    public static function form(Form $form): Form
    {
        return UserForm::configure($form);
    }

    // Table rimane uguale, qui Filament 3 è identico
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }
}
