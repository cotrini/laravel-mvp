<?php

namespace App\Modules\Tours360\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Tour360WebController extends Controller
{
    public function index(): View
    {
        $tours = Tour::published()
            ->with('scenes')
            ->latest('id')
            ->get();

        return view('tours360::Tours.index', compact('tours'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
        ]);

        $property = Property::query()->findOrFail($validated['property_id']);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        if ($property->tour) {
            return redirect()->route('tours360.edit', $property->tour);
        }

        return view('tours360::Tours.form', compact('property'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->tourRules(true));
        $property = Property::query()->findOrFail($validated['property_id']);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        if ($property->tour) {
            return redirect()->route('tours360.edit', $property->tour)
                ->with('error', 'Este inmueble ya tiene un recorrido 360°.');
        }

        DB::transaction(function () use ($property, $validated): void {
            $tour = Tour::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'] ?? $this->propertyLocation($property),
                'cover_url' => $validated['cover_url'] ?? array_values($validated['scenes'])[0]['image_url'],
                'is_published' => (bool) ($validated['is_published'] ?? false),
            ]);

            $tour->property()->save($property);
            $this->saveScenes($tour, $validated['scenes']);
        });

        return redirect()->route('dashboard.index')->with('success', 'El recorrido 360° se agregó al inmueble.');
    }

    public function show(string $slug): View
    {
        $tour = Tour::published()
            ->where('slug', $slug)
            ->with('scenes')
            ->firstOrFail();

        return view('tours360::Tours.show', compact('tour'));
    }

    public function viewer(string $slug, int $sceneId): View
    {
        $tour = Tour::published()
            ->where('slug', $slug)
            ->with(['scenes.hotspots.targetScene', 'scenes'])
            ->firstOrFail();

        $scene = $tour->scenes->firstWhere('id', $sceneId)
            ?? abort(404, 'La escena no pertenece a este recorrido.');

        $hotspotsData = $scene->hotspots->map(fn ($hotspot) => [
            'id' => $hotspot->id,
            'label' => $hotspot->label,
            'yaw' => (float) $hotspot->yaw,
            'pitch' => (float) $hotspot->pitch,
            'target_scene_id' => $hotspot->target_scene_id,
        ]);

        $scenesData = $tour->scenes->map(fn ($tourScene) => [
            'id' => $tourScene->id,
            'title' => $tourScene->title,
        ]);

        return view('tours360::Tours.viewer', compact('tour', 'scene', 'hotspotsData', 'scenesData'));
    }

    public function embed(int $id): View
    {
        $tour = Tour::query()
            ->with(['scenes.hotspots.targetScene', 'scenes'])
            ->findOrFail($id);

        $scene = $tour->scenes->first()
            ?? abort(404, 'El recorrido no tiene escenas configuradas.');

        $hotspotsData = $scene->hotspots->map(fn ($hotspot) => [
            'id' => $hotspot->id,
            'label' => $hotspot->label,
            'yaw' => (float) $hotspot->yaw,
            'pitch' => (float) $hotspot->pitch,
            'target_scene_id' => $hotspot->target_scene_id,
        ]);

        $scenesData = $tour->scenes->map(fn ($tourScene) => [
            'id' => $tourScene->id,
            'title' => $tourScene->title,
        ]);

        return view('tours360::Tours.viewer', compact('tour', 'scene', 'hotspotsData', 'scenesData'));
    }

    public function edit(Request $request, int $id): View
    {
        $tour = Tour::query()->with(['scenes', 'property'])->findOrFail($id);
        $property = $tour->property ?? abort(404);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        return view('tours360::Tours.form', compact('tour', 'property'));
    }

    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tour = Tour::query()->with('property')->findOrFail($id);
        $property = $tour->property ?? abort(404);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate($this->tourRules());

        DB::transaction(function () use ($tour, $validated, $property): void {
            $tour->fill(Arr::only($validated, ['title', 'description', 'location', 'cover_url']));

            if ($tour->isDirty('title')) {
                $tour->slug = Str::slug($tour->title).'-'.Str::random(6);
            }

            $tour->is_published = (bool) ($validated['is_published'] ?? false);
            $tour->save();

            $this->saveScenes($tour, $validated['scenes']);

            $firstScene = array_values($validated['scenes'])[0] ?? null;
            if (blank($tour->cover_url) && isset($firstScene['image_url'])) {
                $tour->update(['cover_url' => $firstScene['image_url']]);
            }

            $tour->property()->save($property);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Recorrido y escenas actualizados correctamente.',
                'data' => $tour->fresh('scenes'),
            ]);
        }

        return redirect()->route('dashboard.index')->with('success', 'El recorrido 360° se actualizó correctamente.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $tour = Tour::query()->with('property')->findOrFail($id);
        $property = $tour->property ?? abort(404);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        DB::transaction(function () use ($property, $tour): void {
            $property->tour()->dissociate();
            $property->save();
            $tour->delete();
        });

        return redirect()->route('dashboard.index')->with('success', 'El recorrido 360° se eliminó del inmueble.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function tourRules(bool $requiresProperty = false): array
    {
        return [
            'property_id' => $requiresProperty ? ['required', 'integer', 'exists:properties,id'] : ['sometimes', 'integer', 'exists:properties,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'cover_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'is_published' => ['sometimes', 'boolean'],
            'scenes' => ['required', 'array', 'min:1', 'max:50'],
            'scenes.*.id' => ['nullable', 'integer'],
            'scenes.*.title' => ['required', 'string', 'max:255'],
            'scenes.*.caption' => ['nullable', 'string', 'max:1000'],
            'scenes.*.image_url' => ['required', 'url', 'starts_with:https://', 'max:2048'],
            'scenes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @param  array<int, array{id?: int|null, title: string, caption?: string|null, image_url: string, sort_order?: int|null}>  $scenes
     */
    private function saveScenes(Tour $tour, array $scenes): void
    {
        foreach ($scenes as $index => $sceneData) {
            $scene = isset($sceneData['id'])
                ? $tour->scenes()->find($sceneData['id'])
                : null;

            abort_if(isset($sceneData['id']) && ! $scene, 404);

            $scene ??= $tour->scenes()->make();
            $scene->fill([
                'title' => $sceneData['title'],
                'caption' => $sceneData['caption'] ?? null,
                'image_url' => $sceneData['image_url'],
                'sort_order' => $sceneData['sort_order'] ?? $index,
            ]);
            $scene->save();
        }
    }

    private function propertyLocation(Property $property): string
    {
        return implode(', ', array_filter([
            $property->neighborhood,
            $property->city,
            $property->department,
        ]));
    }
}
