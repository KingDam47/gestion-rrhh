<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\UserData;
use PHPUnit\Framework\TestCase;

final class UserDataTest extends TestCase
{
    public function testNameCollapsesWhitespaceAndTrims(): void
    {
        self::assertSame('María Temporal', UserData::name("  María   Temporal "));
    }

    public function testEmailIsLowercaseAndTrimmed(): void
    {
        self::assertSame('maria.temporal@example.test', UserData::email(' Maria.Temporal@Example.Test '));
    }

    public function testRoleIsLowercaseAndTrimmed(): void
    {
        self::assertSame('admin', UserData::role(' ADMIN '));
        self::assertSame('hr', UserData::role('HR'));
    }

    public function testEmptyValuesStayEmpty(): void
    {
        self::assertSame('', UserData::name('   '));
        self::assertSame('', UserData::email('   '));
        self::assertSame('', UserData::role('   '));
    }
}
