<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWriteupRequest extends FormRequest
{
    /**
     * Hanya user login yang aktif (bukan banned) yang boleh membuat write-up.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->status === UserStatus::Active;
    }

    /**
     * author_id, slug, dan published_at sengaja tidak ada di sini: nilainya
     * ditentukan server, bukan input.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'difficulty' => ['required', Rule::enum(WriteupDifficulty::class)],
            'status' => ['required', Rule::enum(WriteupStatus::class)],
            'content_md' => ['required', 'string', 'max:200000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
            'tools' => ['nullable', 'array', 'max:10'],
            'tools.*' => ['integer', 'distinct', 'exists:tools,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'title.string' => 'Judul harus berupa teks.',
            'title.max' => 'Judul maksimal 200 karakter.',
            'category_id.required' => 'Pilih kategori.',
            'category_id.integer' => 'Kategori tidak valid.',
            'category_id.exists' => 'Kategori tidak valid.',
            'difficulty.required' => 'Pilih tingkat kesulitan.',
            'status.required' => 'Status wajib dipilih (draft atau publikasi).',
            'content_md.required' => 'Isi write-up wajib diisi.',
            'content_md.string' => 'Isi write-up harus berupa teks.',
            'content_md.max' => 'Isi write-up maksimal 200.000 karakter.',
            'tags.array' => 'Format tag tidak valid.',
            'tags.max' => 'Pilih maksimal 10 tag.',
            'tags.*.integer' => 'Tag tidak valid.',
            'tags.*.distinct' => 'Tag tidak boleh dipilih dua kali.',
            'tags.*.exists' => 'Tag tidak valid.',
            'tools.array' => 'Format tool tidak valid.',
            'tools.max' => 'Pilih maksimal 10 tool.',
            'tools.*.integer' => 'Tool tidak valid.',
            'tools.*.distinct' => 'Tool tidak boleh dipilih dua kali.',
            'tools.*.exists' => 'Tool tidak valid.',
        ];
    }
}
