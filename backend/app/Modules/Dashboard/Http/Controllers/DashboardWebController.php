<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Models\PropertyRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardWebController extends Controller
{
    /**
     * Muestra la vista principal del Panel de Control del Usuario.
     */
    public function index(): View
    {
        $user = Auth::user();

        $propertiesQuery = Property::query();

        if ($user->isFilmmaker()) {
            $propertiesQuery->whereIn(
                'id',
                $user->assignedProperties()->select('properties.id'),
            );
        } elseif ($user->isAppraiser()) {
            $propertiesQuery->whereIn(
                'id',
                $user->assignedAppraisalProperties()->select('properties.id'),
            );
        } elseif (! $user->isAdmin()) {
            $propertiesQuery->where('user_id', $user->id);
        }

        $statsQuery = clone $propertiesQuery;
        $properties = $propertiesQuery->with(['media', 'tour', 'requests'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total_properties' => (clone $statsQuery)->count(),
            'published_properties' => (clone $statsQuery)->where('status', 'publicado')->count(),
            'pending_properties' => (clone $statsQuery)->where('status', 'pendiente')->count(),
        ];
        $pendingPropertyRequests = $user->isAdmin()
            ? PropertyRequest::query()->where('status', PropertyRequest::STATUS_PENDING)->count()
            : 0;

        return view('dashboard::index', compact('user', 'properties', 'stats', 'pendingPropertyRequests'));
    }
}
