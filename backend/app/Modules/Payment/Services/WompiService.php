<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\PaymentOrder;
use App\Modules\User\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiService
{
    /**
     * @return array{public_key: string, private_key: string, events_secret: string, integrity_secret: string, base_url: string, checkout_url: string, publication_price_in_cents: int, currency: string}
     */
    private function config(): array
    {
        return config('services.wompi');
    }

    public function publicationPriceInCents(): int
    {
        return (int) $this->config()['publication_price_in_cents'];
    }

    public function createPublicationOrder(User $user): PaymentOrder
    {
        return PaymentOrder::query()->create([
            'user_id' => $user->id,
            'purpose' => PaymentOrder::PURPOSE_EXTRA_PUBLICATION,
            'reference' => PaymentOrder::generateReference(),
            'amount_in_cents' => $this->publicationPriceInCents(),
            'currency' => $this->config()['currency'],
            'status' => PaymentOrder::STATUS_PENDING,
        ]);
    }

    /**
     * Web Checkout URL con firma de integridad: SHA256(reference + amountInCents + currency + integritySecret).
     */
    public function checkoutUrl(PaymentOrder $order, string $redirectUrl): string
    {
        $config = $this->config();
        $signature = hash(
            'sha256',
            $order->reference.$order->amount_in_cents.$order->currency.$config['integrity_secret'],
        );

        return $config['checkout_url'].'?'.http_build_query([
            'public-key' => $config['public_key'],
            'currency' => $order->currency,
            'amount-in-cents' => $order->amount_in_cents,
            'reference' => $order->reference,
            'signature:integrity' => $signature,
            'redirect-url' => $redirectUrl,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Valida el checksum de un evento: SHA256(valores de signature.properties + timestamp + events_secret).
     *
     * @param  array<string, mixed>  $event
     */
    public function isValidEventSignature(array $event): bool
    {
        $secret = (string) $this->config()['events_secret'];
        $properties = data_get($event, 'signature.properties');
        $checksum = data_get($event, 'signature.checksum');
        $timestamp = $event['timestamp'] ?? null;

        if ($secret === '' || ! is_array($properties) || ! is_string($checksum) || ! is_scalar($timestamp)) {
            return false;
        }

        $concatenated = '';

        foreach ($properties as $property) {
            $value = is_string($property) ? data_get($event['data'] ?? [], $property) : null;

            if (! is_scalar($value)) {
                return false;
            }

            $concatenated .= $value;
        }

        $expected = hash('sha256', $concatenated.$timestamp.$secret);

        return hash_equals($expected, strtolower($checksum));
    }

    /**
     * Consulta la transacción directamente a Wompi (no se confía en los parámetros de la redirección).
     *
     * @return array<string, mixed>|null
     */
    public function fetchTransaction(string $transactionId): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get(rtrim($this->config()['base_url'], '/').'/transactions/'.rawurlencode($transactionId));
        } catch (ConnectionException $exception) {
            Log::warning('Wompi: no fue posible consultar la transacción.', ['transaction_id' => $transactionId]);

            return null;
        }

        $transaction = $response->successful() ? $response->json('data') : null;

        return is_array($transaction) ? $transaction : null;
    }

    /**
     * Aplica el estado de una transacción de Wompi a su orden. Es idempotente y nunca degrada una orden aprobada.
     *
     * @param  array<string, mixed>  $transaction
     */
    public function applyTransaction(array $transaction): ?PaymentOrder
    {
        $reference = $transaction['reference'] ?? null;

        if (! is_string($reference)) {
            return null;
        }

        return DB::transaction(function () use ($transaction, $reference): ?PaymentOrder {
            $order = PaymentOrder::query()->where('reference', $reference)->lockForUpdate()->first();

            if (! $order) {
                return null;
            }

            if (
                (int) ($transaction['amount_in_cents'] ?? 0) !== $order->amount_in_cents
                || ($transaction['currency'] ?? null) !== $order->currency
            ) {
                Log::warning('Wompi: monto o moneda no coinciden con la orden.', ['reference' => $reference]);

                return $order;
            }

            if ($order->isApproved()) {
                return $order;
            }

            $wompiStatus = (string) ($transaction['status'] ?? '');
            $status = $this->mapStatus($wompiStatus);

            $order->fill([
                'status' => $status,
                'wompi_status' => $wompiStatus,
                'wompi_transaction_id' => $transaction['id'] ?? $order->wompi_transaction_id,
                'payload' => $transaction,
                'paid_at' => $status === PaymentOrder::STATUS_APPROVED ? now() : null,
            ])->save();

            return $order;
        });
    }

    public function mapStatus(string $wompiStatus): string
    {
        return match ($wompiStatus) {
            'APPROVED' => PaymentOrder::STATUS_APPROVED,
            'DECLINED', 'VOIDED', 'ERROR' => PaymentOrder::STATUS_DECLINED,
            default => PaymentOrder::STATUS_PENDING,
        };
    }
}
