<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MediaFileExt;
use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaUploadRequest extends FormRequest
{
    /**
     * Hanya user login yang aktif (bukan banned) yang boleh mengunggah gambar.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->status === UserStatus::Active;
    }

    /**
     * Ekstensi yang diizinkan diambil dari enum MediaFileExt. Laravel memeriksa
     * isi file (bukan ekstensi bawaan client) untuk aturan image dan mimes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extensions = implode(',', array_column(MediaFileExt::cases(), 'value'));

        return [
            'file' => ['required', 'file', 'image', 'mimes:'.$extensions, 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih gambar yang akan diunggah.',
            'file.file' => 'Unggahan harus berupa file.',
            'file.uploaded' => 'Gambar gagal diunggah. Periksa ukuran file (maksimal 2 MB).',
            'file.image' => 'File harus berupa gambar.',
            'file.mimes' => 'Format gambar harus jpg, jpeg, png, gif, atau webp.',
            'file.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}
