<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Writeup;

class WriteupPolicy
{
    /**
     * Hanya penulis write-up tersebut, dan hanya bila akunnya masih aktif.
     */
    public function update(User $username, Writeup $writeup): bool
    {
        return $this->milikUserAktif($username, $writeup);
    }

    public function delete(User $username, Writeup $writeup): bool
    {
        return $this->milikUserAktif($username, $writeup);
    }

    private function milikUserAktif(User $username, Writeup $writeup): bool
    {
        return $username->status === UserStatus::Active && $writeup->author_id === $username->id;
    }
}
