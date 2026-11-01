<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Auth;
use App\Modules\Auth\Repositories\UserRepository;
use App\Shared\Support\AuditLogger;

final class AuthService
{
    /**
     * Hash ficticio para que un correo inexistente tarde lo mismo que uno real.
     * No corresponde a ninguna contraseña utilizable.
     *
     * Debe tener el mismo coste que PASSWORD_DEFAULT (12 en PHP 8.4) y
     * regenerarse si ese valor por defecto cambia. AuthTimingTest lo verifica.
     */
    private const TIMING_HASH = '$2y$12$3ixVxY.y5rgbXV5V6MhOlun0FVVuFOoxELpVu3g2e5dW7V9Me0jMa';

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginThrottle $throttle,
        private readonly AuditLogger $audit
    ) {
    }

    public function attempt(string $email, string $password, string $ip): LoginResult
    {
        $email = mb_strtolower(trim($email));

        if ($this->throttle->tooManyAttempts($email, $ip)) {
            $this->audit->record(
                null,
                'auth.login_blocked',
                'auth',
                null,
                ['reason' => 'throttle'],
                $ip
            );

            return LoginResult::TooManyAttempts;
        }

        $user = $this->users->findByEmail($email);
        $hash = $user !== null ? (string) $user['password_hash'] : self::TIMING_HASH;
        $passwordMatches = password_verify($password, $hash);

        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->throttle->hit($email, $ip);
            $this->audit->record(
                $user !== null ? (int) $user['id'] : null,
                'auth.login_failed',
                'auth',
                $user !== null ? (int) $user['id'] : null,
                ['reason' => $user === null ? 'unknown_user' : 'inactive_user'],
                $ip
            );

            return LoginResult::InvalidCredentials;
        }

        if (!$passwordMatches) {
            $this->throttle->hit($email, $ip);
            $this->audit->record(
                (int) $user['id'],
                'auth.login_failed',
                'auth',
                (int) $user['id'],
                ['reason' => 'bad_password'],
                $ip
            );

            return LoginResult::InvalidCredentials;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            // Un rehash no cambia la contraseña: no debe invalidar la sesión.
            $this->users->updatePasswordHash(
                (int) $user['id'],
                password_hash($password, PASSWORD_DEFAULT),
                false
            );
        }

        Auth::login($user);
        $this->users->touchLastLogin((int) $user['id']);
        $this->throttle->clearEmail($email);
        $this->audit->record(
            (int) $user['id'],
            'auth.login',
            'auth',
            (int) $user['id'],
            [],
            $ip
        );

        return LoginResult::Success;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
