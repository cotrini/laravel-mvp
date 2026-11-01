<?php

namespace App\Modules\Property\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class OpenStreetMapGeocoder
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function search(string $query): ?array
    {
        $cacheKey = 'location-geocode:'.sha1(mb_strtolower(trim($query)));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query): ?array {
            $result = RateLimiter::attempt(
                'openstreetmap-nominatim-global',
                1,
                fn (): array => Http::acceptJson()
                    ->connectTimeout(3)
                    ->timeout(8)
                    ->withUserAgent(config('app.name').' ('.config('app.url').')')
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'limit' => 1,
                        'countrycodes' => 'co',
                    ])
                    ->throw()
                    ->json(),
                1,
            );

            if ($result === false) {
                throw new TooManyRequestsHttpException(1, 'Espera un momento antes de volver a buscar una ubicación.');
            }

            $location = $result[0] ?? null;
            if (! is_array($location) || ! is_numeric($location['lat'] ?? null) || ! is_numeric($location['lon'] ?? null)) {
                return null;
            }

            return [
                'latitude' => (float) $location['lat'],
                'longitude' => (float) $location['lon'],
            ];
        });
    }
}
