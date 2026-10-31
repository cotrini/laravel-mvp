<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Property\Models\Property;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token', 'role'])]
class User extends Authenticatable
{
    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_FILMMAKER = 'filmmaker';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => self::ROLE_USER,
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function assignedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'filmmaker_property', 'filmmaker_id', 'property_id')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isFilmmaker(): bool
    {
        return $this->role === self::ROLE_FILMMAKER;
    }

    public function canAccessProperty(Property $property): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isFilmmaker()) {
            return $this->assignedProperties()->whereKey($property->getKey())->exists();
        }

        return (int) $property->user_id === (int) $this->id;
    }

    public function canEditProperty(Property $property): bool
    {
        return $this->isAdmin()
            || (! $this->isFilmmaker() && (int) $property->user_id === (int) $this->id);
    }

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
}
