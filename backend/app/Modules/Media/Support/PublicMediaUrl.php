<?php

namespace App\Modules\Media\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicMediaUrl
{
    private const R2_PREFIXES = ['Pictures/', 'PlansPdf/', 'Tours360/', 'Videos/'];

    public static function from(string $location): string
    {
        $path = self::r2Path($location);

        if ($path !== null) {
            return Storage::disk('r2')->temporaryUrl($path, now()->addHours(24));
        }

        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        return asset($location);
    }

    public static function r2Path(string $location): ?string
    {
        if (Str::startsWith($location, self::R2_PREFIXES)) {
            return $location;
        }

        $configuredUrlPath = self::pathFromBaseUrl($location, config('filesystems.disks.r2.url'));

        if ($configuredUrlPath !== null) {
            return Str::startsWith($configuredUrlPath, self::R2_PREFIXES) ? $configuredUrlPath : null;
        }

        $urlHost = parse_url($location, PHP_URL_HOST);
        $endpointHost = parse_url((string) config('filesystems.disks.r2.endpoint'), PHP_URL_HOST);

        if (! is_string($urlHost) || ! is_string($endpointHost) || $urlHost !== $endpointHost) {
            return null;
        }

        $bucket = config('filesystems.disks.r2.bucket');
        $urlPath = parse_url($location, PHP_URL_PATH);

        if (! is_string($bucket) || ! is_string($urlPath)) {
            return null;
        }

        $objectPath = rawurldecode(ltrim($urlPath, '/'));
        $bucketPrefix = "{$bucket}/";

        if (! Str::startsWith($objectPath, $bucketPrefix)) {
            return null;
        }

        $objectPath = substr($objectPath, strlen($bucketPrefix));

        return Str::startsWith($objectPath, self::R2_PREFIXES) ? $objectPath : null;
    }

    private static function pathFromBaseUrl(string $location, mixed $baseUrl): ?string
    {
        if (! is_string($baseUrl) || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $locationParts = parse_url($location);
        $baseParts = parse_url($baseUrl);

        if (! is_array($locationParts)
            || ! is_array($baseParts)
            || ($locationParts['host'] ?? null) !== ($baseParts['host'] ?? null)
            || ($locationParts['port'] ?? null) !== ($baseParts['port'] ?? null)
            || ! isset($locationParts['path'])) {
            return null;
        }

        $basePath = trim(rawurldecode($baseParts['path'] ?? ''), '/');
        $locationPath = trim(rawurldecode($locationParts['path']), '/');

        if ($basePath !== '') {
            if (! Str::startsWith($locationPath, "{$basePath}/")) {
                return null;
            }

            $locationPath = substr($locationPath, strlen($basePath) + 1);
        }

        return $locationPath;
    }
}
