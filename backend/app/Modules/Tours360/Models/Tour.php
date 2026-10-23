<?php

namespace App\Modules\Tours360\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Modules\Tours360\Factories\TourFactory;

class Tour extends Model
{
    use HasFactory;
    protected static function newFactory()
    {
        return \App\Modules\Tours360\Factories\TourFactory::new();
    }

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_url',
        'location',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tour $tour) {
            $tour->slug ??= Str::slug($tour->title) . '-' . Str::random(6);
        });
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

}
