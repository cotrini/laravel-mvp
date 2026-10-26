<?php

namespace Tests\Unit\Modules\Property\Support;

use App\Modules\Property\Support\Mp4DurationValidator;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class Mp4DurationValidatorTest extends TestCase
{
    public function test_accepts_a_version_zero_mp4_of_exactly_one_minute(): void
    {
        $this->assertVideoDuration($this->mp4WithDuration(60), true);
    }

    public function test_rejects_a_version_zero_mp4_longer_than_one_minute(): void
    {
        $this->assertVideoDuration($this->mp4WithDuration(61), false);
    }

    public function test_accepts_a_version_one_mp4_of_one_minute(): void
    {
        $this->assertVideoDuration($this->mp4WithDuration(60, 1), true);
    }

    public function test_rejects_a_malformed_mp4(): void
    {
        $this->assertVideoDuration('not an mp4 file', false);
    }

    private function assertVideoDuration(string $contents, bool $expected): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mp4');

        if ($path === false) {
            $this->fail('Unable to create a temporary video file.');
        }

        try {
            file_put_contents($path, $contents);
            $video = new UploadedFile($path, 'video.mp4', 'video/mp4', UPLOAD_ERR_OK, true);

            $this->assertSame($expected, (new Mp4DurationValidator)->isAtMostOneMinute($video));
        } finally {
            unlink($path);
        }
    }

    private function mp4WithDuration(int $seconds, int $version = 0): string
    {
        $mvhdContent = pack('C4', $version, 0, 0, 0);

        if ($version === 1) {
            $mvhdContent .= pack('N2', 0, 0)
                .pack('N2', 0, 0)
                .pack('N', 1000)
                .pack('N2', 0, $seconds * 1000);
        } else {
            $mvhdContent .= pack('N', 0)
                .pack('N', 0)
                .pack('N', 1000)
                .pack('N', $seconds * 1000);
        }

        $movieHeader = $this->atom('mvhd', $mvhdContent);
        $movie = $this->atom('moov', $movieHeader);
        $fileType = $this->atom('ftyp', 'isom'.pack('N', 0).'isomiso2');

        return $fileType.$movie;
    }

    private function atom(string $type, string $contents): string
    {
        return pack('N', strlen($contents) + 8).$type.$contents;
    }
}
