<?php

namespace App\Modules\Help\Models;

use App\Modules\Help\Factories\HelpArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HelpArticle extends Model
{
    /** @use HasFactory<HelpArticleFactory> */
    use HasFactory;

    public const DISK = 'r2';

    public const VIDEO_DIRECTORY = 'HelpCenter';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'video_path',
        'is_published',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return HelpArticleFactory::new();
    }

    /**
     * @param  Builder<HelpArticle>  $query
     * @return Builder<HelpArticle>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function videoUrl(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        // Las URLs temporales evitan exponer públicamente el bucket R2.
        return $disk->temporaryUrl($this->video_path, now()->addHours(2));
    }
}
