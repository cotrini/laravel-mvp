<?php

namespace App\Modules\Property\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyRequest extends Model
{
    public const TYPE_VERIFICATION = 'verification';

    public const TYPE_VIDEO = 'video';

    public const TYPE_TOUR_360 = 'tour_360';

    public const TYPE_VIDEO_AND_TOUR_360 = 'video_and_tour_360';

    public const TYPE_BLUEPRINT = 'blueprint';

    public const TYPE_MEDIA_PACKAGE = 'media_package';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'property_id',
        'user_id',
        'type',
        'status',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
