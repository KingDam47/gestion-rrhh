<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Contracts\UserLookup;
use App\Modules\Auth\Repositories\UserRepository;

final class UserLookupService implements UserLookup
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function names(array $userIds): array
    {
        return $this->users->names($userIds);
    }
}
