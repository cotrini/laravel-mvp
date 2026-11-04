<?php

namespace App\Modules\Tours360\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tours360\Models\Tour;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Tour360ApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tours = Tour::query()
            ->published()
            ->with(['scenes.hotspots.targetScene', 'property'])
            ->latest('id')
            ->paginate(12)
            ->through(fn (Tour $tour): array => $this->tourData($tour));

        return response()->json([
            'data' => $tours->items(),
            'meta' => [
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $tour = Tour::query()
            ->published()
            ->with(['scenes.hotspots.targetScene', 'property'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $this->tourData($tour)]);
    }

    public function mine(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tours = Tour::query()
            ->with(['scenes.hotspots.targetScene', 'property'])
            ->when(
                ! $user->isAdmin() && $user->isFilmmaker(),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($propertyQuery) => $propertyQuery->whereIn(
                        'properties.id',
                        $user->assignedProperties()->select('properties.id'),
                    ),
                ),
            )
            ->when(
                ! $user->isAdmin() && ! $user->isFilmmaker(),
                fn ($query) => $query->whereKey([]),
            )
            ->latest('id')
            ->paginate(20)
            ->through(fn (Tour $tour): array => $this->tourData($tour));

        return response()->json([
            'data' => $tours->items(),
            'meta' => [
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tourData(Tour $tour): array
    {
        return [
            'id' => $tour->id,
            'slug' => $tour->slug,
            'title' => $tour->title,
            'description' => $tour->description,
            'location' => $tour->location,
            'cover_url' => $tour->public_cover_url,
            'property_id' => $tour->property?->id,
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
        ];
    }
}
