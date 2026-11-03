<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\HabeasDataConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HabeasDataController extends Controller
{
    public function accept(Request $request): RedirectResponse
    {
        $user = $request->user();
        $acceptedAt = now();

        DB::transaction(function () use ($request, $user, $acceptedAt): void {
            $user->habeasDataConsents()->create([
                'policy_version' => HabeasDataConsent::CURRENT_POLICY_VERSION,
                'ip_address' => (string) $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'accepted_at' => $acceptedAt,
            ]);

            $user->forceFill(['habeas_data_accepted_at' => $acceptedAt])->save();
        });

        return back();
    }
}
