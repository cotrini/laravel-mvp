<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Neighborhood;
use App\Modules\Property\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::query()->withCount('cities')->orderBy('name')->get();
        $departmentId = $request->integer('department_id');
        $selectedDepartment = Department::query()->find($departmentId) ?? $departments->first();
        $cities = $selectedDepartment
            ? City::query()->withCount('neighborhoods')->where('department_id', $selectedDepartment->id)->orderBy('name')->get()
            : collect();
        $cityId = $request->integer('city_id');
        $selectedCity = $cities->firstWhere('id', $cityId) ?? $cities->first();
        $neighborhoods = $selectedCity
            ? Neighborhood::query()->where('city_id', $selectedCity->id)->orderBy('name')->get()
            : collect();

        return view('properties::locations.index', compact(
            'departments',
            'selectedDepartment',
            'cities',
            'selectedCity',
            'neighborhoods',
        ));
    }

    public function cities(Department $department): JsonResponse
    {
        return response()->json($department->cities()->orderBy('name')->get(['id', 'name']));
    }

    public function neighborhoods(City $city): JsonResponse
    {
        return response()->json($city->neighborhoods()->orderBy('name')->get(['id', 'name']));
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
        ]);

        Department::query()->create($validated);

        return back()->with('success', 'El departamento se agregó al catálogo.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department)],
        ]);

        $previousName = $department->name;

        DB::transaction(function () use ($department, $previousName, $validated): void {
            $department->update($validated);
            Property::query()->where('department', $previousName)->update(['department' => $validated['name']]);
        });

        return back()->with('success', 'El departamento se actualizó.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->cities()->exists() || Property::query()->where('department', $department->name)->exists()) {
            return back()->withErrors(['department' => 'No puedes eliminar un departamento con ciudades o publicaciones asociadas.']);
        }

        $department->delete();

        return back()->with('success', 'El departamento se eliminó del catálogo.');
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')->where('department_id', $request->input('department_id')),
            ],
        ]);

        City::query()->create($validated);

        return back()->with('success', 'La ciudad o municipio se agregó al catálogo.');
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')
                    ->where('department_id', $request->input('department_id'))
                    ->ignore($city),
            ],
        ]);

        $previousDepartmentName = $city->department()->value('name');
        $previousCityName = $city->name;
        $newDepartmentName = Department::query()->findOrFail($validated['department_id'])->name;

        DB::transaction(function () use ($city, $previousDepartmentName, $previousCityName, $newDepartmentName, $validated): void {
            $city->update($validated);
            Property::query()
                ->where('department', $previousDepartmentName)
                ->where('city', $previousCityName)
                ->update(['department' => $newDepartmentName, 'city' => $validated['name']]);
        });

        return back()->with('success', 'La ciudad o municipio se actualizó.');
    }

    public function destroyCity(City $city): RedirectResponse
    {
        $departmentName = $city->department()->value('name');
        if (
            $city->neighborhoods()->exists()
            || Property::query()->where('department', $departmentName)->where('city', $city->name)->exists()
        ) {
            return back()->withErrors(['city' => 'No puedes eliminar una ciudad con barrios o publicaciones asociadas.']);
        }

        $city->delete();

        return back()->with('success', 'La ciudad o municipio se eliminó del catálogo.');
    }

    public function storeNeighborhood(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('neighborhoods', 'name')->where('city_id', $request->input('city_id')),
            ],
        ]);

        Neighborhood::query()->create($validated);

        return back()->with('success', 'El barrio se agregó al catálogo.');
    }

    public function updateNeighborhood(Request $request, Neighborhood $neighborhood): RedirectResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('neighborhoods', 'name')
                    ->where('city_id', $request->input('city_id'))
                    ->ignore($neighborhood),
            ],
        ]);

        $city = $neighborhood->city()->with('department')->firstOrFail();
        $previousName = $neighborhood->name;
        $previousCityName = $city->name;
        $previousDepartmentName = $city->department->name;
        $newCity = City::query()->with('department')->findOrFail($validated['city_id']);

        DB::transaction(function () use ($neighborhood, $previousName, $previousCityName, $previousDepartmentName, $newCity, $validated): void {
            $neighborhood->update($validated);
            Property::query()
                ->where('department', $previousDepartmentName)
                ->where('city', $previousCityName)
                ->where('neighborhood', $previousName)
                ->update([
                    'department' => $newCity->department->name,
                    'city' => $newCity->name,
                    'neighborhood' => $validated['name'],
                ]);
        });

        return back()->with('success', 'El barrio se actualizó.');
    }

    public function destroyNeighborhood(Neighborhood $neighborhood): RedirectResponse
    {
        $city = $neighborhood->city()->with('department')->firstOrFail();
        if (
            Property::query()
                ->where('department', $city->department->name)
                ->where('city', $city->name)
                ->where('neighborhood', $neighborhood->name)
                ->exists()
        ) {
            return back()->withErrors(['neighborhood' => 'No puedes eliminar un barrio que todavía usa una publicación.']);
        }

        $neighborhood->delete();

        return back()->with('success', 'El barrio se eliminó del catálogo.');
    }
}
