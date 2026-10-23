<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\Scene;
use Illuminate\Http\Request;

class Tour360ApiController extends Controller
{
    /**
     * Galería de recorridos publicados.
     */
    public function index()
    {
        $tours = Tour::published()
            ->orderByDesc('id')
            ->get();

        return view('tours::index', compact('tours'));
    }

    /**
     * Página de un recorrido con la lista de escenas.
     */
    public function show(string $slug)
    {
        $tour = Tour::published()
            ->where('slug', $slug)
            ->with(['scenes' => fn($q) => $q->orderBy('sort_order')])
            ->firstOrFail();

        return view('tours::show', compact('tour'));
    }

    /**
     * Visor 360 de una escena concreta dentro de un tour.
     */
    public function viewer(string $slug, int $sceneId)
    {
        $tour = Tour::published()
            ->where('slug', $slug)
            ->with(['scenes.hotspots.targetScene', 'scenes' => fn($q) => $q->orderBy('sort_order')])
            ->firstOrFail();

        $scene = $tour->scenes->firstWhere('id', $sceneId)
            ?? abort(404, 'La escena no pertenece a este recorrido.');

        return view('tours::viewer', compact('tour', 'scene'));
    }
}
