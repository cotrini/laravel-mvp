<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Modules\Property\Models\Property;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected static function newFactory()
    {
    return \App\Modules\User\Factories\UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relación con las propiedades del usuario
    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}