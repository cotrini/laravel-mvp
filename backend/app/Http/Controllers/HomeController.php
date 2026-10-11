<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Property\Models\Property;

class HomeController extends Controller
{
    public function index()
    {
        // Obtenemos las últimas 6 propiedades publicadas para el landing page
        $latestProperties = Property::where('status', 'active')
            ->latest()
            ->take(6)
            ->get();

        // Asociamos la vista 'resources/views/welcome.blade.php' pasándole los datos
        return view('welcome', compact('latestProperties'));
    }
}
