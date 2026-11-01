@extends('layouts.app')

@section('title', 'Solicitudes de propiedades - Colombia Sin Comisión')

@section('content')
    <main class="mx-auto min-h-screen max-w-6xl space-y-6 px-4 py-10 sm:px-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-700">Administración</p>
                <h1 class="mt-1 text-3xl font-extrabold text-slate-900">Solicitudes de propiedades</h1>
                <p class="mt-2 text-sm text-slate-600">Revisa solicitudes de verificación, video y recorrido 360° enviadas por los propietarios.</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="font-semibold text-blue-700 underline">Volver al panel</a>
        </header>

        @foreach(['success', 'warning'] as $noticeType)
            @if(session($noticeType))
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold text-blue-800" role="status">
                    {{ session($noticeType) }}
                </div>
            @endif
        @endforeach

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($propertyRequests->isEmpty())
                <p class="p-6 text-sm text-slate-600">No hay solicitudes registradas.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-5 py-4">Propiedad</th>
                                <th scope="col" class="px-5 py-4">Solicitud</th>
                                <th scope="col" class="px-5 py-4">Usuario</th>
                                <th scope="col" class="px-5 py-4">Fecha</th>
                                <th scope="col" class="px-5 py-4">Estado</th>
                                <th scope="col" class="px-5 py-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($propertyRequests as $propertyRequest)
                                @php
                                    $requestLabel = match ($propertyRequest->type) {
                                        'verification' => 'Verificación presencial',
                                        'video' => 'Video',
                                        'tour_360' => 'Recorrido 360°',
                                        'video_and_tour_360' => 'Video y recorrido 360°',
                                        'blueprint' => 'Planos arquitectónicos',
                                        default => ucfirst($propertyRequest->type),
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        @if($propertyRequest->property)
                                            <a class="font-bold text-blue-700 underline" href="{{ route('properties.show', $propertyRequest->property) }}">
                                                {{ $propertyRequest->property->title }}
                                            </a>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $propertyRequest->property->city }}</span>
                                        @else
                                            <span class="text-slate-500">Propiedad eliminada</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $requestLabel }}</td>
                                    <td class="px-5 py-4 text-slate-600">
                                        {{ $propertyRequest->user?->name ?? 'Usuario no disponible' }}
                                        <span class="mt-1 block text-xs">{{ $propertyRequest->user?->email }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $propertyRequest->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $propertyRequest->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $propertyRequest->status === 'pending' ? 'Pendiente' : 'Atendida' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($propertyRequest->status === 'pending')
                                            <form action="{{ route('admin.property-requests.complete', $propertyRequest) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">
                                                    Marcar atendida
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-500">Completada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $propertyRequests->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
