<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WompiWebhookController extends Controller
{
    public function __invoke(Request $request, WompiService $wompi): JsonResponse
    {
        $event = $request->json()->all();

        if (! $wompi->isValidEventSignature($event)) {
            return response()->json(['message' => 'Firma inválida.'], 403);
        }

        if (($event['event'] ?? null) === 'transaction.updated') {
            $transaction = data_get($event, 'data.transaction');

            if (is_array($transaction)) {
                $wompi->applyTransaction($transaction);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
