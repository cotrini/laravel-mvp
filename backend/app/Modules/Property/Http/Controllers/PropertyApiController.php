<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'destination' => ['nullable', 'string', 'max:120'],
            'property_type' => ['nullable', 'string', 'max:50'],
            'listing_type' => ['nullable', 'in:venta,alquiler'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'bedrooms' => ['nullable', 'integer', 'between:0,100'],
            'bathrooms' => ['nullable', 'integer', 'between:0,100'],
            'stratum' => ['nullable', 'integer', 'between:1,6'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $properties = Property::query()
            ->active()
            ->where('status', 'active')
            ->with(['media' => fn ($query) => $query->whereIn('type', ['image', 'video']), 'tour.scenes.hotspots.targetScene']);

        if (filled($filters['destination'] ?? null)) {
            $destination = $filters['destination'];
            $properties->where(function (Builder $query) use ($destination): void {
                $query->where('city', 'like', "%{$destination}%")
                    ->orWhere('department', 'like', "%{$destination}%")
                    ->orWhere('neighborhood', 'like', "%{$destination}%");
            });
        }

        foreach (['property_type', 'listing_type', 'bedrooms', 'bathrooms', 'stratum'] as $filter) {
            if (isset($filters[$filter])) {
                $properties->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['min_price'])) {
            $properties->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $properties->where('price', '<=', $filters['max_price']);
        }

        $results = $properties
            ->latest('id')
            ->paginate($filters['per_page'] ?? 12)
            ->through(fn (Property $property): array => $this->propertyData($property));

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->ensurePropertyIsVisible($request, $property);

        $property->load([
            'media' => fn ($query) => $query->whereIn('type', ['image', 'video']),
            'tour.scenes.hotspots.targetScene',
        ]);

        return response()->json(['data' => $this->propertyData($property)]);
    }

    public function mine(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $properties = Property::query()
            ->with(['media' => fn ($query) => $query->whereIn('type', ['image', 'video']), 'tour.scenes.hotspots.targetScene'])
            ->when(
                $user->isFilmmaker(),
                fn (Builder $query) => $query->whereIn('id', $user->assignedProperties()->select('properties.id')),
            )
            ->when(
                $user->isAppraiser(),
                fn (Builder $query) => $query->whereIn('id', $user->assignedAppraisalProperties()->select('properties.id')),
            )
            ->when(
                ! $user->isAdmin() && ! $user->isFilmmaker() && ! $user->isAppraiser(),
                fn (Builder $query) => $query->where('user_id', $user->id),
            )
            ->latest('id')
            ->paginate($filters['per_page'] ?? 12)
            ->through(fn (Property $property): array => $this->propertyData($property));

        return response()->json([
            'data' => $properties->items(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    private function ensurePropertyIsVisible(Request $request, Property $property): void
    {
        $user = $request->user();

        abort_if(
            (! $property->is_active || $property->status !== 'active')
            && ! ($user instanceof User && $user->canAccessProperty($property)),
            404,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyData(Property $property): array
    {
        $tour = $property->tour;

        return [
            'id' => $property->id,
            'identification_code' => $property->identification_code,
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'property_type' => $property->property_type,
            'listing_type' => $property->listing_type,
            'status' => $property->status,
            'is_active' => $property->is_active,
            'is_verified' => $property->is_verified,
            'department' => $property->department,
            'city' => $property->city,
            'neighborhood' => $property->neighborhood,
            'address' => $property->address,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'half_bathrooms' => $property->half_bathrooms,
            'parking_spaces' => $property->parking_spaces,
            'built_area_m2' => $property->built_area_m2,
            'lot_area_m2' => $property->lot_area_m2,
            'floor_number' => $property->floor_number,
            'total_floors' => $property->total_floors,
            'stratum' => $property->stratum,
            'year_built' => $property->year_built,
            'administration_fee' => $property->administration_fee,
            'is_furnished' => $property->is_furnished,
            'has_elevator' => $property->has_elevator,
            'media' => $property->media->map(fn (Media $media): array => [
                'id' => $media->id,
                'type' => $media->type,
                'title' => $media->title,
                'url' => $media->public_url,
                'is_primary' => $media->is_primary,
                'order' => $media->order,
            ])->values(),
            'tour' => $tour !== null && $tour->is_published
                ? [
                    'id' => $tour->id,
                    'slug' => $tour->slug,
                    'title' => $tour->title,
                    'description' => $tour->description,
                    'location' => $tour->location,
                    'cover_url' => $tour->public_cover_url,
                    'scenes' => $tour->scenes->map(fn ($scene): array => [
                        'id' => $scene->id,
                        'title' => $scene->title,
                        'caption' => $scene->caption,
                        'image_url' => $scene->public_image_url,
                        'sort_order' => $scene->sort_order,
                        'hotspots' => $scene->hotspots->map(fn ($hotspot): array => [
                            'id' => $hotspot->id,
                            'target_scene_id' => $hotspot->target_scene_id,
                            'label' => $hotspot->label,
                            'yaw' => $hotspot->yaw,
                            'pitch' => $hotspot->pitch,
                        ])->values(),
                    ])->values(),
                ]
                : null,
        ];
    }
}
