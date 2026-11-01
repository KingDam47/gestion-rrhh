<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AuditLabelTest extends TestCase
{
    public function testKnownActionsHaveSpanishLabels(): void
    {
        self::assertSame('Usuario creado', \audit_action_label('user.created'));
        self::assertSame('Contraseña propia cambiada', \audit_action_label('user.password_changed'));
        self::assertSame('Permiso aprobado', \audit_action_label('leave.approved'));
        self::assertSame('Candidato contratado', \audit_action_label('application.hired'));
        self::assertSame('Inicio de sesión', \audit_action_label('auth.login'));
        self::assertSame('Intento de sesión fallido', \audit_action_label('auth.login_failed'));
        self::assertSame('Acceso bloqueado por intentos', \audit_action_label('auth.login_blocked'));
    }

    public function testUnknownActionFallsBackToReadableText(): void
    {
        self::assertSame('Custom action test', \audit_action_label('custom.action_test'));
    }

    public function testEntityLabels(): void
    {
        self::assertSame('Usuario', \audit_entity_label('user'));
        self::assertSame('Autenticación', \audit_entity_label('auth'));
        self::assertSame('Permiso', \audit_entity_label('leave_request'));
        self::assertSame('Asistencia', \audit_entity_label('attendance_record'));
        self::assertSame('Candidato', \audit_entity_label('candidate'));
        self::assertSame('Otro tipo', \audit_entity_label('otro_tipo'));
    }
}
