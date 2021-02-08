<?php

declare(strict_types=1);

namespace App\DTO\Contracts\User;

use App\Entity;

interface UserListEntryInterface
{
    public function get(Entity\User $user): self;
}
