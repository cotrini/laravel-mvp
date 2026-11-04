<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaApiController extends Controller
{
    public function index(Request $request, Property $property): JsonResponse
    {
        $user = $request->user();

        abort_if(
            (! $property->is_active || $property->status !== 'active')
            && ! ($user instanceof User && $user->canAccessProperty($property)),
            404,
        );

        $media = $property->media()
            ->whereIn('type', ['image', 'video'])
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (Media $item): array => [
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->title,
                'url' => $item->public_url,
                'is_primary' => $item->is_primary,
                'order' => $item->order,
            ]);

        return response()->json([
            'data' => $media,
            'property_id' => $property->id,
        ]);
    }
}
