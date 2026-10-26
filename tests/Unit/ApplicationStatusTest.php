<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Recruitment\Support\ApplicationStatus;
use PHPUnit\Framework\TestCase;

final class ApplicationStatusTest extends TestCase
{
    public function testAllReturnsTheFiveStatuses(): void
    {
        self::assertSame(
            ['received', 'screening', 'interview', 'selected', 'rejected'],
            ApplicationStatus::all()
        );
    }

    public function testValidTransitionsAreAllowed(): void
    {
        $expected = [
            'received' => ['screening', 'rejected'],
            'screening' => ['interview', 'rejected'],
            'interview' => ['selected', 'rejected'],
        ];

        foreach ($expected as $from => $targets) {
            self::assertSame($targets, ApplicationStatus::next($from));

            foreach ($targets as $to) {
                self::assertTrue(ApplicationStatus::allows($from, $to), "$from -> $to debe permitirse");
            }
        }
    }

    public function testInvalidJumpsAreRejected(): void
    {
        self::assertFalse(ApplicationStatus::allows('received', 'interview'));
        self::assertFalse(ApplicationStatus::allows('received', 'selected'));
        self::assertFalse(ApplicationStatus::allows('screening', 'selected'));
        self::assertFalse(ApplicationStatus::allows('interview', 'received'));
        self::assertFalse(ApplicationStatus::allows('selected', 'rejected'));
        self::assertFalse(ApplicationStatus::allows('rejected', 'screening'));
        self::assertFalse(ApplicationStatus::allows('unknown', 'received'));
    }

    public function testSelectedAndRejectedAreFinal(): void
    {
        self::assertTrue(ApplicationStatus::isFinal('selected'));
        self::assertTrue(ApplicationStatus::isFinal('rejected'));
        self::assertSame([], ApplicationStatus::next('selected'));
        self::assertSame([], ApplicationStatus::next('rejected'));

        self::assertFalse(ApplicationStatus::isFinal('received'));
        self::assertFalse(ApplicationStatus::isFinal('screening'));
        self::assertFalse(ApplicationStatus::isFinal('interview'));
    }

    public function testIsStatusRecognizesOnlyKnownStatuses(): void
    {
        foreach (ApplicationStatus::all() as $status) {
            self::assertTrue(ApplicationStatus::isStatus($status));
        }

        self::assertFalse(ApplicationStatus::isStatus('hired'));
        self::assertFalse(ApplicationStatus::isStatus(''));
    }
}
