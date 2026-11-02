<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Models\PropertyRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardWebController extends Controller
{
    /**
     * Muestra la vista principal del Panel de Control del Usuario.
     */
    public function index(Request $request): View
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
        $search = '';

        if ($user->isAdmin()) {
            $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
            ]);
            $search = trim($filters['search'] ?? '');

            if ($search !== '') {
                $identificationCode = strtoupper($search);
                $propertyId = null;

                if (preg_match('/^CSC-(\d+)$/', $identificationCode, $matches) === 1) {
                    $propertyId = (int) $matches[1];
                } elseif (ctype_digit($search)) {
                    $propertyId = (int) $search;
                }

                $propertiesQuery->where(function (Builder $query) use ($search, $propertyId): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('neighborhood', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $ownerQuery) use ($search): void {
                            $ownerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        });

                    if ($propertyId !== null) {
                        $query->orWhereKey($propertyId);
                    }
                });
            }
        }

        $properties = $propertiesQuery->with(['media', 'tour', 'requests'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total_properties' => (clone $statsQuery)->count(),
            'published_properties' => (clone $statsQuery)->where('status', 'publicado')->count(),
            'pending_properties' => (clone $statsQuery)->where('status', 'pendiente')->count(),
        ];
        $pendingPropertyRequests = $user->isAdmin()
            ? PropertyRequest::query()->where('status', PropertyRequest::STATUS_PENDING)->count()
            : 0;

        return view('dashboard::index', compact(
            'user',
            'properties',
            'stats',
            'pendingPropertyRequests',
            'search',
        ));
    }
}
