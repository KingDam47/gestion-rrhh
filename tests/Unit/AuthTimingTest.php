<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Services\AuthService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthTimingTest extends TestCase
{
    public function testDummyHashUsesTheSameCostAsTheDefaultAlgorithm(): void
    {
        $reference = password_get_info(password_hash('referencia', PASSWORD_DEFAULT));
        $dummy = password_get_info(self::timingHash());

        self::assertSame('bcrypt', $dummy['algoName']);
        self::assertSame(
            $reference['options']['cost'] ?? null,
            $dummy['options']['cost'] ?? null,
            'AuthService::TIMING_HASH debe regenerarse cuando cambie el coste de PASSWORD_DEFAULT.'
        );
    }

    public function testDummyHashDoesNotMatchCommonPasswords(): void
    {
        foreach (['password123', 'admin', '1234567890'] as $candidate) {
            self::assertFalse(password_verify($candidate, self::timingHash()));
        }
    }

    private static function timingHash(): string
    {
        return (string) (new ReflectionClass(AuthService::class))->getConstant('TIMING_HASH');
    }
}
