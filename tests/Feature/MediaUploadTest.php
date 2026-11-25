<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaFileExt;
use App\Enums\UserStatus;
use App\Models\MediaUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function formatGambar(): array
    {
        return [
            'png' => ['png'],
            'jpg' => ['jpg'],
            'gif' => ['gif'],
            'webp' => ['webp'],
        ];
    }

    #[DataProvider('formatGambar')]
    public function test_gambar_valid_tersimpan_di_disk_public_dan_dicatat(string $ext): void
    {
        $user = $this->buatUser();
        $file = $this->fileGambar($ext, 'Screenshot Saya.'.$ext);

        $response = $this->actingAs($user)->postJson(route('writeups.media.store'), ['file' => $file]);

        $response->assertCreated()->assertJsonStructure(['url', 'id']);

        $media = MediaUpload::query()->findOrFail($response->json('id'));

        Storage::disk('public')->assertExists($media->file_path);
        $this->assertStringStartsWith('writeups/'.now()->format('Y/m').'/', $media->file_path);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.'.$ext.'$/', $media->stored_name);
        $this->assertSame($media->url, $response->json('url'));
        $this->assertStringEndsWith($media->file_path, $response->json('url'));

        $this->assertSame(MediaFileExt::from($ext), $media->file_ext);
        $this->assertSame($user->id, $media->uploader_id);
        $this->assertNull($media->writeup_id);
        $this->assertSame('Screenshot Saya.'.$ext, $media->original_name);
        $this->assertSame(strlen($this->bytesGambar($ext)), $media->file_size);
        $this->assertStringStartsWith('image/', $media->mime_type);
    }

    public function test_ekstensi_dari_client_tidak_dipercaya(): void
    {
        $response = $this->actingAs($this->buatUser())->postJson(route('writeups.media.store'), [
            'file' => $this->fileGambar('png', 'foto.jpg'),
        ]);

        $response->assertCreated();

        $media = MediaUpload::query()->firstOrFail();

        $this->assertSame(MediaFileExt::Png, $media->file_ext);
        $this->assertSame('image/png', $media->mime_type);
        $this->assertStringEndsWith('.png', $media->stored_name);
        $this->assertStringEndsWith('.png', $media->file_path);
    }

    public function test_file_non_gambar_ditolak(): void
    {
        $user = $this->buatUser();

        $penyamar = [
            'skrip php bernama png' => $this->fileAsli('<?php echo 1;', 'shell.png'),
            'teks bernama jpg' => $this->fileAsli('bukan gambar', 'foto.jpg'),
            'pdf' => $this->fileAsli("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n", 'dokumen.pdf'),
            'svg' => $this->fileAsli('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'x.svg'),
            'svg bernama png' => $this->fileAsli('<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'x.png'),
        ];

        foreach ($penyamar as $file) {
            $this->actingAs($user)
                ->postJson(route('writeups.media.store'), ['file' => $file])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('file');
        }

        $this->assertSame(0, MediaUpload::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_file_di_atas_2_mb_ditolak_dan_di_bawahnya_diterima(): void
    {
        $user = $this->buatUser();

        $terlaluBesar = $this->fileAsli(
            $this->bytesGambar('png').str_repeat("\0", 2 * 1024 * 1024 + 1024),
            'besar.png',
        );
        $masihBoleh = $this->fileAsli(
            $this->bytesGambar('png').str_repeat("\0", 1536 * 1024),
            'sedang.png',
        );

        $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $terlaluBesar])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'Ukuran gambar maksimal 2 MB.');

        $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $masihBoleh])
            ->assertCreated();

        $this->assertSame(1, MediaUpload::query()->count());
    }

    public function test_file_wajib_ada(): void
    {
        $this->actingAs($this->buatUser())
            ->postJson(route('writeups.media.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_tamu_tidak_bisa_mengunggah(): void
    {
        $this->postJson(route('writeups.media.store'), ['file' => $this->fileGambar()])
            ->assertUnauthorized();

        $this->assertSame(0, MediaUpload::query()->count());
    }

    public function test_user_banned_ditolak_403(): void
    {
        $banned = $this->buatUser(UserStatus::Banned, 'diblokir');

        $this->actingAs($banned)
            ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar()])
            ->assertForbidden();

        $this->assertSame(0, MediaUpload::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_unggahan_dibatasi_20_per_menit(): void
    {
        $user = $this->buatUser();

        for ($i = 1; $i <= 20; $i++) {
            $this->actingAs($user)
                ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar()])
                ->assertCreated();
        }

        $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar()])
            ->assertTooManyRequests();
    }
}
