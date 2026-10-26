<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PropertyMediaRequest as PropertyMediaRequestMail;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PropertyWebController extends Controller
{
    private const MAX_BLUEPRINT_SIZE_KB = 10240;

    private const MAX_BLUEPRINT_PAGES = 10;

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

    public function showBlueprint(Property $property): BinaryFileResponse
    {
        $blueprint = $property->media()->where('type', 'blueprint')->firstOrFail();
        $expectedDirectory = "properties/{$property->id}/blueprints/";
        $path = $blueprint->url_or_path;

        abort_unless(str_starts_with($path, $expectedDirectory), 404);

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
            'blueprint' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:'.self::MAX_BLUEPRINT_SIZE_KB],
            // Add other fields as necessary
        ], $this->propertyDetailsRules()), [
            'blueprint.mimes' => 'El archivo debe ser un documento PDF válido.',
            'blueprint.extensions' => 'El archivo debe tener la extensión .pdf.',
            'blueprint.max' => 'El PDF no puede superar los 10 MB.',
        ]);

        $blueprintFile = $validatedData['blueprint'] ?? null;
        unset($validatedData['blueprint']);
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

        $videoPath = $video->storePublicly("properties/{$property->id}/videos", 'public');

        if (! is_string($videoPath)) {
            throw new RuntimeException('No fue posible guardar el video de la propiedad.');
        }

        $videoMedia = $property->media()->where('type', 'video')->first();
        $previousVideoPath = $videoMedia?->url_or_path;

        try {
            $mediaData = [
                'type' => 'video',
                'url_or_path' => 'storage/'.$videoPath,
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
            Storage::disk('public')->delete($videoPath);

            throw $exception;
        }

        if ($previousVideoPath !== null) {
            $previousVideoPath = str_replace('\\', '/', $previousVideoPath);
            $previousStoragePrefix = "storage/properties/{$property->id}/videos/";

            if (str_starts_with($previousVideoPath, $previousStoragePrefix)) {
                Storage::disk('public')->delete(substr($previousVideoPath, strlen('storage/')));
            }
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
        $blueprintPath = $blueprintFile->store("properties/{$property->id}/blueprints", 'local');

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
            Storage::disk('local')->delete($blueprintPath);

            throw $exception;
        }

        if ($previousPath !== null && str_starts_with($previousPath, "properties/{$property->id}/blueprints/")) {
            Storage::disk('local')->delete($previousPath);
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
            // Add other fields as necessary
        ], $this->propertyDetailsRules()));

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

        $blueprintPaths = $property->media()
            ->where('type', 'blueprint')
            ->pluck('url_or_path');
        $property->delete();

        foreach ($blueprintPaths as $path) {
            if (str_starts_with($path, "properties/{$property->id}/blueprints/")) {
                Storage::disk('local')->delete($path);
            }
        }

        return redirect()->route('properties.index');
    }

    private function handlePropertyImages(Request $request, Property $property)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $destinationPath = public_path('uploads/media');

                if (! \File::exists($destinationPath)) {
                    \File::makeDirectory($destinationPath, 0755, true);
                }

                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);

                $property->media()->create([
                    'type' => 'image',
                    'url_or_path' => 'uploads/media/'.$filename,
                    'title' => $file->getClientOriginalName(),
                    'mediable_type' => get_class($property),
                    'mediable_id' => $property->id,
                ]);
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
