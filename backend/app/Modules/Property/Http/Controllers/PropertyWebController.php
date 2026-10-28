<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PropertyMediaRequest as PropertyMediaRequestMail;
use App\Modules\Media\Support\PublicMediaUrl;
use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Support\Mp4DurationValidator;
use App\Modules\Property\Support\PdfPageCountValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PropertyWebController extends Controller
{
    private const MAX_BLUEPRINT_SIZE_KB = 10240;

    private const MAX_BLUEPRINT_PAGES = 10;

    private const MAX_PROPERTY_IMAGE_SIZE_KB = 15360;

    private const MAX_PROPERTY_IMAGES = 20;

    public function __construct(
        private readonly Mp4DurationValidator $durationValidator,
        private readonly PdfPageCountValidator $pdfPageCountValidator,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'destination' => ['nullable', 'string', 'max:120'],
            'property_type' => ['nullable', 'in:apartamento,casa,finca,lote,local,oficina,comercial,bodega'],
            'listing_type' => ['nullable', 'in:venta,alquiler'],
            'bedrooms' => ['nullable', 'integer', 'between:0,100'],
            'bathrooms' => ['nullable', 'integer', 'between:0,100'],
            'stratum' => ['nullable', 'integer', 'between:1,6'],
            'min_price' => ['nullable', 'numeric', 'min:1000000', Rule::when($request->input('listing_type') === 'alquiler', ['max:100000000'], ['max:50000000000']), Rule::when($request->filled('max_price'), ['lte:max_price'])],
            'max_price' => ['nullable', 'numeric', 'min:1000000', Rule::when($request->input('listing_type') === 'alquiler', ['max:100000000'], ['max:50000000000']), Rule::when($request->filled('min_price'), ['gte:min_price'])],
        ]);

        $destination = trim($filters['destination'] ?? '');
        $propertyType = $filters['property_type'] ?? '';
        $listingType = $filters['listing_type'] ?? '';

        $propertyTypes = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'apartamento', 'label' => 'Apartamentos'],
            ['value' => 'casa', 'label' => 'Casas'],
            ['value' => 'finca', 'label' => 'Fincas'],
            ['value' => 'lote', 'label' => 'Lotes'],
            ['value' => 'oficina', 'label' => 'Oficinas'],
            ['value' => 'comercial', 'label' => 'Propiedades comerciales'],
            ['value' => 'bodega', 'label' => 'Bodegas'],
        ];

        $propertyTypeLabels = collect($propertyTypes)
            ->pluck('label', 'value')
            ->all();
        $propertyTypeLabels['local'] = 'Propiedades comerciales';

        $propertiesQuery = Property::query()
            ->with('media')
            ->where('status', 'active');

        if ($destination !== '') {
            $propertiesQuery->where(function (Builder $query) use ($destination) {
                $query->where('city', 'like', "%{$destination}%")
                    ->orWhere('department', 'like', "%{$destination}%")
                    ->orWhere('neighborhood', 'like', "%{$destination}%");
            });
        }

        if ($propertyType === 'comercial' || $propertyType === 'local') {
            $propertiesQuery->whereRaw('LOWER(property_type) IN (?, ?)', ['comercial', 'local']);
        } elseif ($propertyType === 'oficina') {
            $propertiesQuery->whereRaw('LOWER(property_type) = ?', ['oficina']);
        } elseif ($propertyType !== '') {
            $propertiesQuery->whereRaw('LOWER(property_type) = ?', [$propertyType]);
        }

        if ($listingType !== '') {
            $propertiesQuery->where('listing_type', $listingType);
        }

        foreach (['bedrooms', 'bathrooms', 'stratum'] as $field) {
            if (isset($filters[$field])) {
                $propertiesQuery->where($field, $filters[$field]);
            }
        }

        if (isset($filters['min_price'])) {
            $propertiesQuery->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $propertiesQuery->where('price', '<=', $filters['max_price']);
        }

        $properties = $propertiesQuery
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $typeLabel = $propertyTypeLabels[$propertyType] ?? 'Inmuebles';
        $listingTitle = $listingType !== ''
            ? "{$typeLabel} en ".($listingType === 'alquiler' ? 'alquiler' : 'venta').' en '.($destination !== '' ? $destination : 'Colombia')
            : match (true) {
                $propertyType !== '' && $destination !== '' => "{$typeLabel} en {$destination}",
                $propertyType !== '' => "{$typeLabel} en Colombia",
                $destination !== '' => "Inmuebles en {$destination}",
                default => 'Encuentra tu próximo inmueble',
            };

        return view('properties::index', [
            'properties' => $properties,
            'propertyTypes' => $propertyTypes,
            'destination' => $destination,
            'propertyType' => $propertyType,
            'listingType' => $listingType,
            'listingTitle' => $listingTitle,
            'catalogFilters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $property = Property::query()
            ->with(['media', 'tour', 'user'])
            ->findOrFail($id);
        $otherProperties = Property::query()
            ->with('media')
            ->where('id', '!=', $property->id)
            ->where('status', 'active')
            ->latest()
            ->take(3)
            ->get();

        return view('properties::show', compact('property', 'otherProperties'));
    }

    public function requestMedia(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'media_type' => ['required', 'in:video,tour_360,blueprint'],
        ]);

        $mediaLabel = match ($validated['media_type']) {
            'video' => 'un video y un recorrido virtual 360°',
            'tour_360' => 'un recorrido virtual 360°',
            'blueprint' => 'los planos arquitectónicos en PDF',
        };

        Mail::to($property->user()->firstOrFail()->email)->send(new PropertyMediaRequestMail(
            $property,
            $mediaLabel,
            route('properties.show', $property),
        ));

        return response()->json([
            'message' => 'Tu solicitud fue enviada al propietario.',
        ]);
    }

    public function showBlueprint(Property $property): BinaryFileResponse|StreamedResponse
    {
        $blueprint = $property->media()->where('type', 'blueprint')->firstOrFail();
        $path = $blueprint->url_or_path;
        $r2Directory = "PlansPdf/{$property->id}/";

        if (str_starts_with($path, $r2Directory)) {
            $disk = Storage::disk('r2');
            abort_unless($disk->exists($path), 404);

            return $disk->response($path, 'planos-arquitectonicos.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="planos-arquitectonicos.pdf"',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "sandbox; default-src 'none';",
            ]);
        }

        $legacyDirectory = "properties/{$property->id}/blueprints/";
        abort_unless(str_starts_with($path, $legacyDirectory), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="planos-arquitectonicos.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none';",
        ]);
    }

    public function create()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $departments = Department::query()
            ->with(['cities' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('properties::create', compact('departments'));
    }

    public function edit($id)
    {
        $property = Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $departments = Department::query()
            ->with(['cities' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
        $department = $departments->first(
            fn (Department $department): bool => mb_strtolower($department->name) === mb_strtolower($property->department),
        );
        $city = $department?->cities->firstWhere('name', $property->city);
        $selectedDepartmentId = old('department_id', $department?->id);
        $selectedCityId = old('city_id', $city?->id);

        return view('properties::edit', compact(
            'property',
            'departments',
            'selectedDepartmentId',
            'selectedCityId',
        ));
    }

    public function store(Request $request)
    {
        // Validate and store the property
        $validatedData = $request->validate(array_merge([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => [
                'required',
                'numeric',
                'min:1000000',
                Rule::when($request->input('listing_type') === 'alquiler', ['max:100000000'], ['max:50000000000']),
            ],
            'property_type' => 'required|string',
            'listing_type' => ['required', 'in:venta,alquiler'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('department_id', $request->input('department_id'))],
            'neighborhood' => ['required', 'string', 'max:255'],
            'tour_id' => 'nullable|exists:tours,id',
            'images' => ['nullable', 'array', 'max:'.self::MAX_PROPERTY_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:'.self::MAX_PROPERTY_IMAGE_SIZE_KB],
            'blueprint' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:'.self::MAX_BLUEPRINT_SIZE_KB],
            // Add other fields as necessary
        ], $this->propertyDetailsRules()), [
            'blueprint.mimes' => 'El archivo debe ser un documento PDF válido.',
            'blueprint.extensions' => 'El archivo debe tener la extensión .pdf.',
            'blueprint.max' => 'El PDF no puede superar los 10 MB.',
            'images.*.required' => 'Selecciona una fotografía válida.',
            'images.*.uploaded' => 'No fue posible recibir la fotografía completa. Asegúrate de que no supere los 15 MB e inténtalo de nuevo.',
            'images.*.image' => 'El archivo debe ser una imagen válida.',
            'images.*.mimes' => 'La fotografía debe estar en formato JPEG o PNG.',
            'images.*.max' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
        ]);

        $blueprintFile = $validatedData['blueprint'] ?? null;
        unset($validatedData['blueprint'], $validatedData['images']);
        $validatedData = $this->replaceLocationIdsWithNames($validatedData);

        if ($blueprintFile instanceof UploadedFile && ! $this->pdfPageCountValidator->isWithinPageLimit($blueprintFile, self::MAX_BLUEPRINT_PAGES)) {
            return back()
                ->withErrors(['blueprint' => 'El PDF debe ser legible y tener entre 1 y 10 páginas.'])
                ->withInput();
        }

        $property = new Property($validatedData);
        $property->user_id = auth()->id();
        $property->save();
        $this->handlePropertyImages($request, $property);
        if ($blueprintFile instanceof UploadedFile) {
            $this->persistBlueprintFile($property, $blueprintFile);
        }

        return redirect()->route('properties.show', $property->id);
    }

    public function storeVideo(Request $request, int $id): RedirectResponse
    {
        $property = Property::findOrFail($id);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimes:mp4', 'extensions:mp4', 'max:102400'],
        ], [
            'video.mimes' => 'El video debe estar en formato MP4.',
            'video.extensions' => 'El archivo debe tener la extensión .mp4.',
            'video.max' => 'El video no puede superar los 100 MB.',
        ]);

        $video = $validated['video'];

        if (! $this->durationValidator->isAtMostOneMinute($video)) {
            return back()
                ->withErrors(['video' => 'El archivo debe ser un video MP4 válido de máximo 1 minuto.'])
                ->withInput();
        }

        $videoPath = $video->store("Videos/{$property->id}", 'r2');

        if (! is_string($videoPath)) {
            throw new RuntimeException('No fue posible guardar el video de la propiedad.');
        }

        $videoMedia = $property->media()->where('type', 'video')->first();
        $previousVideoPath = $videoMedia?->url_or_path;

        try {
            $mediaData = [
                'type' => 'video',
                'url_or_path' => $videoPath,
                'title' => $video->getClientOriginalName(),
                'mediable_type' => Property::class,
                'mediable_id' => $property->id,
            ];

            if ($videoMedia) {
                $videoMedia->update($mediaData);
            } else {
                $property->media()->create($mediaData);
            }
        } catch (Throwable $exception) {
            $this->cleanupR2Uploads([$videoPath]);

            throw $exception;
        }

        if ($previousVideoPath !== null) {
            $this->deleteStoredMedia($previousVideoPath, $property->id, 'video');
        }

        return redirect()->route('dashboard.index')->with('success', 'El video se agregó a la propiedad.');
    }

    public function storeBlueprint(Request $request, int $id): RedirectResponse
    {
        $property = Property::query()->findOrFail($id);
        abort_unless((int) $property->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'blueprint' => [
                'required',
                'file',
                'mimes:pdf',
                'extensions:pdf',
                'max:'.self::MAX_BLUEPRINT_SIZE_KB,
            ],
        ], [
            'blueprint.mimes' => 'El archivo debe ser un documento PDF válido.',
            'blueprint.extensions' => 'El archivo debe tener la extensión .pdf.',
            'blueprint.max' => 'El PDF no puede superar los 10 MB.',
        ]);

        if (! $this->pdfPageCountValidator->isWithinPageLimit($validated['blueprint'], self::MAX_BLUEPRINT_PAGES)) {
            return back()
                ->withErrors(['blueprint' => 'El PDF debe ser legible y tener entre 1 y 10 páginas.'])
                ->withInput();
        }

        $this->persistBlueprintFile($property, $validated['blueprint']);

        return redirect()->route('properties.edit', $property)->with('success', 'Los planos arquitectónicos se agregaron a la propiedad.');
    }

    private function persistBlueprintFile(Property $property, UploadedFile $blueprintFile): void
    {
        $blueprintPath = $blueprintFile->store("PlansPdf/{$property->id}", 'r2');

        if (! is_string($blueprintPath)) {
            throw new RuntimeException('No fue posible guardar los planos arquitectónicos.');
        }

        $blueprintMedia = $property->media()->where('type', 'blueprint')->first();
        $previousPath = $blueprintMedia?->url_or_path;

        try {
            $mediaData = [
                'type' => 'blueprint',
                'url_or_path' => $blueprintPath,
                'title' => 'Planos arquitectónicos',
                'mediable_type' => Property::class,
                'mediable_id' => $property->id,
            ];

            if ($blueprintMedia) {
                $blueprintMedia->update($mediaData);
            } else {
                $property->media()->create($mediaData);
            }
        } catch (Throwable $exception) {
            $this->cleanupR2Uploads([$blueprintPath]);

            throw $exception;
        }

        if ($previousPath !== null) {
            $this->deleteStoredMedia($previousPath, $property->id, 'blueprint');
        }
    }

    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate and update the property
        $validatedData = $request->validate(array_merge([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => [
                'required',
                'numeric',
                'min:1000000',
                Rule::when($request->input('listing_type') === 'alquiler', ['max:100000000'], ['max:50000000000']),
            ],
            'property_type' => 'required|string',
            'listing_type' => ['required', 'in:venta,alquiler'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('department_id', $request->input('department_id'))],
            'neighborhood' => ['required', 'string', 'max:255'],
            'tour_id' => 'nullable|exists:tours,id',
            'images' => ['nullable', 'array', 'max:'.self::MAX_PROPERTY_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:'.self::MAX_PROPERTY_IMAGE_SIZE_KB],
            // Add other fields as necessary
        ], $this->propertyDetailsRules()), [
            'images.*.required' => 'Selecciona una fotografía válida.',
            'images.*.uploaded' => 'No fue posible recibir la fotografía completa. Asegúrate de que no supere los 15 MB e inténtalo de nuevo.',
            'images.*.image' => 'El archivo debe ser una imagen válida.',
            'images.*.mimes' => 'La fotografía debe estar en formato JPEG o PNG.',
            'images.*.max' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
        ]);

        unset($validatedData['images']);
        $validatedData = $this->replaceLocationIdsWithNames($validatedData);
        $property->update($validatedData);
        $this->handlePropertyImages($request, $property);

        return redirect()->route('properties.show', $property->id);
    }

    public function destroy($id)
    {
        $property = Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $mediaItems = $property->media()->get(['type', 'url_or_path']);
        $tour = $property->tour()->with('scenes')->first();
        $tourPaths = collect([$tour?->cover_url])
            ->concat($tour?->scenes->pluck('image_url') ?? [])
            ->map(fn (?string $location): ?string => $location !== null ? PublicMediaUrl::r2Path($location) : null)
            ->filter(fn (?string $path): bool => is_string($path) && str_starts_with($path, "Tours360/{$property->id}/"))
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($property, $tour): void {
            if ($tour !== null) {
                $property->tour()->dissociate();
                $property->save();
                $tour->delete();
            }

            $property->delete();
        });

        if ($tourPaths !== [] && ! Storage::disk('r2')->delete($tourPaths)) {
            throw new RuntimeException('No fue posible eliminar las imágenes del recorrido 360° en Cloudflare R2.');
        }

        foreach ($mediaItems as $mediaItem) {
            $this->deleteStoredMedia($mediaItem->url_or_path, $property->id, $mediaItem->type);
        }

        return redirect()->route('properties.index');
    }

    private function handlePropertyImages(Request $request, Property $property): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $property, &$storedPaths): void {
                foreach ($request->file('images') as $file) {
                    $path = $file->store("Pictures/{$property->id}", 'r2');

                    if (! is_string($path)) {
                        throw new RuntimeException('No fue posible guardar las fotos de la propiedad.');
                    }

                    $storedPaths[] = $path;

                    $property->media()->create([
                        'type' => 'image',
                        'url_or_path' => $path,
                        'title' => $file->getClientOriginalName(),
                        'mediable_type' => Property::class,
                        'mediable_id' => $property->id,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->cleanupR2Uploads($storedPaths);

            throw $exception;
        }
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
                report(new RuntimeException('No fue posible limpiar los archivos R2 tras un fallo de carga.'));
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }

    private function deleteStoredMedia(string $path, int $propertyId, string $type): void
    {
        $path = str_replace('\\', '/', $path);
        $r2Directory = match ($type) {
            'image' => "Pictures/{$propertyId}/",
            'blueprint' => "PlansPdf/{$propertyId}/",
            'video' => "Videos/{$propertyId}/",
            'virtual_tour_360', 'model_3d' => "Tours360/{$propertyId}/",
            default => null,
        };

        if ($r2Directory !== null && str_starts_with($path, $r2Directory)) {
            Storage::disk('r2')->delete($path);

            return;
        }

        if ($type === 'blueprint' && str_starts_with($path, "properties/{$propertyId}/blueprints/")) {
            Storage::disk('local')->delete($path);

            return;
        }

        if ($type === 'video' && str_starts_with($path, "storage/properties/{$propertyId}/videos/")) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));

            return;
        }

        if (str_starts_with($path, 'uploads/media/')) {
            $publicPath = public_path($path);

            if (File::exists($publicPath)) {
                File::delete($publicPath);
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function propertyDetailsRules(): array
    {
        return [
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'half_bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:100'],
            'built_area_m2' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'lot_area_m2' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'floor_number' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'stratum' => ['nullable', 'integer', 'between:1,6'],
            'year_built' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'administration_fee' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'is_furnished' => ['required', 'boolean'],
            'has_elevator' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validatedData
     * @return array<string, mixed>
     */
    private function replaceLocationIdsWithNames(array $validatedData): array
    {
        $validatedData['department'] = Department::query()->findOrFail($validatedData['department_id'])->name;
        $validatedData['city'] = City::query()->findOrFail($validatedData['city_id'])->name;

        unset($validatedData['department_id'], $validatedData['city_id']);

        return $validatedData;
    }
}
