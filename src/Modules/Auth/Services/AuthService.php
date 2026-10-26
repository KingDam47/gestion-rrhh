<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Auth;
use App\Core\Session;
use App\Modules\Auth\Repositories\UserRepository;

final class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;
    private const ATTEMPTS_KEY = '_login_attempts';

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function attempt(string $email, string $password): LoginResult
    {
        if ($this->isLockedOut()) {
            return LoginResult::TooManyAttempts;
        }

        $email = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->registerFailure();

            return LoginResult::InvalidCredentials;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $this->registerFailure();

            return LoginResult::InvalidCredentials;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::login($user);
        $this->users->touchLastLogin((int) $user['id']);
        Session::forget(self::ATTEMPTS_KEY);

        return LoginResult::Success;
    }

    public function logout(): void
    {
        Auth::logout();
    }

    private function isLockedOut(): bool
    {
        $attempts = Session::get(self::ATTEMPTS_KEY, []);

        if (!is_array($attempts)) {
            return false;
        }

        return (int) ($attempts['locked_until'] ?? 0) > time();
    }

    private function registerFailure(): void
    {
        $attempts = Session::get(self::ATTEMPTS_KEY, []);
        $count = is_array($attempts) ? (int) ($attempts['count'] ?? 0) : 0;
        $count++;

        $data = ['count' => $count];

        if ($count >= self::MAX_ATTEMPTS) {
            $data = [
                'count' => 0,
                'locked_until' => time() + self::LOCKOUT_SECONDS,
            ];
        }

        Session::put(self::ATTEMPTS_KEY, $data);
    }
}
