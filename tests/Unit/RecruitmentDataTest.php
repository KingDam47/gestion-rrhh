<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Recruitment\Support\RecruitmentData;
use PHPUnit\Framework\TestCase;

final class RecruitmentDataTest extends TestCase
{
    public function testNameCollapsesWhitespace(): void
    {
        self::assertSame('Ana María', RecruitmentData::name("  Ana   María "));
        self::assertSame('', RecruitmentData::name(null));
    }

    public function testEmailIsLowercaseAndEmptyBecomesNull(): void
    {
        self::assertSame('ana@example.test', RecruitmentData::email(' Ana@Example.Test '));
        self::assertNull(RecruitmentData::email('   '));
        self::assertNull(RecruitmentData::email(null));
    }

    public function testPhoneAndNotesBecomeNullWhenEmpty(): void
    {
        self::assertSame('+1 555 0101', RecruitmentData::phone(' +1 555 0101 '));
        self::assertNull(RecruitmentData::phone(''));
        self::assertNull(RecruitmentData::phone(null));

        self::assertSame('Disponibilidad inmediata', RecruitmentData::notes(' Disponibilidad inmediata '));
        self::assertNull(RecruitmentData::notes('   '));
    }

    public function testDateOrNullAcceptsOnlyRealIsoDates(): void
    {
        self::assertSame('2026-10-26', RecruitmentData::dateOrNull('2026-10-26'));
        self::assertSame('2026-02-28', RecruitmentData::dateOrNull('2026-02-28'));
        self::assertNull(RecruitmentData::dateOrNull('2026-02-30'));
        self::assertNull(RecruitmentData::dateOrNull('26/10/2026'));
        self::assertNull(RecruitmentData::dateOrNull(''));
        self::assertNull(RecruitmentData::dateOrNull(null));
    }

    public function testDateTrimsWithoutValidating(): void
    {
        self::assertSame('2026-10-26', RecruitmentData::date(' 2026-10-26 '));
        self::assertSame('', RecruitmentData::date(null));
    }
}
