<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;

class PropertyWebController extends Controller
{
    public function index()
    {
        // Tu código aquí
    }
    public function show($id)
    {
        // implements the logic to show a specific property by its ID
        $property = \App\Modules\Property\Models\Property::findOrFail($id);
        return view('properties::show', compact('property'));
    }
}