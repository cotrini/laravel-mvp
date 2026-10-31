<?php

namespace App\Modules\User\Models;

use App\Modules\Property\Models\Property;
use App\Modules\User\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone_number', 'password'])]
#[Hidden(['password', 'remember_token', 'role'])]
class User extends \App\Models\User
{
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
