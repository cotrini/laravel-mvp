<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MediaWebController extends Controller
{
    /**
     * Muestra una lista de todos los archivos multimedia.
     */
    public function index()
    {
        $mediaItems = Media::latest()->paginate(15);

        return view('media::index', compact('mediaItems'));
    }

    /**
     * Muestra el formulario para crear un nuevo recurso multimedia.
     */
    public function create()
    {
        return view('media::create');
    }

    /**
     * Almacena un nuevo archivo multimedia en el servidor y en la base de datos.
     */
    public function store(Request $request)
    {
        $type = $request->input('type');
        $fileRules = match ($type) {
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'blueprint' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240'],
            'video' => ['required', 'file', 'mimes:mp4', 'extensions:mp4', 'max:102400'],
            'virtual_tour_360' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
            default => ['required', 'file', 'max:10240'],
        };

        $validated = $request->validate([
            'file' => $fileRules,
            'type' => ['required', 'string', 'max:50'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $file = $validated['file'];
        $property = Property::query()->findOrFail($validated['property_id']);
        $directory = $this->storageDirectory($validated['type'], $property->id);
        $path = $file->store($directory, 'r2');

        if (! is_string($path)) {
            throw new RuntimeException('No fue posible guardar el archivo multimedia.');
        }

        try {
            $property->media()->create([
                'mediable_type' => Property::class,
                'mediable_id' => $property->id,
                'type' => $validated['type'],
                'url_or_path' => $path,
                'title' => $validated['title'] ?? $file->getClientOriginalName(),
                'is_primary' => $validated['is_primary'] ?? false,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('r2')->delete($path);

            throw $exception;
        }

        return back()->with('success', 'Archivo multimedia subido exitosamente.');
    }

    /**
     * Muestra los detalles de un archivo multimedia específico.
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);

        return view('media::show', compact('media'));
    }

    /**
     * Muestra el formulario de edición para un archivo multimedia.
     */
    public function edit($id)
    {
        $media = Media::findOrFail($id);

        return view('media::edit', compact('media'));
    }

    /**
     * Actualiza la información o metadatos de un archivo multimedia.
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'is_primary' => 'boolean',
        ]);

        $media->update([
            'title' => $request->input('title', $media->title),
            'type' => $request->input('type', $media->type),
            'is_primary' => $request->input('is_primary', $media->is_primary),
        ]);

        return redirect()->route('dashboard.index')->with('success', 'Multimedia actualizada correctamente.');
    }

    /**
     * Elimina el archivo multimedia tanto del servidor como de la base de datos.
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        $path = str_replace('\\', '/', $media->url_or_path);
        $r2Directory = $this->storageDirectory($media->type, $media->property_id);

        if (str_starts_with($path, $r2Directory.'/')) {
            Storage::disk('r2')->delete($path);
        } elseif ($media->type === 'blueprint' && str_starts_with($path, "properties/{$media->property_id}/blueprints/")) {
            Storage::disk('local')->delete($path);
        } elseif (str_starts_with($path, 'storage/properties/') && $media->type === 'video') {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        } elseif ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }

        // Eliminar registro de la base de datos
        $media->delete();

        return back()->with('success', 'Archivo multimedia eliminado correctamente.');
    }

    private function storageDirectory(string $type, int $propertyId): string
    {
        $folder = match ($type) {
            'blueprint' => 'PlansPdf',
            'video' => 'Videos',
            'virtual_tour_360', 'model_3d' => 'Tours360',
            default => 'Pictures',
        };

        return "{$folder}/{$propertyId}";
    }
}
