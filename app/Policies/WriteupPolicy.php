<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserStatus;
use App\Enums\WriteupStatus;
use App\Models\User;
use App\Models\Writeup;

class WriteupPolicy
{
    /**
     * Write-up yang sudah terbit (sama dengan scope Writeup::published()) boleh dilihat
     * siapa saja, termasuk tamu; selain itu (draft/terjadwal) hanya penulisnya.
     */
    public function view(?User $user, Writeup $writeup): bool
    {
        if ($this->sudahTerbit($writeup)) {
            return true;
        }

        return $user !== null && $writeup->author_id === $user->id;
    }

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

    private function sudahTerbit(Writeup $writeup): bool
    {
        return $writeup->status === WriteupStatus::Published
            && $writeup->published_at !== null
            && $writeup->published_at->lte(now());
    }

    private function milikUserAktif(User $username, Writeup $writeup): bool
    {
        return $username->status === UserStatus::Active && $writeup->author_id === $username->id;
    }
}
