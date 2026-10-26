<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Attendance\Support\AttendanceData;
use PHPUnit\Framework\TestCase;

final class AttendanceDataTest extends TestCase
{
    public function testTimeNormalizesToDatabaseFormat(): void
    {
        self::assertSame('08:05:00', AttendanceData::time('08:05'));
        self::assertSame('08:05:30', AttendanceData::time('08:05:30'));
        self::assertSame('17:00:00', AttendanceData::time(' 17:00 '));
    }

    public function testEmptyTimeBecomesNull(): void
    {
        self::assertNull(AttendanceData::time(''));
        self::assertNull(AttendanceData::time(null));
    }

    public function testDateOrNullRejectsInvalidDates(): void
    {
        self::assertSame('2026-10-26', AttendanceData::dateOrNull('2026-10-26'));
        self::assertNull(AttendanceData::dateOrNull('2026-02-30'));
        self::assertNull(AttendanceData::dateOrNull('26/10/2026'));
        self::assertNull(AttendanceData::dateOrNull(''));
    }

    public function testTimeOrdering(): void
    {
        self::assertTrue(AttendanceData::timeIsAfterOrEqual('08:00:00', '17:00:00'));
        self::assertTrue(AttendanceData::timeIsAfterOrEqual('08:00:00', '08:00:00'));
        self::assertFalse(AttendanceData::timeIsAfterOrEqual('17:00:00', '08:00:00'));
    }

    public function testMonthNormalizationAndRange(): void
    {
        self::assertSame('2026-10', AttendanceData::month('2026-10'));
        self::assertSame('2026-02', AttendanceData::month('2026-02'));
        self::assertSame(date('Y-m'), AttendanceData::month('2026-13'));
        self::assertSame('octubre 2026', AttendanceData::monthLabel('2026-10'));

        self::assertSame(
            ['start' => '2026-02-01', 'end' => '2026-02-28'],
            AttendanceData::monthRange('2026-02')
        );
        self::assertSame(
            ['start' => '2026-10-01', 'end' => '2026-10-31'],
            AttendanceData::monthRange('2026-10')
        );
    }

    public function testDaysBetweenIncludesBothEnds(): void
    {
        self::assertSame(1, AttendanceData::daysBetween('2026-10-01', '2026-10-01'));
        self::assertSame(5, AttendanceData::daysBetween('2026-10-01', '2026-10-05'));
        self::assertSame(0, AttendanceData::daysBetween('2026-10-05', '2026-10-01'));
    }

    public function testNotesAreTrimmedAndEmptyBecomesNull(): void
    {
        self::assertSame('Ingreso con tardanza.', AttendanceData::notes('  Ingreso con tardanza.  '));
        self::assertNull(AttendanceData::notes('   '));
        self::assertNull(AttendanceData::notes(null));
    }
}
