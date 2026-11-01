<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\AppraisalCompanyRequest;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PropertyAppraisalController extends Controller
{
    private const MAX_APPRAISAL_SIZE_KB = 20480;

    public function request(Request $request, Property $property): RedirectResponse
    {
        abort_unless(
            ! $request->user()->isAppraiser()
                && (int) $property->user_id === (int) $request->user()->id,
            403,
        );

        $recipient = config('services.appraisals.address');

        if (! is_string($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->with('warning', 'No se pudo enviar la solicitud. Configura el correo de la casa de avalúos.');
        }

        Mail::to($recipient)->send(new AppraisalCompanyRequest($property, $request->user()));

        return back()->with(
            'success',
            'La casa de avalúos recibió la solicitud y se pondrá en contacto con el propietario.',
        );
    }

    public function upload(Request $request, Property $property): RedirectResponse
    {
        abort_unless($request->user()->canUploadAppraisal($property), 403);

        $validated = $request->validate([
            'appraisal' => [
                'required',
                'file',
                'mimes:pdf',
                'extensions:pdf',
                'max:'.self::MAX_APPRAISAL_SIZE_KB,
            ],
        ], [
            'appraisal.mimes' => 'El archivo debe ser un documento PDF válido.',
            'appraisal.extensions' => 'El archivo debe tener la extensión .pdf.',
            'appraisal.max' => 'El avalúo no puede superar los 20 MB.',
        ]);

        $path = $validated['appraisal']->store("Avaluos/{$property->id}", 'r2');

        if (! is_string($path)) {
            throw new RuntimeException('No fue posible guardar el avalúo comercial en Cloudflare R2.');
        }

        try {
            $previousPath = DB::transaction(function () use ($path, $property): ?string {
                $lockedProperty = Property::query()
                    ->lockForUpdate()
                    ->findOrFail($property->id);
                $appraisal = $lockedProperty->media()->where('type', 'appraisal')->first();
                $previousPath = $appraisal?->url_or_path;
                $mediaData = [
                    'type' => 'appraisal',
                    'url_or_path' => $path,
                    'title' => 'Avalúo comercial',
                    'mediable_type' => Property::class,
                    'mediable_id' => $property->id,
                ];

                if ($appraisal instanceof Media) {
                    $appraisal->update($mediaData);
                } else {
                    $lockedProperty->media()->create($mediaData);
                }

                return $previousPath;
            });
        } catch (Throwable $exception) {
            try {
                Storage::disk('r2')->delete($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        if (
            is_string($previousPath)
            && Str::startsWith($previousPath, "Avaluos/{$property->id}/")
        ) {
            Storage::disk('r2')->delete($previousPath);
        }

        return redirect()->route('dashboard.index')->with('success', 'El avalúo comercial se guardó correctamente.');
    }

    public function show(Property $property): StreamedResponse
    {
        abort_if(
            ! $property->is_active && ! auth()->user()?->canAccessProperty($property),
            404,
        );

        $appraisal = $property->media()->where('type', 'appraisal')->firstOrFail();
        $path = $appraisal->url_or_path;
        $directory = "Avaluos/{$property->id}/";

        abort_unless(Str::startsWith($path, $directory), 404);

        $disk = Storage::disk('r2');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, 'avaluo-comercial.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="avaluo-comercial.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none';",
        ]);
    }
}
