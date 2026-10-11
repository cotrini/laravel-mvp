<?php

namespace App\Modules\Property\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla en la base de datos
    protected $table = 'properties';

    // Campos permitidos para asignación masiva (Mass Assignment)
    protected $fillable = [
        'title',
        'description',
        'price',
        'property_type',
        'status',
        'department',
        'city',
        'neighborhood',
    ];
}