<?php

namespace App\Modules\Property\Models;

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Factories\PropertyFactory;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return PropertyFactory::new();
    }

    // Nombre explícito de la tabla en la base de datos
    protected $table = 'properties';

    // Campos permitidos para asignación masiva (Mass Assignment)
    protected $fillable = [
        'title',
        'description',
        'price',
        'property_type',
        'listing_type',
        'status',
        'department',
        'city',
        'neighborhood',
        'tour_id',
        'bedrooms',
        'bathrooms',
        'half_bathrooms',
        'parking_spaces',
        'built_area_m2',
        'lot_area_m2',
        'floor_number',
        'total_floors',
        'stratum',
        'year_built',
        'administration_fee',
        'is_furnished',
        'has_elevator',
    ];

    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'half_bathrooms' => 'integer',
            'parking_spaces' => 'integer',
            'built_area_m2' => 'decimal:2',
            'lot_area_m2' => 'decimal:2',
            'floor_number' => 'integer',
            'total_floors' => 'integer',
            'stratum' => 'integer',
            'year_built' => 'integer',
            'administration_fee' => 'decimal:2',
            'is_furnished' => 'boolean',
            'has_elevator' => 'boolean',
        ];
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'property_id');
    }

    // O si estás usando la relación polimórfica que definiste en la tabla:
    public function mediaPolymorphic()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
