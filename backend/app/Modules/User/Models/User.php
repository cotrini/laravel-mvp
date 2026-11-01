<?php

namespace App\Modules\User\Models;

use App\Modules\Property\Models\Property;
use App\Modules\User\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token', 'role'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_FILMMAKER = 'filmmaker';

    public const ROLE_APPRAISER = 'appraiser';

    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $attributes = [
        'email_verification_required' => false,
        'role' => self::ROLE_USER,
    ];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function assignedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'filmmaker_property', 'filmmaker_id', 'property_id')
            ->withTimestamps();
    }

    public function assignedAppraisalProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'appraiser_property', 'appraiser_id', 'property_id')
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

    public function isAppraiser(): bool
    {
        return $this->role === self::ROLE_APPRAISER;
    }

    public function requiresEmailVerification(): bool
    {
        return $this->email_verification_required;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! $this->requiresEmailVerification() || $this->email_verified_at !== null;
    }

    public function canAccessProperty(Property $property): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isFilmmaker()) {
            return $this->assignedProperties()->whereKey($property->getKey())->exists();
        }

        if ($this->isAppraiser()) {
            return $this->assignedAppraisalProperties()->whereKey($property->getKey())->exists();
        }

        return (int) $property->user_id === (int) $this->id;
    }

    public function canEditProperty(Property $property): bool
    {
        return $this->isAdmin()
            || ($this->isFilmmaker() && $this->assignedProperties()->whereKey($property->getKey())->exists())
            || ($this->role === self::ROLE_USER && (int) $property->user_id === (int) $this->id);
    }

    public function canDeleteProperty(Property $property): bool
    {
        return $this->isAdmin()
            || ($this->role === self::ROLE_USER && (int) $property->user_id === (int) $this->id);
    }

    public function canManagePropertyMedia(Property $property): bool
    {
        return $this->isAdmin()
            || ($this->isFilmmaker() && $this->assignedProperties()->whereKey($property->getKey())->exists());
    }

    public function canUploadAppraisal(Property $property): bool
    {
        return $this->isAppraiser()
            && $this->assignedAppraisalProperties()->whereKey($property->getKey())->exists();
    }

    public function canTogglePropertyActive(Property $property): bool
    {
        return $this->isAdmin()
            || ($this->role === self::ROLE_USER && (int) $property->user_id === (int) $this->id);
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
            'email_verification_required' => 'boolean',
            'is_banned' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
