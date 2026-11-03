<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabeasDataConsent extends Model
{
    /** Versión vigente del aviso; al cambiarla se vuelve a solicitar el consentimiento. */
    public const CURRENT_POLICY_VERSION = '1.0';

    protected $fillable = ['user_id', 'policy_version', 'ip_address', 'user_agent', 'accepted_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
