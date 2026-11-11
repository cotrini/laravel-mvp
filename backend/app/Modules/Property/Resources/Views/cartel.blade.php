@extends('layouts.app')

@section('title', 'Cartel ' . $property->identification_code . ' - Colombia Sin Comisión')

@section('content')
    @php
        $headline = match ($property->listing_type) {
            'venta' => 'SE VENDE',
            'alquiler' => 'SE ARRIENDA',
            default => 'EN OFERTA',
        };
        $fileSlug = str($headline)->slug()->value();
    @endphp

    <style>
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            .site-header, .site-footer, .no-print { display: none !important; }
            html, body { background: #fff !important; }
            .page-shell { padding: 0 !important; }
            .cartel-wrapper { overflow: visible !important; padding: 0 !important; }
            #cartel { box-shadow: none !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>

    <div class="page-shell">
        <div class="no-print mb-6 flex flex-wrap items-center gap-3">
            <a href="{{ route('dashboard.index') }}" class="rounded-xl bg-slate-600 px-4 py-2 font-semibold text-white transition hover:bg-slate-700">
                ← Volver a mis publicaciones
            </a>
            <button type="button" onclick="window.print()" class="rounded-xl bg-emerald-600 px-5 py-2 font-semibold text-white shadow transition hover:bg-emerald-700">
                🖨️ Imprimir cartel
            </button>
            <button type="button" id="cartel-download" class="rounded-xl bg-sky-600 px-5 py-2 font-semibold text-white shadow transition hover:bg-sky-700">
                📲 Descargar para WhatsApp
            </button>
            <p class="text-sm text-slate-500">{{ $property->title }} · {{ $property->identification_code }}</p>
        </div>

        <div class="cartel-wrapper flex justify-center overflow-x-auto p-2">
            <div id="cartel" class="relative flex w-[950px] shrink-0 flex-col justify-between overflow-hidden rounded-3xl border-[10px] border-emerald-700 bg-white p-8 shadow-2xl">
                <div class="flex items-center justify-between border-b-2 border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center rounded-xl bg-amber-300 p-2 shadow-sm">
                            <svg class="h-6 w-6 text-slate-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-extrabold tracking-tight">
                            <span class="text-slate-900">Colombia</span> <span class="text-emerald-600">Sin Comisión</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Trato directo</span>
                </div>

                <div class="my-6 grid grid-cols-12 items-center gap-6">
                    <div class="col-span-8 flex flex-col justify-center">
                        <h1 class="text-4xl font-black leading-tight tracking-tight text-slate-900">
                            ¡Tu lugar especial está a un clic!
                        </h1>

                        <div class="my-5 rounded-2xl border-b-4 border-emerald-800 bg-emerald-600 px-6 py-4 text-center text-white shadow-md">
                            <span class="text-5xl font-black uppercase tracking-widest">{{ $headline }}</span>
                        </div>

                        <div class="mt-1">
                            <span class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Teléfono propietario</span>
                            @if($contactPhone)
                                <p class="mt-1 text-6xl font-black tracking-tight text-slate-900">{{ $contactPhone }}</p>
                            @else
                                <p class="mt-1 text-xl font-semibold text-slate-500">Escanea el código QR para contactar al propietario</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-span-4 flex flex-col items-center justify-center rounded-3xl border-2 border-slate-200 bg-slate-50 p-6 shadow-inner">
                        <div class="rounded-2xl bg-white p-3 shadow-md">
                            <div id="cartel-qr" class="h-[210px] w-[210px] [&_img]:h-full [&_img]:w-full" role="img" aria-label="Código QR de la publicación"></div>
                        </div>
                        <span class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-600">Escanea aquí</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3 text-center">
                    <p class="text-xs font-medium text-slate-400">
                        Publica y negocia directamente en <span class="font-bold text-slate-600">colombiasincomision.com</span>
                        · Código {{ $property->identification_code }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script
        src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"
        integrity="sha384-8FWZA6BGMXhsfO+BLtrJK0We6gg5o1JyO8xQm6peWDEUs17ACA5ziE/NIAkl9z2k"
        crossorigin="anonymous"
    ></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"
        integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <script>
        (() => {
            const qrContainer = document.getElementById('cartel-qr');

            if (typeof qrcode === 'function') {
                const qr = qrcode(0, 'M');
                qr.addData(@js($propertyUrl));
                qr.make();
                qrContainer.innerHTML = qr.createImgTag(8, 0);
            } else {
                qrContainer.textContent = 'No se pudo generar el código QR.';
            }

            document.getElementById('cartel-download').addEventListener('click', () => {
                if (typeof html2canvas !== 'function') {
                    alert('No se pudo cargar la herramienta de descarga. Revisa tu conexión e inténtalo de nuevo.');
                    return;
                }

                html2canvas(document.getElementById('cartel'), { scale: 2, useCORS: true }).then((canvas) => {
                    const link = document.createElement('a');
                    link.download = @js('cartel-'.$fileSlug.'-'.strtolower($property->identification_code).'.png');
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            });
        })();
    </script>
@endsection
