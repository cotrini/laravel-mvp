<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;

class PropertyWebController extends Controller
{
    public function index()
    {
        return view('properties::index');
    }
    public function show($id)
    {
        // implements the logic to show a specific property by its ID
        $property = \App\Modules\Property\Models\Property::findOrFail($id);
        return view('properties::show', compact('property'));
    }
    public function create()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        return view('properties::create');
    }
}