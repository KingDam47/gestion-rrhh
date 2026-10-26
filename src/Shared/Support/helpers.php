<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\OldInput;

/**
 * Helpers de presentación. Sin consultas ni reglas de negocio.
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, string $default = ''): string
{
    return OldInput::get($key, $default);
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date($format, $timestamp);
}

function format_time(?string $time): string
{
    if ($time === null || $time === '') {
        return '—';
    }

    $timestamp = strtotime($time);

    return $timestamp === false ? '—' : date('H:i', $timestamp);
}

function status_label(string $status): string
{
    return match ($status) {
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'present' => 'Presente',
        'late' => 'Tardanza',
        'absent' => 'Ausencia',
        'pending' => 'Pendiente',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'vacation' => 'Vacaciones',
        'personal' => 'Permiso personal',
        'sick' => 'Incapacidad',
        'other' => 'Otro',
        'open' => 'Abierta',
        'closed' => 'Cerrada',
        'received' => 'Recibida',
        'screening' => 'En evaluación',
        'interview' => 'Entrevista',
        'selected' => 'Seleccionada',
        'admin' => 'Administrador',
        'hr' => 'Personal de RR. HH.',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function audit_action_label(string $action): string
{
    return match ($action) {
        'user.login' => 'Inicio de sesión',
        'user.created' => 'Usuario creado',
        'user.updated' => 'Usuario actualizado',
        'user.activated' => 'Usuario reactivado',
        'user.deactivated' => 'Usuario desactivado',
        'user.password_reset' => 'Contraseña restablecida',
        'user.password_changed' => 'Contraseña propia cambiada',
        'employee.deactivated' => 'Empleado desactivado',
        'employee.reactivated' => 'Empleado reactivado',
        'department.activated' => 'Departamento reactivado',
        'department.deactivated' => 'Departamento desactivado',
        'position.activated' => 'Cargo reactivado',
        'position.deactivated' => 'Cargo desactivado',
        'attendance.created' => 'Asistencia registrada',
        'attendance.updated' => 'Asistencia actualizada',
        'leave.approved' => 'Permiso aprobado',
        'leave.rejected' => 'Permiso rechazado',
        'vacancy.closed' => 'Vacante cerrada',
        'application.created' => 'Postulación registrada',
        'application.status_changed' => 'Estado de postulación cambiado',
        'application.selected' => 'Postulación seleccionada',
        'application.rejected' => 'Postulación rechazada',
        'application.hired' => 'Candidato contratado',
        default => ucfirst(str_replace(['.', '_'], ' ', $action)),
    };
}

function audit_entity_label(string $entityType): string
{
    return match ($entityType) {
        'user' => 'Usuario',
        'employee' => 'Empleado',
        'department' => 'Departamento',
        'position' => 'Cargo',
        'leave_request' => 'Permiso',
        'attendance_record' => 'Asistencia',
        'vacancy' => 'Vacante',
        'candidate' => 'Candidato',
        'application' => 'Postulación',
        default => ucfirst(str_replace('_', ' ', $entityType)),
    };
}
