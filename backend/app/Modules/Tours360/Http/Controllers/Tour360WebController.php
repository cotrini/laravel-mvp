<?php

namespace App\Modules\Tours360\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Tours360\Models\Tour;
use App\Modules\Tours360\Models\Scene;


class Tour360WebController extends Controller
{
    /**
     * Galería de recorridos publicados.
     */
    public function index()
    {
        $tours = Tour::published()
            ->orderByDesc('id')
            ->get();

        return view('tours360::index', compact('tours'));
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

        return view('tours360::Tours.   show', compact('tour'));
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

        // Preparamos los arrays mapeados en PHP limpio
        $hotspotsData = $scene->hotspots->map(fn($h) => [
            'id' => $h->id,
            'label' => $h->label,
            'yaw' => (float) $h->yaw,
            'pitch' => (float) $h->pitch,
            'target_scene_id' => $h->target_scene_id,
        ]);

        $scenesData = $tour->scenes->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->title
        ]);

        return view('tours360::tours.viewer', compact('tour', 'scene', 'hotspotsData', 'scenesData'));
    }

    public function embed($id)
    {
        $tour = Tour::with(['scenes.hotspots.targetScene', 'scenes' => fn($q) => $q->orderBy('sort_order')])->findOrFail($id);
        
        $scene = $tour->scenes->first() ?? abort(404, 'El recorrido no tiene escenas configuradas.');

        // Mapeamos igual para el embed
        $hotspotsData = $scene->hotspots->map(fn($h) => [
            'id' => $h->id,
            'label' => $h->label,
            'yaw' => (float) $h->yaw,
            'pitch' => (float) $h->pitch,
            'target_scene_id' => $h->target_scene_id,
        ]);

        $scenesData = $tour->scenes->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->title
        ]);

        return view('tours360::tours.viewer', compact('tour', 'scene', 'hotspotsData', 'scenesData'));
    }
}
