<?php

namespace App\Modules\Tours360\Models;

use App\Modules\Media\Support\PublicMediaUrl;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Factories\TourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tour extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return TourFactory::new();
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
            $tour->slug ??= Str::slug($tour->title).'-'.Str::random(6);
        });
    }

    public function getPublicCoverUrlAttribute(): ?string
    {
        return filled($this->cover_url) ? PublicMediaUrl::from($this->cover_url) : null;
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('sort_order');
    }

    public function property(): HasOne
    {
        return $this->hasOne(Property::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
