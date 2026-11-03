<?php

namespace App\Modules\Payment\Models;

use App\Modules\Payment\Factories\PaymentOrderFactory;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentOrder extends Model
{
    /** @use HasFactory<PaymentOrderFactory> */
    use HasFactory;

    public const PURPOSE_EXTRA_PUBLICATION = 'extra_publication';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_DECLINED = 'DECLINED';

    protected $fillable = [
        'user_id',
        'purpose',
        'reference',
        'amount_in_cents',
        'currency',
        'status',
        'wompi_transaction_id',
        'wompi_status',
        'payload',
        'paid_at',
    ];

    protected static function newFactory(): Factory
    {
        return PaymentOrderFactory::new();
    }

    public static function generateReference(): string
    {
        return 'PUB-'.strtoupper((string) Str::ulid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_in_cents' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
