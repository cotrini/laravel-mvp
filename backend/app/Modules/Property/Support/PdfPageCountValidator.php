<?php

namespace App\Modules\Property\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class PdfPageCountValidator
{
    public function isWithinPageLimit(UploadedFile $file, int $maxPages): bool
    {
        $contents = $file->get();

        if (! is_string($contents)) {
            throw new RuntimeException('No fue posible leer el archivo PDF cargado.');
        }

        if (! str_starts_with($contents, '%PDF-') || ! str_contains($contents, '%%EOF')) {
            return false;
        }

        preg_match_all('/\/Type\s*\/Page(?!s)\b/', $contents, $pageObjects);
        preg_match_all('/\b\d+\s+\d+\s+obj\b(.*?)\bendobj\b/s', $contents, $objects);

        $pageTreeCounts = [];

        foreach ($objects[1] as $object) {
            if (preg_match('/\/Type\s*\/Pages\b/', $object) === 1
                && preg_match('/\/Count\s+(\d+)\b/', $object, $count) === 1) {
                $pageTreeCounts[] = (int) $count[1];
            }
        }

        $pageCount = max([...$pageTreeCounts, count($pageObjects[0])]);

        return $pageCount > 0 && $pageCount <= $maxPages;
    }
}
