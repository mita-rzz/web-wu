<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['username', 'display_name'] as $kolom) {
            if (is_string($this->input($kolom))) {
                $data[$kolom] = trim($this->input($kolom));
            }
        }

        if (is_string($this->input('email'))) {
            $data['email'] = mb_strtolower(trim($this->input('email')));
        }

        if ($this->has('bio')) {
            $bio = $this->input('bio');
            $data['bio'] = is_string($bio) && trim($bio) !== '' ? trim($bio) : null;
        }

        $this->merge($data);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $idSendiri = $this->user()->id;

        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique(User::class)->ignore($idSendiri),
                function (string $attribute, mixed $value, Closure $fail) use ($idSendiri): void {
                    $sudahAda = User::query()
                        ->whereRaw('lower(username) = ?', [mb_strtolower((string) $value)])
                        ->whereKeyNot($idSendiri)
                        ->exists();

                    if ($sudahAda) {
                        $fail('Username sudah dipakai.');
                    }
                },
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($idSendiri),
            ],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->input('email') !== $this->user()->email),
                'string',
                'current_password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, garis bawah (_), titik (.), dan strip (-).',
            'username.unique' => 'Username sudah dipakai.',
            'display_name.required' => 'Nama tampilan wajib diisi.',
            'display_name.string' => 'Nama tampilan harus berupa teks.',
            'display_name.max' => 'Nama tampilan maksimal 100 karakter.',
            'bio.string' => 'Bio harus berupa teks.',
            'bio.max' => 'Bio maksimal 500 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.lowercase' => 'Email harus berupa huruf kecil.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah dipakai.',
            'current_password.required' => 'Password saat ini wajib diisi untuk mengganti email.',
            'current_password.string' => 'Password saat ini harus berupa teks.',
            'current_password.current_password' => 'Password saat ini salah.',
        ];
    }
}
