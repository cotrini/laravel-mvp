<?php

namespace App\Modules\Property\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class Mp4DurationValidator
{
    public function isAtMostOneMinute(UploadedFile $video): bool
    {
        $file = fopen($video->getRealPath(), 'rb');

        if ($file === false) {
            throw new RuntimeException('No fue posible leer el video cargado.');
        }

        try {
            $fileSize = $video->getSize();

            if (! is_int($fileSize) || $fileSize <= 0) {
                return false;
            }

            $offset = 0;

            while ($offset < $fileSize) {
                $atom = $this->readAtomHeader($file, $offset, $fileSize);

                if ($atom === null) {
                    return false;
                }

                if ($atom['type'] === 'moov') {
                    $childOffset = $atom['content_offset'];

                    while ($childOffset < $atom['end']) {
                        $child = $this->readAtomHeader($file, $childOffset, $atom['end']);

                        if ($child === null) {
                            return false;
                        }

                        if ($child['type'] === 'mvhd') {
                            return $this->movieHeaderIsAtMostOneMinute($file, $child);
                        }

                        $childOffset = $child['end'];
                    }

                    return false;
                }

                $offset = $atom['end'];
            }
        } finally {
            fclose($file);
        }

        return false;
    }

    /**
     * @param  resource  $file
     * @return array{type: string, content_offset: int, end: int}|null
     */
    private function readAtomHeader($file, int $offset, int $parentEnd): ?array
    {
        if (fseek($file, $offset) !== 0) {
            return null;
        }

        $header = fread($file, 8);

        if ($header === false || strlen($header) !== 8) {
            return null;
        }

        $atomSize = unpack('Nsize', substr($header, 0, 4))['size'];
        $atomType = substr($header, 4, 4);
        $headerSize = 8;

        if ($atomSize === 1) {
            $extendedSize = fread($file, 8);

            if ($extendedSize === false || strlen($extendedSize) !== 8) {
                return null;
            }

            $sizeParts = unpack('Nhigh/Nlow', $extendedSize);
            $atomSize = $sizeParts['high'] * 4294967296 + $sizeParts['low'];
            $headerSize = 16;
        } elseif ($atomSize === 0) {
            $atomSize = $parentEnd - $offset;
        }

        if ($atomSize < $headerSize || $offset + $atomSize > $parentEnd) {
            return null;
        }

        return [
            'type' => $atomType,
            'content_offset' => $offset + $headerSize,
            'end' => $offset + $atomSize,
        ];
    }

    /**
     * @param  resource  $file
     * @param  array{type: string, content_offset: int, end: int}  $atom
     */
    private function movieHeaderIsAtMostOneMinute($file, array $atom): bool
    {
        if (fseek($file, $atom['content_offset']) !== 0) {
            return false;
        }

        $versionAndFlags = fread($file, 4);

        if ($versionAndFlags === false || strlen($versionAndFlags) !== 4) {
            return false;
        }

        $version = ord($versionAndFlags[0]);
        $timeScaleOffset = $version === 1 ? 20 : 12;
        $durationOffset = $version === 1 ? 24 : 16;
        $durationLength = $version === 1 ? 8 : 4;

        if (
            ! in_array($version, [0, 1], true)
            || $atom['end'] < $atom['content_offset'] + $durationOffset + $durationLength
            || fseek($file, $atom['content_offset'] + $timeScaleOffset) !== 0
        ) {
            return false;
        }

        $timeScaleBytes = fread($file, 4);
        $durationBytes = fread($file, $durationOffset - $timeScaleOffset - 4 + $durationLength);

        if ($timeScaleBytes === false || strlen($timeScaleBytes) !== 4 || $durationBytes === false) {
            return false;
        }

        $timeScale = unpack('Nvalue', $timeScaleBytes)['value'];

        if ($timeScale === 0) {
            return false;
        }

        $durationBytes = substr($durationBytes, $durationOffset - $timeScaleOffset - 4, $durationLength);

        if (strlen($durationBytes) !== $durationLength) {
            return false;
        }

        if ($durationLength === 8) {
            $durationParts = unpack('Nhigh/Nlow', $durationBytes);
            $duration = $durationParts['high'] * 4294967296 + $durationParts['low'];
        } else {
            $duration = unpack('Nvalue', $durationBytes)['value'];
        }

        return $duration > 0 && is_finite((float) $duration) && ($duration / $timeScale) <= 60;
    }
}
