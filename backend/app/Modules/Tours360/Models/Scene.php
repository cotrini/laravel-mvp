<?php

namespace App\Modules\Tours360\Models;

use App\Modules\Media\Support\PublicMediaUrl;
use App\Modules\Tours360\Factories\SceneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scene extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return SceneFactory::new();
    }

    protected $fillable = [
        'tour_id',
        'title',
        'caption',
        'image_url',
        'sort_order',
    ];

    public function getPublicImageUrlAttribute(): string
    {
        return PublicMediaUrl::from($this->image_url);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class);
    }
}
