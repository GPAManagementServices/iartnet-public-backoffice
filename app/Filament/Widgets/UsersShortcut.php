<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class UsersShortcut extends Widget
{
    // Filament 3: static e string
    protected static string $view = 'filament.widgets.users-shortcut';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }
}
