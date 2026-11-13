<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Pembantu untuk test fitur write-up dan unggah gambar.
 *
 * UserFactory bawaan Breeze masih memakai kolom lama (name, password), jadi
 * user dibuat langsung sesuai skema (username, display_name, password_hash).
 */
trait MembuatDataUji
{
    /**
     * Gambar 2x2 piksel yang valid, supaya test tidak bergantung pada ekstensi GD.
     *
     * @var array<string, string>
     */
    private const GAMBAR_BASE64 = [
        'png' => 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWMU2NnIwMDAxMDAwMDAAAAPMQFOrbEm0QAAAABJRU5ErkJggg==',
        'jpg' => '/9j/4AAQSkZJRgABAQEAYABgAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy/8AAEQgAAgACAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AKKKKZ+cH/9k=',
        'gif' => 'R0lGODdhAgACAIAAABS6hAAAACwAAAAAAgACAAACAoRRADs=',
        'webp' => 'UklGRjAAAABXRUJQVlA4ICQAAABQAQCdASoCAAIAAUAmJaAABOOAAPDP//d5v/+3m//7eb/fkAA=',
    ];

    protected function buatUser(UserStatus $status = UserStatus::Active, string $username = 'penulis'): User
    {
        $user = new User([
            'username' => $username,
            'email' => $username.'@example.com',
            'display_name' => 'Penulis '.$username,
            // Cast "hashed" pada password_hash meng-hash nilainya otomatis.
            'password_hash' => 'password',
        ]);

        // role, status, dan email_verified_at tidak mass-assignable.
        $user->forceFill([
            'role' => UserRole::Author,
            'status' => $status,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    protected function bytesGambar(string $ext): string
    {
        return base64_decode(self::GAMBAR_BASE64[$ext], true);
    }

    /**
     * File sungguhan di disk, bukan UploadedFile::fake(): file palsu Laravel
     * melaporkan MIME dari nama file, padahal server mendeteksinya dari isi.
     */
    protected function fileAsli(string $isi, string $namaClient): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uji');
        file_put_contents($path, $isi);

        $this->beforeApplicationDestroyed(static fn () => @unlink($path));

        return new UploadedFile($path, $namaClient, null, null, true);
    }

    protected function fileGambar(string $ext = 'png', ?string $namaClient = null): UploadedFile
    {
        return $this->fileAsli($this->bytesGambar($ext), $namaClient ?? 'gambar.'.$ext);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    protected function dataWriteup(array $override = []): array
    {
        return array_merge([
            'title' => 'Blind SQL Injection di Login Portal',
            'category_id' => Category::query()->value('id'),
            'difficulty' => 'medium',
            'status' => 'draft',
            'content_md' => "## Ringkasan\n\nForm login rentan.",
        ], $override);
    }
}
