<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $properties = Property::query();

        if ($user->isFilmmaker()) {
            $properties->whereIn('id', $user->assignedProperties()->select('properties.id'));
        } elseif ($user->isAppraiser()) {
            $properties->whereIn('id', $user->assignedAppraisalProperties()->select('properties.id'));
        } elseif (! $user->isAdmin()) {
            $properties->where('user_id', $user->id);
        }

        $stats = [
            'total_properties' => (clone $properties)->count(),
            'published_properties' => (clone $properties)->where('status', 'publicado')->count(),
            'pending_properties' => (clone $properties)->where('status', 'pendiente')->count(),
        ];

        $propertyList = (clone $properties)
            ->with(['media' => fn ($query) => $query->whereIn('type', ['image', 'video']), 'tour'])
            ->latest('id')
            ->paginate(10)
            ->through(fn (Property $property): array => [
                'id' => $property->id,
                'identification_code' => $property->identification_code,
                'title' => $property->title,
                'status' => $property->status,
                'is_active' => $property->is_active,
                'city' => $property->city,
                'department' => $property->department,
                'media_count' => $property->media->count(),
                'has_tour' => $property->tour !== null,
            ]);

        return response()->json([
            'data' => [
                'stats' => $stats,
                'properties' => $propertyList->items(),
            ],
            'meta' => [
                'current_page' => $propertyList->currentPage(),
                'last_page' => $propertyList->lastPage(),
                'per_page' => $propertyList->perPage(),
                'total' => $propertyList->total(),
            ],
        ]);
    }
}
