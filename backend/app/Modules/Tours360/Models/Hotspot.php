<?php

namespace App\Modules\Tours360\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotspot extends Model
{
    use HasFactory;
    protected static function newFactory()
    {
        return \App\Modules\Tours360\Factories\HotspotFactory::new();
    }

    protected $fillable = [
        'scene_id',
        'target_scene_id',
        'label',
        'yaw',
        'pitch',
    ];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function targetScene(): BelongsTo
    {
        return $this->belongsTo(Scene::class, 'target_scene_id');
    }
}
