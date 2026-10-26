<?php

namespace App\Http\Controllers;

use App\Modules\Property\Models\Property;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'destination' => ['nullable', 'string', 'max:120'],
            'property_type' => ['nullable', 'in:apartamento,casa,finca,lote,local,oficina,comercial,bodega'],
            'listing_type' => ['nullable', 'in:venta,alquiler'],
        ]);

        $destination = trim($filters['destination'] ?? '');
        $propertyType = $filters['property_type'] ?? '';
        $listingType = $filters['listing_type'] ?? '';

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

        if ($propertyType !== '') {
            if ($propertyType === 'comercial' || $propertyType === 'local') {
                $propertiesQuery->whereRaw('LOWER(property_type) IN (?, ?)', ['comercial', 'local']);
            } else {
                $propertiesQuery->whereRaw('LOWER(property_type) = ?', [$propertyType]);
            }
        }

        if ($listingType !== '') {
            $propertiesQuery->where('listing_type', $listingType);
        }

        $latestProperties = $propertiesQuery
            ->latest()
            ->take(6)
            ->get();

        $hasSearch = $destination !== '' || $propertyType !== '' || $listingType !== '';
        $propertySections = [];

        if ($hasSearch) {
            $propertySections[] = [
                'title' => $listingType !== ''
                    ? ($propertyType !== '' ? ucfirst($propertyType) : 'Inmuebles').' en '.($listingType === 'alquiler' ? 'alquiler' : 'venta')
                    : 'Inmuebles encontrados',
                'description' => 'Resultados que coinciden con tu búsqueda.',
                'accent' => 'teal',
                'properties' => $latestProperties,
            ];
        } else {
            $categories = [
                ['type' => 'apartamento', 'title' => 'Apartamentos', 'accent' => 'aqua'],
                ['type' => 'casa', 'title' => 'Casas', 'accent' => 'rose'],
                ['type' => 'finca', 'title' => 'Fincas y casas campestres', 'accent' => 'yellow'],
                ['type' => 'lote', 'title' => 'Lotes', 'accent' => 'lavender'],
                ['type' => 'oficina', 'title' => 'Oficinas', 'accent' => 'orange'],
                ['type' => 'comercial', 'title' => 'Propiedades comerciales', 'accent' => 'lavender'],
                ['type' => 'bodega', 'title' => 'Bodegas', 'accent' => 'yellow'],
            ];

            foreach ($categories as $category) {
                $query = Property::query()
                    ->with('media')
                    ->where('status', 'active')
                    ->when($category['type'] === 'oficina', function (Builder $query) {
                        $query->whereRaw('LOWER(property_type) = ?', ['oficina']);
                    }, function (Builder $query) use ($category) {
                        if ($category['type'] === 'comercial') {
                            $query->whereRaw('LOWER(property_type) IN (?, ?)', ['comercial', 'local']);
                        } else {
                            $query->whereRaw('LOWER(property_type) = ?', [$category['type']]);
                        }
                    });

                $properties = $query->latest()->take(3)->get();

                if ($properties->isNotEmpty()) {
                    $propertySections[] = [
                        'title' => $category['title'],
                        'description' => 'Las publicaciones más recientes de esta categoría.',
                        'accent' => $category['accent'],
                        'type' => $category['type'],
                        'properties' => $properties,
                    ];
                }
            }
        }

        return view('welcome', [
            'latestProperties' => $latestProperties,
            'propertySections' => $propertySections,
            'destination' => $destination,
            'propertyType' => $propertyType,
            'listingType' => $listingType,
            'hasSearch' => $hasSearch,
        ]);
    }
}
