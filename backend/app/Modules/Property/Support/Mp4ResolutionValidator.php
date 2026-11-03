<?php

namespace App\Modules\Property\Support;

use Illuminate\Http\UploadedFile;

class Mp4ResolutionValidator
{
    public const MAX_LONG_SIDE = 1920;

    public const MAX_SHORT_SIDE = 1080;

    private const MAX_MOOV_BYTES = 33554432;

    /**
     * Acepta videos hasta Full HD (1920x1080), en horizontal o vertical, leyendo el tkhd de la pista de video.
     */
    public function isAtMostFullHd(UploadedFile $video): bool
    {
        $dimensions = $this->videoDimensions($video);

        if ($dimensions === null) {
            return false;
        }

        return max($dimensions) <= self::MAX_LONG_SIDE && min($dimensions) <= self::MAX_SHORT_SIDE;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    public function videoDimensions(UploadedFile $video): ?array
    {
        $path = $video->getRealPath();
        $fileSize = $video->getSize();
        $file = is_string($path) ? fopen($path, 'rb') : false;

        if ($file === false || ! is_int($fileSize)) {
            return null;
        }

        try {
            $moov = $this->readTopLevelAtom($file, $fileSize, 'moov');
        } finally {
            fclose($file);
        }

        return $moov === null ? null : $this->findVideoTrackDimensions($moov);
    }

    /**
     * @param  resource  $file
     */
    private function readTopLevelAtom($file, int $fileSize, string $type): ?string
    {
        $offset = 0;

        while ($offset + 8 <= $fileSize) {
            if (fseek($file, $offset) !== 0) {
                return null;
            }

            $header = fread($file, 8);

            if ($header === false || strlen($header) !== 8) {
                return null;
            }

            $size = unpack('Nsize', $header)['size'];
            $headerSize = 8;

            if ($size === 1) {
                $extended = fread($file, 8);

                if ($extended === false || strlen($extended) !== 8) {
                    return null;
                }

                $parts = unpack('Nhigh/Nlow', $extended);
                $size = $parts['high'] * 4294967296 + $parts['low'];
                $headerSize = 16;
            } elseif ($size === 0) {
                $size = $fileSize - $offset;
            }

            if ($size < $headerSize || $offset + $size > $fileSize) {
                return null;
            }

            if (substr($header, 4, 4) === $type) {
                $contentSize = $size - $headerSize;

                if ($contentSize > self::MAX_MOOV_BYTES) {
                    return null;
                }

                $content = $contentSize > 0 ? fread($file, $contentSize) : '';

                return is_string($content) && strlen($content) === $contentSize ? $content : null;
            }

            $offset += $size;
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function findVideoTrackDimensions(string $buffer): ?array
    {
        $length = strlen($buffer);
        $offset = 0;

        while ($offset + 8 <= $length) {
            $size = unpack('Nsize', substr($buffer, $offset, 4))['size'];
            $type = substr($buffer, $offset + 4, 4);

            if ($size < 8 || $offset + $size > $length) {
                return null;
            }

            $content = substr($buffer, $offset + 8, $size - 8);

            if ($type === 'trak') {
                $dimensions = $this->findVideoTrackDimensions($content);

                if ($dimensions !== null) {
                    return $dimensions;
                }
            } elseif ($type === 'tkhd') {
                $dimensions = $this->parseTrackHeader($content);

                if ($dimensions !== null && $dimensions[0] > 0 && $dimensions[1] > 0) {
                    return $dimensions;
                }
            }

            $offset += $size;
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseTrackHeader(string $content): ?array
    {
        $widthOffset = match (ord($content[0] ?? "\xFF")) {
            0 => 76,
            1 => 88,
            default => null,
        };

        if ($widthOffset === null || strlen($content) < $widthOffset + 8) {
            return null;
        }

        $size = unpack('Nwidth/Nheight', substr($content, $widthOffset, 8));

        // Width y height se almacenan como punto fijo 16.16.
        return [$size['width'] >> 16, $size['height'] >> 16];
    }
}
