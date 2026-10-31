<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Services\LoginThrottle;
use PHPUnit\Framework\TestCase;

final class LoginThrottleTest extends TestCase
{
    private string $directory;
    private int $now = 1_700_000_000;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'gestion-rrhh-throttle-'
            . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($this->directory);
    }

    public function testEmailIsBlockedAfterFiveFailures(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.10');
        }

        self::assertFalse($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.10'));

        $throttle->hit('admin@gestion-rrhh.test', '192.0.2.10');

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.10'));
    }

    public function testIpIsBlockedAfterTwentyFailuresAcrossEmails(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 19; $attempt++) {
            $throttle->hit('user' . $attempt . '@example.test', '192.0.2.20');
        }

        self::assertFalse($throttle->tooManyAttempts('otra@example.test', '192.0.2.20'));

        $throttle->hit('user19@example.test', '192.0.2.20');

        self::assertTrue($throttle->tooManyAttempts('otra@example.test', '192.0.2.20'));
    }

    public function testSuccessfulLoginClearsEmailButNotIp(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.30');
        }

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.30'));

        $throttle->clearEmail('  ADMIN@gestion-rrhh.test  ');

        self::assertFalse($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.30'));

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.30');
        }

        self::assertFalse($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.30'));

        $throttle->hit('admin@gestion-rrhh.test', '192.0.2.30');

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.30'));
    }

    public function testIpCounterIsNotClearedBySuccessfulLogin(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $throttle->hit('user' . $attempt . '@example.test', '192.0.2.40');
        }

        self::assertTrue($throttle->tooManyAttempts('nueva@example.test', '192.0.2.40'));

        $throttle->clearEmail('user19@example.test');

        self::assertTrue($throttle->tooManyAttempts('nueva@example.test', '192.0.2.40'));
    }

    public function testWindowExpiryAllowsAttemptsAgain(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.50');
        }

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.50'));

        $this->now += 901;

        self::assertFalse($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.50'));

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.50');
        }

        self::assertFalse($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.50'));

        $throttle->hit('admin@gestion-rrhh.test', '192.0.2.50');

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.50'));
    }

    public function testEmailComparisonIsCaseInsensitiveAndTrimmed(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $throttle->hit('  Admin@Gestion-RRHH.test  ', '192.0.2.60');
        }

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.60'));
    }

    public function testDifferentEmailsFromTheSameIpRemainIndependent(): void
    {
        $throttle = $this->throttle();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $throttle->hit('admin@gestion-rrhh.test', '192.0.2.70');
        }

        self::assertTrue($throttle->tooManyAttempts('admin@gestion-rrhh.test', '192.0.2.70'));
        self::assertFalse($throttle->tooManyAttempts('rrhh@gestion-rrhh.test', '192.0.2.70'));
    }

    private function throttle(
        int $emailLimit = 5,
        int $ipLimit = 20,
        int $windowSeconds = 900
    ): LoginThrottle {
        return new LoginThrottle(
            $this->directory,
            $emailLimit,
            $ipLimit,
            $windowSeconds,
            fn (): int => $this->now
        );
    }
}
