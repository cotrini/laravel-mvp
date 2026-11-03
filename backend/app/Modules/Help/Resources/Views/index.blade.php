@extends('layouts.app')

@section('title', 'Centro de Ayuda')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Centro de Ayuda</h1>
            <p class="mt-2 text-slate-600">Tutoriales y consejos para publicar, vender y arrendar tu propiedad sin comisión.</p>
        </header>

        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @can('manage-users')
            <details class="mb-10 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <summary class="cursor-pointer font-semibold text-slate-800">Agregar artículo</summary>
                <form method="POST" action="{{ route('help.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4">
                    @csrf
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Título" required class="rounded-lg border border-slate-300 px-3 py-2">
                    <select name="category" class="rounded-lg border border-slate-300 px-3 py-2">
                        <option value="tutorial">Tutorial</option>
                        <option value="consejo">Consejo</option>
                    </select>
                    <textarea name="excerpt" rows="2" maxlength="500" placeholder="Resumen breve" required class="rounded-lg border border-slate-300 px-3 py-2">{{ old('excerpt') }}</textarea>
                    <textarea name="body" rows="5" placeholder="Contenido (opcional)" class="rounded-lg border border-slate-300 px-3 py-2">{{ old('body') }}</textarea>
                    <input type="file" name="video" accept="video/mp4,video/webm" class="text-sm">
                    @if ($errors->any())
                        <ul class="text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <button type="submit" class="w-fit rounded-lg bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700">Publicar</button>
                </form>
            </details>
        @endcan

        @forelse ($articles as $article)
            @if ($loop->first)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @endif
            <article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                @if ($videoUrl = $article->videoUrl())
                    <video class="aspect-video w-full bg-black" controls preload="metadata" playsinline>
                        <source src="{{ $videoUrl }}" type="video/mp4">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                @endif
                <div class="flex flex-1 flex-col gap-2 p-5">
                    <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase text-blue-700">{{ $article->category }}</span>
                    <h2 class="text-lg font-bold text-slate-900">{{ $article->title }}</h2>
                    <p class="text-sm text-slate-600">{{ $article->excerpt }}</p>
                    @if ($article->body)
                        <details class="text-sm text-slate-700">
                            <summary class="cursor-pointer font-medium text-blue-600">Leer más</summary>
                            <div class="mt-2 whitespace-pre-line">{{ $article->body }}</div>
                        </details>
                    @endif
                    @can('manage-users')
                        <form method="POST" action="{{ route('help.destroy', $article) }}" class="mt-auto pt-2" onsubmit="return confirm('¿Eliminar este artículo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Eliminar</button>
                        </form>
                    @endcan
                </div>
            </article>
            @if ($loop->last)
                </div>
            @endif
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Aún no hay artículos publicados.</p>
        @endforelse

        <div class="mt-8">{{ $articles->links() }}</div>
    </section>
@endsection
