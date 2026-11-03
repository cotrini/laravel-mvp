<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\PaymentOrder;
use App\Modules\Payment\Services\PublicationQuota;
use App\Modules\Payment\Services\WompiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentOrderController extends Controller
{
    public function __construct(
        private readonly WompiService $wompi,
        private readonly PublicationQuota $quota,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($this->quota->canPublish($user)) {
            return redirect()->route('properties.create');
        }

        return view('payments::publication', [
            'amountInCents' => $this->wompi->publicationPriceInCents(),
            'pendingOrder' => $user->paymentOrders()
                ->where('status', PaymentOrder::STATUS_PENDING)
                ->latest()
                ->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->quota->canPublish($user)) {
            return redirect()->route('properties.create');
        }

        $order = $this->wompi->createPublicationOrder($user);

        return redirect()->away($this->wompi->checkoutUrl($order, route('payments.return')));
    }

    /**
     * Wompi redirige aquí tras el pago; el estado se confirma consultando la API, no con los parámetros recibidos.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        $transactionId = $request->query('id');

        if (is_string($transactionId) && $transactionId !== '') {
            $transaction = $this->wompi->fetchTransaction($transactionId);

            if ($transaction !== null) {
                $this->wompi->applyTransaction($transaction);
            }
        }

        $user = $request->user();

        if ($this->quota->canPublish($user)) {
            return redirect()->route('properties.create')
                ->with('success', 'Pago aprobado. Ya puedes publicar tu nueva propiedad.');
        }

        $hasPendingOrder = $user->paymentOrders()->where('status', PaymentOrder::STATUS_PENDING)->exists();

        return redirect()->route('payments.publication.show')->with(
            $hasPendingOrder ? 'status' : 'error',
            $hasPendingOrder
                ? 'Tu pago está en proceso. Te habilitaremos la publicación apenas Wompi lo confirme.'
                : 'El pago no fue aprobado. Puedes intentarlo de nuevo.',
        );
    }
}
