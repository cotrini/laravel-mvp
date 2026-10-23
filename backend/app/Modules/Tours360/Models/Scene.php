<?php

namespace App\Modules\Tours360\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scene extends Model
{
    use HasFactory;
    protected static function newFactory()
    {
        return \App\Modules\Tours360\Factories\SceneFactory::new();
    }

    protected $fillable = [
        'tour_id',
        'title',
        'caption',
        'image_url',
        'sort_order',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class);
    }
}
