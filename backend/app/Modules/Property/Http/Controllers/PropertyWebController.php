<?php

namespace App\Modules\Property\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;

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

    public function edit($id)
    {
        $property = \App\Modules\Property\Models\Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }
        return view('properties::edit', compact('property'));
    }
    public function store(Request $request)
    {
        // Validate and store the property
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            // Add other fields as necessary
        ]);

        $property = new \App\Modules\Property\Models\Property($validatedData);
        $property->user_id = auth()->id();
        $property->save();
        $this->handlePropertyImages($request, $property);

        return redirect()->route('properties.show', $property->id);
    }
    public function update(Request $request, $id)
    {
        $property = \App\Modules\Property\Models\Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate and update the property
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'property_type' => 'required|string',
            // Add other fields as necessary
        ]);

        $property->update($validatedData);
        $this->handlePropertyImages($request, $property);

        return redirect()->route('properties.show', $property->id);
    }
    public function destroy($id)
    {
        $property = \App\Modules\Property\Models\Property::findOrFail($id);
        if (auth()->id() !== $property->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $property->delete();

        return redirect()->route('properties.index');
    }
    private function handlePropertyImages(Request $request, Property $property)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $destinationPath = public_path('uploads/media');
                
                if (!\File::exists($destinationPath)) {
                    \File::makeDirectory($destinationPath, 0755, true);
                }

                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);

                $property->media()->create([
                    'type' => 'image',
                    'url_or_path' => 'uploads/media/' . $filename,
                    'title' => $file->getClientOriginalName(),
                    'mediable_type' => get_class($property),
                    'mediable_id' => $property->id,
                ]);
            }
        }
    }       
}