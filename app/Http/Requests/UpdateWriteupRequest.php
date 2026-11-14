<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Writeup;

/**
 * Aturan validasi dan pesan error diwarisi dari StoreWriteupRequest supaya
 * tidak ada duplikasi; yang berbeda hanya otorisasinya.
 */
class UpdateWriteupRequest extends StoreWriteupRequest
{
    /**
     * Hanya penulis write-up yang aktif (lihat WriteupPolicy::update).
     */
    public function authorize(): bool
    {
        $writeup = $this->route('writeup');

        return $this->user() !== null
            && $writeup instanceof Writeup
            && $this->user()->can('update', $writeup);
    }
}
