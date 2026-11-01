<?php

namespace App\Modules\Credito\Models;

use Illuminate\Database\Eloquent\Model;

class BankInterestRate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_label',
        'annual_effective_rate',
    ];

    protected function casts(): array
    {
        return [
            'annual_effective_rate' => 'decimal:2',
        ];
    }
}
