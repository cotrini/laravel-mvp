<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use Illuminate\Support\Facades\Auth;

class DashboardWebController extends Controller
{
    /**
     * Muestra la vista principal del Panel de Control del Usuario.
     */
    public function index()
    {
        $user = Auth::user();

        // Obtener las propiedades pertenecientes al usuario autenticado con sus medios
        $properties = Property::where('user_id', $user->id)
            ->with(['media', 'tour'])
            ->latest()
            ->paginate(10);

        // Métricas básicas para las tarjetas del panel
        $stats = [
            'total_properties' => Property::where('user_id', $user->id)->count(),
            'published_properties' => Property::where('user_id', $user->id)->where('status', 'publicado')->count(),
            'pending_properties' => Property::where('user_id', $user->id)->where('status', 'pendiente')->count(),
        ];

        return view('dashboard::index', compact('user', 'properties', 'stats'));
    }
}
