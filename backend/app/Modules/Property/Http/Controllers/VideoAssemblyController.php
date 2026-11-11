<?php

namespace App\Modules\Property\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Property\Models\Property;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

// Une escenas de la app móvil en un video comercial (máx. 40 s) con ffmpeg y lo guarda
// en el inmueble igual que PropertyWebController::storeVideo.
class VideoAssemblyController extends Controller
{
    private const FORMATS = [
        'horizontal' => [1920, 1080],
        'vertical' => [1080, 1920],
    ];

    private const MAX_SECONDS = 40;

    public function store(Request $request, Property $property): JsonResponse
    {
        abort_unless($request->user()->canManagePropertyMedia($property), 403);

        $validated = $request->validate([
            'format' => ['required', 'in:horizontal,vertical'],
            'clips' => ['required', 'array', 'min:1', 'max:12'],
            'clips.*' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime', 'max:102400'],
        ], [
            'clips.*.mimetypes' => 'Cada escena debe ser un video MP4 o MOV.',
            'clips.*.max' => 'Cada escena no puede superar los 100 MB.',
        ]);

        $workDir = storage_path('app/video-assembly/'.Str::uuid());
        FileFacade::makeDirectory($workDir, 0755, true);

        try {
            $clipPaths = [];
            foreach ($validated['clips'] as $index => $clip) {
                $extension = $clip->extension() ?: 'mp4';
                $clipPaths[] = $clip->move($workDir, "clip-{$index}.{$extension}")->getPathname();
            }

            $outputPath = $workDir.'/commercial.mp4';
            $this->assemble($clipPaths, $validated['format'], $outputPath);

            $videoPath = Storage::disk('r2')->putFileAs(
                "Videos/{$property->id}",
                new File($outputPath),
                Str::uuid().'.mp4',
            );

            if (! is_string($videoPath)) {
                throw new RuntimeException('No fue posible guardar el video de la propiedad.');
            }

            $videoMedia = $property->media()->where('type', 'video')->first();
            $isNewVideo = $videoMedia === null;
            $previousVideoPath = $videoMedia?->url_or_path;

            $mediaData = [
                'type' => 'video',
                'url_or_path' => $videoPath,
                'title' => 'Video comercial ('.$validated['format'].')',
                'mediable_type' => Property::class,
                'mediable_id' => $property->id,
            ];

            if ($videoMedia) {
                $videoMedia->update($mediaData);
            } else {
                $videoMedia = $property->media()->create($mediaData);
            }

            if ($previousVideoPath !== null && $previousVideoPath !== $videoPath) {
                Storage::disk('r2')->delete($previousVideoPath);
            }

            return response()->json([
                'message' => 'El video comercial se ensambló y se agregó a la propiedad.',
                'data' => [
                    'id' => $videoMedia->refresh()->id,
                    'type' => $videoMedia->type,
                    'title' => $videoMedia->title,
                    'url' => $videoMedia->public_url,
                ],
            ], $isNewVideo ? 201 : 200);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } finally {
            FileFacade::deleteDirectory($workDir);
        }
    }

    /**
     * @param  list<string>  $clipPaths
     */
    private function assemble(array $clipPaths, string $format, string $outputPath): void
    {
        [$width, $height] = self::FORMATS[$format];
        $binary = config('services.ffmpeg.binary', 'ffmpeg');

        $command = [$binary, '-y', '-hide_banner', '-loglevel', 'error'];
        foreach ($clipPaths as $path) {
            $command[] = '-i';
            $command[] = $path;
        }

        $logoPath = config('services.ffmpeg.logo_path');
        $hasLogo = is_string($logoPath) && is_file($logoPath);
        if ($hasLogo) {
            $command[] = '-i';
            $command[] = $logoPath;
        }

        $filters = [];
        $labels = '';
        foreach (array_keys($clipPaths) as $index) {
            $filters[] = "[{$index}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,"
                ."pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=30,format=yuv420p[v{$index}]";
            $labels .= "[v{$index}]";
        }
        $count = count($clipPaths);
        $filters[] = "{$labels}concat=n={$count}:v=1:a=0[base]";

        if ($hasLogo) {
            $logoIndex = $count;
            $filters[] = "[{$logoIndex}:v]scale=-1:96[logo]";
            $filters[] = '[base][logo]overlay=24:24[out]';
        } else {
            $font = config('services.ffmpeg.font_path');
            $fontOption = is_string($font) && $font !== '' ? "fontfile='{$font}':" : '';
            $filters[] = "[base]drawtext={$fontOption}text='Colombia Sin Comisión':fontcolor=white:"
                .'fontsize=48:box=1:boxcolor=black@0.45:boxborderw=14:x=24:y=24[out]';
        }

        array_push($command, '-filter_complex', implode(';', $filters), '-map', '[out]', '-an');
        array_push($command, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23');
        array_push($command, '-movflags', '+faststart', '-t', (string) self::MAX_SECONDS, $outputPath);

        try {
            $process = new Process($command, null, null, null, 900);
            $process->run();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'ffmpeg no está disponible en el servidor. Configura FFMPEG_BINARY.',
                previous: $exception,
            );
        }

        if (! $process->isSuccessful() || ! is_file($outputPath)) {
            throw new RuntimeException('ffmpeg no pudo ensamblar el video: '.$process->getErrorOutput());
        }
    }
}
