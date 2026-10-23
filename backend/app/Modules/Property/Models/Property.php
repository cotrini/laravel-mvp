<?php

namespace App\Modules\Property\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Media\Models\Media;


class Property extends Model
{
    use HasFactory;
    protected static function newFactory()
    {
        return \App\Modules\Property\Factories\PropertyFactory::new();
    }

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
        'tour_id',
    ];

    public function media()
    {
        return $this->hasMany(Media::class, 'property_id');
    }

    // O si estás usando la relación polimórfica que definiste en la tabla:
    public function mediaPolymorphic()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
    public function tour()
    {
        return $this->belongsTo(\App\Modules\Tours360\Models\Tour::class);
    }
}