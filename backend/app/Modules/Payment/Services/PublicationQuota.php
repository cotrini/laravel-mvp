<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\PaymentOrder;
use App\Modules\User\Models\User;

class PublicationQuota
{
    public const FREE_PROPERTIES_PER_USER = 1;

    public function allowedPropertiesFor(User $user): int
    {
        if ($user->isAdmin()) {
            return PHP_INT_MAX;
        }

        $paidSlots = $user->paymentOrders()
            ->where('purpose', PaymentOrder::PURPOSE_EXTRA_PUBLICATION)
            ->approved()
            ->count();

        return self::FREE_PROPERTIES_PER_USER + $paidSlots;
    }

    public function canPublish(User $user): bool
    {
        return $user->properties()->count() < $this->allowedPropertiesFor($user);
    }
}
