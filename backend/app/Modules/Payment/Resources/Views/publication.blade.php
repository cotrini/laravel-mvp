@extends('layouts.app')

@section('title', 'Publicar otra propiedad - Colombia Sin Comisión')

@section('content')
<section class="mx-auto max-w-xl px-4 py-12">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">Publica otra propiedad</h1>
        <p class="mt-3 text-slate-600">
            Tu cuenta incluye <strong>1 propiedad publicada gratis</strong>. Para publicar una propiedad adicional
            realiza un pago único a través de Wompi (Bancolombia).
        </p>

        @foreach (['success' => 'emerald', 'status' => 'amber', 'error' => 'red'] as $key => $color)
            @if (session($key))
                <p role="alert" class="mt-4 rounded-lg bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-800">{{ session($key) }}</p>
            @endif
        @endforeach

        <dl class="mt-6 flex items-center justify-between rounded-xl bg-slate-50 px-5 py-4">
            <dt class="font-medium text-slate-700">Publicación adicional</dt>
            <dd class="text-xl font-bold text-slate-900">${{ number_format($amountInCents / 100, 0, ',', '.') }} COP</dd>
        </dl>

        <form method="POST" action="{{ route('payments.publication.store') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">
                Pagar con Wompi
            </button>
        </form>

        @if ($pendingOrder)
            <p class="mt-4 text-sm text-slate-500">
                Tienes un pago pendiente (ref. {{ $pendingOrder->reference }}). Si ya pagaste, se habilitará automáticamente al confirmarse.
            </p>
        @endif
    </div>
</section>
@endsection
