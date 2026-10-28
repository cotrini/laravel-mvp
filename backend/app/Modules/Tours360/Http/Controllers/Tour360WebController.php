<?php

namespace App\Modules\Tours360\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Support\PublicMediaUrl;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

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
        $validated = $request->validate($this->tourRules(true), $this->tourValidationMessages());
        $property = Property::query()->findOrFail($validated['property_id']);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);
        $this->validateSceneImages($validated['scenes']);

        if ($property->tour) {
            return redirect()->route('tours360.edit', $property->tour)
                ->with('error', 'Este inmueble ya tiene un recorrido 360°.');
        }

        $storedPaths = [];

        try {
            $scenes = $this->storeSceneImages($validated['scenes'], $property, $storedPaths);

            DB::transaction(function () use ($property, $validated, $scenes): void {
                $tour = Tour::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'location' => $validated['location'] ?? $this->propertyLocation($property),
                    'cover_url' => $validated['cover_url'] ?? array_values($scenes)[0]['image_url'],
                    'is_published' => (bool) ($validated['is_published'] ?? false),
                ]);

                $tour->property()->save($property);
                $this->saveScenes($tour, $scenes);
            });
        } catch (Throwable $exception) {
            $this->cleanupR2Uploads($storedPaths);

            throw $exception;
        }

        return redirect()->route('dashboard.index')->with('success', 'El recorrido 360° se agregó al inmueble.');
    }

    public function storeSceneImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:15360'],
        ], [
            'image.uploaded' => 'No fue posible recibir la fotografía completa. Asegúrate de que no supere los 15 MB e inténtalo de nuevo.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimes' => 'La fotografía debe estar en formato JPEG o PNG.',
            'image.max' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        $image = $validated['image'];
        $extension = $this->sceneImageExtension($image);
        $path = "Tours360/{$property->id}/".Str::uuid().'.'.$extension;

        if (! Storage::disk('r2')->put($path, $image->get())) {
            throw new RuntimeException('No fue posible guardar la imagen de la escena 360°.');
        }

        return response()->json(['path' => $path]);
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
        $tour = Tour::query()->with(['property', 'scenes'])->findOrFail($id);
        $property = $tour->property ?? abort(404);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate($this->tourRules(), $this->tourValidationMessages());
        $this->validateSceneImages($validated['scenes']);
        $storedPaths = [];

        try {
            $scenes = $this->storeSceneImages($validated['scenes'], $property, $storedPaths);

            DB::transaction(function () use ($tour, $validated, $property, $scenes): void {
                $tour->fill(Arr::only($validated, ['title', 'description', 'location', 'cover_url']));

                if ($tour->isDirty('title')) {
                    $tour->slug = Str::slug($tour->title).'-'.Str::random(6);
                }

                $tour->is_published = (bool) ($validated['is_published'] ?? false);
                $tour->save();

                $this->saveScenes($tour, $scenes);

                $firstScene = array_values($scenes)[0] ?? null;
                if (blank($tour->cover_url) && isset($firstScene['image_url'])) {
                    $tour->update(['cover_url' => $firstScene['image_url']]);
                }

                $tour->property()->save($property);
            });
        } catch (Throwable $exception) {
            $this->cleanupR2Uploads($storedPaths);

            throw $exception;
        }

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
        $tour = Tour::query()->with(['property', 'scenes'])->findOrFail($id);
        $property = $tour->property ?? abort(404);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);
        $storedPaths = collect([$tour->cover_url])
            ->concat($tour->scenes->pluck('image_url'))
            ->map(fn (?string $location): ?string => $location !== null ? PublicMediaUrl::r2Path($location) : null)
            ->filter(fn (?string $path): bool => is_string($path) && str_starts_with($path, 'Tours360/'))
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($property, $tour): void {
            $property->tour()->dissociate();
            $property->save();
            $tour->delete();
        });

        if ($storedPaths !== [] && ! Storage::disk('r2')->delete($storedPaths)) {
            throw new RuntimeException('No fue posible eliminar las imágenes del recorrido 360° en Cloudflare R2.');
        }

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
            'scenes' => ['required', 'array', 'min:1', 'max:30'],
            'scenes.*.id' => ['nullable', 'integer'],
            'scenes.*.title' => ['required', 'string', 'max:255'],
            'scenes.*.caption' => ['nullable', 'string', 'max:1000'],
            'scenes.*.image_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'scenes.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:15360'],
            'scenes.*.staged_image' => ['nullable', 'string', 'max:255'],
            'scenes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tourValidationMessages(): array
    {
        return [
            'scenes.*.image.uploaded' => 'No fue posible recibir la fotografía completa. Asegúrate de que no supere los 15 MB e inténtalo de nuevo.',
            'scenes.*.image.image' => 'El archivo debe ser una imagen válida.',
            'scenes.*.image.mimes' => 'La fotografía debe estar en formato JPEG o PNG.',
            'scenes.*.image.max' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
            'scenes.max' => 'Un recorrido 360° puede tener como máximo 30 escenas.',
        ];
    }

    /**
     * @param  array<int, string>  $storedPaths
     */
    private function cleanupR2Uploads(array $storedPaths): void
    {
        if ($storedPaths === []) {
            return;
        }

        try {
            if (! Storage::disk('r2')->delete($storedPaths)) {
                report(new RuntimeException('No fue posible limpiar los archivos R2 tras un fallo de carga de escenas.'));
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
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

    /**
     * @param  array<int, array{image_url?: string|null, image?: UploadedFile|null, staged_image?: string|null}>  $scenes
     */
    private function validateSceneImages(array $scenes): void
    {
        foreach ($scenes as $index => $scene) {
            $imageFile = $scene['image'] ?? null;
            $stagedImage = $scene['staged_image'] ?? null;

            if (blank($scene['image_url'] ?? null) && blank($stagedImage) && ! ($imageFile instanceof UploadedFile)) {
                throw ValidationException::withMessages([
                    "scenes.{$index}.image_url" => 'Agrega un enlace HTTPS o sube una imagen para cada escena.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $scenes
     * @param  array<int, string>  $storedPaths
     * @return array<int, array<string, mixed>>
     */
    private function storeSceneImages(
        array $scenes,
        Property $property,
        array &$storedPaths,
    ): array {
        foreach ($scenes as $index => $scene) {
            $imageFile = $scene['image'] ?? null;
            $stagedImage = $scene['staged_image'] ?? null;
            unset($scene['image']);
            unset($scene['staged_image']);

            if (filled($stagedImage)) {
                if (! $this->isStagedImagePath($stagedImage, $property) || ! Storage::disk('r2')->exists($stagedImage)) {
                    throw ValidationException::withMessages([
                        "scenes.{$index}.image_url" => 'La fotografía cargada ya no está disponible. Vuelve a seleccionarla.',
                    ]);
                }

                $scene['image_url'] = Storage::disk('r2')->url($stagedImage);
            } elseif ($imageFile instanceof UploadedFile) {
                $extension = $this->sceneImageExtension($imageFile);
                $path = "Tours360/{$property->id}/".Str::uuid().'.'.$extension;

                if (! Storage::disk('r2')->put($path, $imageFile->get())) {
                    throw new RuntimeException('No fue posible guardar la imagen de la escena 360°.');
                }

                $storedPaths[] = $path;
                $scene['image_url'] = Storage::disk('r2')->url($path);
            }

            $scenes[$index] = $scene;
        }

        return $scenes;
    }

    private function isStagedImagePath(string $path, Property $property): bool
    {
        $prefix = "Tours360/{$property->id}/";

        if (! str_starts_with($path, $prefix)) {
            return false;
        }

        $relativePath = substr($path, strlen($prefix));

        if (str_starts_with($relativePath, 'pending/')) {
            $relativePath = substr($relativePath, strlen('pending/'));
        }

        if (str_contains($relativePath, '/')) {
            return false;
        }

        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $uuid = pathinfo($relativePath, PATHINFO_FILENAME);

        return in_array($extension, ['jpg', 'png'], true)
            && Str::isUuid($uuid);
    }

    private function sceneImageExtension(UploadedFile $image): string
    {
        return match ($image->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw ValidationException::withMessages([
                'image' => 'La fotografía debe estar en formato JPEG o PNG.',
            ]),
        };
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
