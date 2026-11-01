@extends('layouts.app')

@section('title', 'Verifica tu correo - Colombia Sin Comisión')

@section('content')
    <main class="mx-auto max-w-xl px-4 py-16">
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Verifica tu correo electrónico</h1>
            <p class="mt-3 text-slate-600">
                Antes de continuar, confirma tu dirección de correo con el enlace que te enviamos.
            </p>

            @if (session('error'))
                <p class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">
                    {{ session('error') }}
                </p>
            @endif

            @if (session('success'))
                <p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700" role="status">
                    {{ session('success') }}
                </p>
            @endif

            <p
                id="verification-countdown"
                class="mt-4 text-sm text-slate-600"
                role="status"
                aria-live="polite"
                data-remaining-seconds="{{ $resendCooldownSeconds }}"
                @if ($resendCooldownSeconds === 0) hidden @endif
            >
                Podrás pedir otro correo en <span data-countdown-value>{{ $resendCooldownSeconds }}</span> segundos.
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit" data-resend-button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Reenviar enlace de verificación
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="text-sm font-medium text-slate-600 underline">
                    Cerrar sesión
                </button>
            </form>
        </section>
    </main>

    <script>
        (() => {
            const countdown = document.querySelector('#verification-countdown');
            const button = document.querySelector('[data-resend-button]');

            if (!countdown || !button) {
                return;
            }

            let remainingSeconds = Number(countdown.dataset.remainingSeconds);
            const updateCountdown = () => {
                if (remainingSeconds <= 0) {
                    countdown.hidden = true;
                    button.disabled = false;
                    return;
                }

                button.disabled = true;
                countdown.hidden = false;
                countdown.querySelector('[data-countdown-value]').textContent = remainingSeconds;
                remainingSeconds -= 1;
            };

            const hasCooldown = remainingSeconds > 0;
            updateCountdown();

            if (hasCooldown) {
                window.setInterval(updateCountdown, 1000);
            }
        })();
    </script>
@endsection
