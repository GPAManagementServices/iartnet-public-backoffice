<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Concerns\NormalizesTextOnSave;
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSupervisorAcademy(): bool
    {
        return $this->role === 'supervisor_academy';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    /**
     * Filament: può accedere al pannello?
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Qui decidi chi può entrare nel pannello "admin"
        // Per esempio: super_admin, supervisor_academy ed editor
        return in_array($this->role, [
            'super_admin',
            'supervisor_academy',
            'editor',
        ], true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function scalarOptionalTextAttributes(): array
    {
        return ['email'];
    }

    protected function scalarRequiredTextAttributes(): array
    {
        return ['name', 'role'];
    }
}
