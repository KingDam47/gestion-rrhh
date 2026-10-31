<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Auth;
use App\Modules\Auth\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginThrottle $throttle
    ) {
    }

    public function attempt(string $email, string $password, string $ip): LoginResult
    {
        $email = mb_strtolower(trim($email));

        if ($this->throttle->tooManyAttempts($email, $ip)) {
            return LoginResult::TooManyAttempts;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->throttle->hit($email, $ip);

            return LoginResult::InvalidCredentials;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $this->throttle->hit($email, $ip);

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

        return LoginResult::Success;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
