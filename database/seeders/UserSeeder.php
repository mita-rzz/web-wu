<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'username' => 'admin',
                'display_name' => 'Administrator',
                'bio' => 'Admin platform.',
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
            ],
            [
                'email' => 'author@example.com',
                'username' => 'author',
                'display_name' => 'Author Contoh',
                'bio' => 'Penulis write-up CTF.',
                'role' => UserRole::Author,
                'status' => UserStatus::Active,
            ],
            [
                'email' => 'banned@example.com',
                'username' => 'banned',
                'display_name' => 'User Diblokir',
                'bio' => null,
                'role' => UserRole::Author,
                'status' => UserStatus::Banned,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->fill([
                'username' => $data['username'],
                'display_name' => $data['display_name'],
                'bio' => $data['bio'],
            ]);

            if (! $user->exists) {
                $user->password_hash = 'password';
            }

            $user->forceFill([
                'role' => $data['role'],
                'status' => $data['status'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }
    }
}