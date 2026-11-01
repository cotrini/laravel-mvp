<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\AdminPropertyRequest;
use App\Mail\OwnerMediaRequest;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Models\PropertyRequest as PropertyServiceRequest;
use App\Modules\User\Models\User;
use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class PropertyRequestController extends Controller
{
    public function index(): View
    {
        $propertyRequests = PropertyServiceRequest::query()
            ->with(['property', 'user'])
            ->latest()
            ->paginate(20);

        return view('properties::admin.requests', compact('propertyRequests'));
    }

    public function storeOwnerRequest(Request $request, Property $property): RedirectResponse
    {
        abort_unless(
            ! $request->user()->isAppraiser()
                && (int) $property->user_id === (int) $request->user()->id,
            403,
        );

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                PropertyServiceRequest::TYPE_VERIFICATION,
                PropertyServiceRequest::TYPE_VIDEO,
                PropertyServiceRequest::TYPE_TOUR_360,
                PropertyServiceRequest::TYPE_VIDEO_AND_TOUR_360,
            ])],
        ]);

        $this->ensureRequestIsAvailable($property, $validated['type']);

        [$propertyRequest, $created] = $this->createPendingRequest(
            $property,
            $request->user(),
            $validated['type'],
        );

        if (! $created) {
            return back()->with('warning', 'Ya tienes una solicitud pendiente de este tipo para esta propiedad.');
        }

        $emailSent = $this->notifyAdministrators($propertyRequest);

        return back()->with(
            $emailSent ? 'success' : 'warning',
            $emailSent
                ? 'Tu solicitud fue enviada al administrador.'
                : 'La solicitud quedó guardada, pero no se pudo enviar el correo. El administrador la verá en su panel.',
        );
    }

    public function storeMediaRequest(Request $request, Property $property): JsonResponse
    {
        abort_unless($property->is_active, 404);

        $validated = $request->validate([
            'media_type' => ['required', Rule::in([
                PropertyServiceRequest::TYPE_VIDEO,
                PropertyServiceRequest::TYPE_TOUR_360,
                PropertyServiceRequest::TYPE_BLUEPRINT,
                PropertyServiceRequest::TYPE_MEDIA_PACKAGE,
            ])],
        ]);

        $this->ensureRequestIsAvailable($property, $validated['media_type']);

        if ($validated['media_type'] === PropertyServiceRequest::TYPE_MEDIA_PACKAGE) {
            $owner = $property->user;
            abort_if($owner === null || ! filled($owner->email), 422, 'No fue posible contactar al propietario.');

            $requestedMedia = collect([
                ! $property->media()->where('type', PropertyServiceRequest::TYPE_VIDEO)->exists()
                    ? 'videos profesionales'
                    : null,
                ! $property->tour()->exists() ? 'recorridos virtuales 360°' : null,
            ])->filter()->implode(', ');

            Mail::to($owner->email)->send(new OwnerMediaRequest(
                $property,
                PropertyServiceRequest::TYPE_MEDIA_PACKAGE,
                'Una persona interesada',
                null,
                null,
                $requestedMedia,
            ));

            return response()->json([
                'message' => 'El propietario recibió un aviso sobre el interés en su inmueble.',
            ]);
        }

        if ($validated['media_type'] !== PropertyServiceRequest::TYPE_BLUEPRINT) {
            $requester = $request->validate([
                'requester_name' => ['required', 'string', 'max:255'],
                'requester_email' => ['nullable', 'email', 'max:255'],
                'requester_phone' => ['nullable', 'string', 'max:30'],
            ]);
            $owner = $property->user;
            abort_if($owner === null || ! filled($owner->email), 422, 'No fue posible contactar al propietario.');

            Mail::to($owner->email)->send(new OwnerMediaRequest(
                $property,
                $validated['media_type'],
                $requester['requester_name'],
                $requester['requester_email'] ?? null,
                $requester['requester_phone'] ?? null,
            ));

            $normalizedOwnerPhone = ColombianPhoneNumberFormatter::normalize($owner->phone_number);
            $ownerPhone = is_string($normalizedOwnerPhone)
                ? preg_replace('/\D+/', '', $normalizedOwnerPhone)
                : null;
            $whatsAppUrl = null;

            if ($ownerPhone !== null && $ownerPhone !== '') {
                $message = implode("\n", [
                    'Hola, soy '.$requester['requester_name'].' y me interesa '.$property->title.'.',
                    $validated['media_type'] === PropertyServiceRequest::TYPE_VIDEO
                        ? 'Quisiera solicitar un video del inmueble.'
                        : 'Quisiera solicitar un recorrido virtual 360° del inmueble.',
                    'Las propiedades con recorridos 360 y videos tienen un 70% más de probabilidades de ser vendidas en plazos menores.',
                    'Inmueble: '.route('properties.show', $property),
                    'Mi correo: '.($requester['requester_email'] ?? 'No proporcionado'),
                    'Mi teléfono: '.($requester['requester_phone'] ?? 'No proporcionado'),
                ]);
                $whatsAppUrl = 'https://wa.me/'.$ownerPhone.'?text='.rawurlencode($message);
            }

            return response()->json([
                'message' => 'La solicitud se envió al propietario por correo electrónico.',
                'whatsapp_url' => $whatsAppUrl,
            ]);
        }

        [$propertyRequest, $created] = $this->createPendingRequest(
            $property,
            $request->user(),
            $validated['media_type'],
        );

        $emailSent = $created && $this->notifyAdministrators($propertyRequest);

        return response()->json([
            'message' => $created
                ? ($emailSent
                    ? 'La solicitud fue enviada al administrador.'
                    : 'La solicitud quedó guardada para el administrador.')
                : 'Ya existe una solicitud pendiente para este contenido.',
        ]);
    }

    public function markCompleted(PropertyServiceRequest $propertyRequest): RedirectResponse
    {
        $propertyRequest->update(['status' => PropertyServiceRequest::STATUS_COMPLETED]);

        return back()->with('success', 'La solicitud quedó marcada como atendida.');
    }

    private function ensureRequestIsAvailable(Property $property, string $type): void
    {
        if ($type === PropertyServiceRequest::TYPE_MEDIA_PACKAGE) {
            $hasVideo = $property->media()->where('type', PropertyServiceRequest::TYPE_VIDEO)->exists();
            $hasTour = $property->tour()->exists();

            abort_if($hasVideo && $hasTour, 409, 'La propiedad ya tiene video y recorrido 360°.');

            return;
        }

        if ($type === PropertyServiceRequest::TYPE_VIDEO_AND_TOUR_360) {
            $hasVideo = $property->media()->where('type', PropertyServiceRequest::TYPE_VIDEO)->exists();
            $hasTour = $property->tour()->exists();

            abort_if($hasVideo && $hasTour, 409, 'La propiedad ya tiene video y recorrido 360°.');

            return;
        }

        if ($type === PropertyServiceRequest::TYPE_VERIFICATION && $property->is_verified) {
            abort(409, 'La propiedad ya está verificada.');
        }

        if (
            $type === PropertyServiceRequest::TYPE_VIDEO
            && $property->media()->where('type', PropertyServiceRequest::TYPE_VIDEO)->exists()
        ) {
            abort(409, 'La propiedad ya tiene un video.');
        }

        if ($type === PropertyServiceRequest::TYPE_TOUR_360 && $property->tour()->exists()) {
            abort(409, 'La propiedad ya tiene un recorrido 360°.');
        }
    }

    /**
     * @return array{PropertyServiceRequest, bool}
     */
    private function createPendingRequest(
        Property $property,
        ?User $user,
        string $type,
    ): array {
        $propertyRequest = PropertyServiceRequest::query()->firstOrCreate([
            'property_id' => $property->id,
            'type' => $type,
            'status' => PropertyServiceRequest::STATUS_PENDING,
        ], [
            'user_id' => $user?->id,
        ]);

        return [$propertyRequest, $propertyRequest->wasRecentlyCreated];
    }

    private function notifyAdministrators(PropertyServiceRequest $propertyRequest): bool
    {
        $configuredAddress = config('services.admin_notifications.address');
        $addresses = collect([$configuredAddress])
            ->merge(User::query()->where('role', User::ROLE_ADMIN)->pluck('email'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($addresses === []) {
            return false;
        }

        Mail::to($addresses)->send(new AdminPropertyRequest($propertyRequest));

        return true;
    }
}
