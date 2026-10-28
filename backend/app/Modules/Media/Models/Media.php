<?php

namespace App\Modules\Media\Models;

use App\Modules\Media\Factories\MediaFactory;
use App\Modules\Media\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MediaFactory::new();
    }

    // Nombre explícito de la tabla en MySQL
    protected $table = 'media';

    // Campos habilitados para asignación masiva
    protected $fillable = [
        'property_id',
        'mediable_id',
        'mediable_type',
        'type',
        'url_or_path',
        'title',
        'order',
        'is_primary',
        'meta',
    ];

    // Cásting automático de atributos (Fundamental para trabajar con JSON y Booleanos)
    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
        'meta' => 'array', // Convierte el JSON de MySQL a un Array de PHP automáticamente
    ];

    public function getPublicUrlAttribute(): string
    {
        return PublicMediaUrl::from($this->url_or_path);
    }

    /**
     * Relación directa con la Propiedad.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relación polimórfica (para asociar archivos a Propiedades, Usuarios, etc.).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
