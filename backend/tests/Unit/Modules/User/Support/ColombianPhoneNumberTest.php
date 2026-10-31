<?php

namespace Tests\Unit\Modules\User\Support;

use App\Modules\User\Support\ColombianPhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ColombianPhoneNumberTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_normalizes_colombian_mobile_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, ColombianPhoneNumber::normalize($input));
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_numbers_that_are_not_colombian_mobiles(mixed $input): void
    {
        $this->assertSame(null, ColombianPhoneNumber::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validNumbers(): array
    {
        return [
            'ten national digits' => ['3001234567', '+573001234567'],
            'plus country code' => ['+57 300 123 4567', '+573001234567'],
            'country code without plus' => ['573001234567', '+573001234567'],
            'dashes and parentheses' => ['(300) 123-4567', '+573001234567'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'landline' => ['6012345678'],
            'too short' => ['300123456'],
            'foreign number' => ['+1 2025550123'],
            'letters' => ['300ABC4567'],
            'empty' => [''],
            'array' => [['3001234567']],
        ];
    }
}
