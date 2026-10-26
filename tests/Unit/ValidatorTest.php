<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredFieldsAreReported(): void
    {
        $validator = new Validator(['email' => '', 'password' => '']);

        $errors = $validator->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:10'],
        ]);

        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('password', $errors);
    }

    public function testValidDataPasses(): void
    {
        $validator = new Validator([
            'email' => 'admin@gestion-rrhh.test',
            'password' => 'password123',
        ]);

        $errors = $validator->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'min:10', 'max:255'],
        ]);

        self::assertSame([], $errors);
    }

    public function testEmailFormatIsValidated(): void
    {
        $validator = new Validator(['email' => 'correo-invalido']);

        $errors = $validator->validate(['email' => ['email']]);

        self::assertArrayHasKey('email', $errors);
    }

    public function testMaxLengthUsesCharactersNotBytes(): void
    {
        $validator = new Validator(['name' => 'José Ramírez']);

        $errors = $validator->validate(['name' => ['max:12']]);

        self::assertSame([], $errors);
    }

    public function testDateMustMatchIsoFormat(): void
    {
        $validator = new Validator(['hire_date' => '2026-02-31']);

        $errors = $validator->validate(['hire_date' => ['date']]);

        self::assertArrayHasKey('hire_date', $errors);
    }

    public function testTimeMustBeTwentyFourHourFormat(): void
    {
        $validator = new Validator(['check_in' => '25:10']);

        $errors = $validator->validate(['check_in' => ['time']]);

        self::assertArrayHasKey('check_in', $errors);
    }

    public function testNullableFieldSkipsOtherRules(): void
    {
        $validator = new Validator(['phone' => '']);

        $errors = $validator->validate(['phone' => ['nullable', 'max:30']]);

        self::assertSame([], $errors);
    }

    public function testInRuleRejectsUnknownValues(): void
    {
        $validator = new Validator(['status' => 'deleted']);

        $errors = $validator->validate(['status' => ['in:active,inactive']]);

        self::assertArrayHasKey('status', $errors);
    }

    public function testSameRuleComparesBothFields(): void
    {
        $validator = new Validator([
            'password' => 'password123',
            'password_confirmation' => 'otra-clave',
        ]);

        $errors = $validator->validate([
            'password_confirmation' => ['same:password'],
        ]);

        self::assertArrayHasKey('password_confirmation', $errors);
    }

    public function testRegexRuleAcceptsAValidPhone(): void
    {
        $validator = new Validator(['phone' => '+1 555 0101']);

        $errors = $validator->validate([
            'phone' => ['regex:/^[0-9+().\s-]{6,30}$/u'],
        ]);

        self::assertSame([], $errors);
    }

    public function testRegexRuleRejectsAnInvalidPhone(): void
    {
        $validator = new Validator(['phone' => 'llamar pronto']);

        $errors = $validator->validate([
            'phone' => ['regex:/^[0-9+().\s-]{6,30}$/u'],
        ]);

        self::assertArrayHasKey('phone', $errors);
    }
}
