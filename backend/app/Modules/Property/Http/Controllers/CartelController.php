<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use App\Modules\User\Support\ColombianPhoneNumber;
use Illuminate\Contracts\View\View;

class CartelController extends Controller
{
    /**
     * Muestra el cartel imprimible (con QR) de una propiedad al propietario legítimo.
     */
    public function generarCartel(int $id): View
    {
        $propiedad = Property::query()
            ->with(['user', 'media'])
            ->findOrFail($id);

        abort_unless((int) $propiedad->user_id === (int) auth()->id(), 403);

        $normalizedPhone = ColombianPhoneNumber::normalize($propiedad->user?->phone_number);

        return view('properties::cartel', [
            'property' => $propiedad,
            'propertyUrl' => route('properties.show', $propiedad->id),
            'contactPhone' => $normalizedPhone !== null ? substr($normalizedPhone, 3) : null,
        ]);
    }
}
