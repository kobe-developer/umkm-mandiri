<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token', 'pin_hash'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    public function hasPin(): bool
    {
        return $this->pin_hash !== null;
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function openShift(): HasOne
    {
        return $this->hasOne(Shift::class)->whereNull('closed_at')->latestOfMany('opened_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('role', Role::Owner->value);
    }
}
