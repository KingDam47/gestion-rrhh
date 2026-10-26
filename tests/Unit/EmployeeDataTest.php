<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Employees\Support\EmployeeData;
use PHPUnit\Framework\TestCase;

final class EmployeeDataTest extends TestCase
{
    public function testDocumentRemovesSpacesAndUppercases(): void
    {
        self::assertSame('AB123456', EmployeeData::document("  ab 123 456 "));
    }

    public function testNameCollapsesWhitespace(): void
    {
        self::assertSame('José María', EmployeeData::name("  José   María "));
    }

    public function testEmailIsLowercaseAndEmptyBecomesNull(): void
    {
        self::assertSame('ana@example.test', EmployeeData::email(' ANA@Example.Test '));
        self::assertNull(EmployeeData::email('   '));
        self::assertNull(EmployeeData::email(null));
    }

    public function testPhoneIsTrimmedAndEmptyBecomesNull(): void
    {
        self::assertSame('+1 555 0101', EmployeeData::phone('  +1 555 0101 '));
        self::assertNull(EmployeeData::phone(''));
    }

    public function testDescriptionIsTrimmedAndEmptyBecomesNull(): void
    {
        self::assertSame('Área de soporte', EmployeeData::description('  Área de soporte  '));
        self::assertNull(EmployeeData::description('   '));
    }
}
