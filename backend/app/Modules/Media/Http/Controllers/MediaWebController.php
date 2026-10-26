<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'file' => 'required|file|max:10240', // Máximo 10MB
            'type' => 'required|string',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/media');

            // Crear la carpeta si no existe físicamente
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $urlOrPath = 'uploads/media/' . $filename;

            Media::create([
                'property_id' => $request->input('property_id'),
                'type' => $request->input('type', 'image'),
                'url_or_path' => $urlOrPath,
                'title' => $request->input('title', $file->getClientOriginalName()),
                'is_primary' => $request->input('is_primary', false),
            ]);
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

        if ($media->type === 'blueprint'
            && $media->property_id
            && str_starts_with($media->url_or_path, "properties/{$media->property_id}/blueprints/")) {
            Storage::disk('local')->delete($media->url_or_path);
        } elseif ($media->url_or_path && File::exists(public_path($media->url_or_path))) {
            File::delete(public_path($media->url_or_path));
        }

        // Eliminar registro de la base de datos
        $media->delete();

        return back()->with('success', 'Archivo multimedia eliminado correctamente.');
    }
}
