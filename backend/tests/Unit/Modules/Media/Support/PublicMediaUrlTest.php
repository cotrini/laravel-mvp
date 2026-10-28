<?php

namespace Tests\Unit\Modules\Media\Support;

use App\Modules\Media\Support\PublicMediaUrl;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaUrlTest extends TestCase
{
    public function test_it_signs_private_r2_object_urls_for_browser_access(): void
    {
        config([
            'filesystems.disks.r2.endpoint' => 'https://account.r2.cloudflarestorage.com',
            'filesystems.disks.r2.bucket' => 'colombiasincomision',
            'filesystems.disks.r2.url' => null,
        ]);
        Storage::fake('r2');
        Storage::disk('r2')->buildTemporaryUrlsUsing(
            fn (string $path, DateTimeInterface $expiration): string => "https://signed.example/{$path}?expires=".$expiration->getTimestamp(),
        );

        $url = PublicMediaUrl::from(
            'https://account.r2.cloudflarestorage.com/colombiasincomision/Pictures/43/photo.png',
        );

        $this->assertStringStartsWith('https://signed.example/Pictures/43/photo.png?expires=', $url);
    }

    public function test_it_signs_object_urls_from_the_configured_public_base_url(): void
    {
        config([
            'filesystems.disks.r2.endpoint' => 'https://account.r2.cloudflarestorage.com',
            'filesystems.disks.r2.bucket' => 'colombiasincomision',
            'filesystems.disks.r2.url' => 'https://media.example.com/storage',
        ]);
        Storage::fake('r2');
        Storage::disk('r2')->buildTemporaryUrlsUsing(
            fn (string $path, DateTimeInterface $expiration): string => "https://signed.example/{$path}?expires=".$expiration->getTimestamp(),
        );

        $url = PublicMediaUrl::from('https://media.example.com/storage/Pictures/43/photo.png');

        $this->assertStringStartsWith('https://signed.example/Pictures/43/photo.png?expires=', $url);
    }

    public function test_it_signs_r2_object_keys_for_browser_access(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->buildTemporaryUrlsUsing(
            fn (string $path, DateTimeInterface $expiration): string => "https://signed.example/{$path}?expires=".$expiration->getTimestamp(),
        );

        $url = PublicMediaUrl::from('Pictures/43/photo.png');

        $this->assertStringStartsWith('https://signed.example/Pictures/43/photo.png?expires=', $url);
    }

    public function test_it_keeps_external_image_urls_unchanged(): void
    {
        $url = 'https://images.example.com/photo.png';

        $this->assertSame($url, PublicMediaUrl::from($url));
    }
}
