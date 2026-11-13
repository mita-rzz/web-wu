<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\MediaUpload;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\Writeup;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class WriteupCreateTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaxonomySeeder::class);
        $this->withoutVite();
        Storage::fake('public');
    }

    // --- Akses ---------------------------------------------------------

    public function test_tamu_diarahkan_ke_login_saat_membuka_form(): void
    {
        $this->get('/writeups/create')->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_bisa_menyimpan_write_up(): void
    {
        $this->post(route('writeups.store'), $this->dataWriteup())->assertRedirect(route('login'));

        $this->assertSame(0, Writeup::query()->count());
    }

    public function test_user_aktif_bisa_login_dengan_skema_users_dan_membuka_form(): void
    {
        $user = $this->buatUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->get(route('writeups.create'))
            ->assertOk()
            ->assertSee('Simpan sebagai draft')
            ->assertSee('Publikasikan')
            ->assertSee('Sisipkan gambar')
            ->assertSee('name="_token"', false)
            ->assertSee(Category::query()->value('name'))
            ->assertSee(Tag::query()->value('name'))
            ->assertSee(Tool::query()->value('name'));
    }

    public function test_form_memuat_semua_pilihan_dari_database_dan_enum(): void
    {
        $response = $this->actingAs($this->buatUser())->get(route('writeups.create'))->assertOk();

        foreach ([Category::query(), Tag::query(), Tool::query()] as $query) {
            foreach ($query->pluck('name') as $nama) {
                $response->assertSee($nama);
            }
        }

        foreach (WriteupDifficulty::cases() as $level) {
            $response->assertSee('value="'.$level->value.'"', false);
        }
    }

    public function test_tombol_create_post_menuju_route_form_dan_menampilkan_display_name(): void
    {
        $this->get('/')->assertOk()->assertSee('href="'.route('writeups.create').'"', false);

        $this->actingAs($this->buatUser())->get('/')->assertSee('Penulis penulis');
    }

    public function test_user_banned_tidak_bisa_membuka_form_maupun_menyimpan(): void
    {
        $banned = $this->buatUser(UserStatus::Banned, 'diblokir');

        $this->actingAs($banned)->get(route('writeups.create'))->assertForbidden();
        $this->actingAs($banned)->post(route('writeups.store'), $this->dataWriteup())->assertForbidden();

        $this->assertSame(0, Writeup::query()->count());
    }

    // --- Penyimpanan ---------------------------------------------------

    public function test_user_aktif_bisa_menyimpan_draft(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['status' => 'draft']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertSame(WriteupStatus::Draft, $writeup->status);
        $this->assertNull($writeup->published_at);
        $this->assertSame($user->id, $writeup->author_id);
        $this->assertSame('blind-sql-injection-di-login-portal', $writeup->slug);
        $this->assertSame(WriteupDifficulty::Medium, $writeup->difficulty);
        $this->assertFalse(Writeup::published()->whereKey($writeup->id)->exists());
        $this->assertTrue(Writeup::draft()->whereKey($writeup->id)->exists());
    }

    public function test_user_aktif_bisa_mempublikasikan_write_up(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['status' => 'published']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertSame(WriteupStatus::Published, $writeup->status);
        $this->assertNotNull($writeup->published_at);
        $this->assertTrue(Writeup::published()->whereKey($writeup->id)->exists());
    }

    public function test_flash_status_tampil_setelah_redirect(): void
    {
        $this->actingAs($this->buatUser())
            ->followingRedirects()
            ->post(route('writeups.store'), $this->dataWriteup(['status' => 'published']))
            ->assertOk()
            ->assertSee('Write-up berhasil dipublikasikan.');

        $this->actingAs($this->buatUser(username: 'lain'))
            ->followingRedirects()
            ->post(route('writeups.store'), $this->dataWriteup(['title' => 'Lain', 'status' => 'draft']))
            ->assertSee('Write-up berhasil disimpan sebagai draft.');
    }

    public function test_tag_dan_tool_tersimpan_lewat_pivot(): void
    {
        $tagIds = Tag::query()->orderBy('id')->limit(3)->pluck('id')->all();
        $toolIds = Tool::query()->orderBy('id')->limit(2)->pluck('id')->all();

        $this->actingAs($this->buatUser())
            ->post(route('writeups.store'), $this->dataWriteup(['tags' => $tagIds, 'tools' => $toolIds]))
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertEqualsCanonicalizing($tagIds, $writeup->tags()->pluck('tags.id')->all());
        $this->assertEqualsCanonicalizing($toolIds, $writeup->tools()->pluck('tools.id')->all());
    }

    public function test_tag_dan_tool_boleh_kosong(): void
    {
        $this->actingAs($this->buatUser())
            ->post(route('writeups.store'), $this->dataWriteup())
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertSame(0, $writeup->tags()->count());
        $this->assertSame(0, $writeup->tools()->count());
    }

    public function test_author_id_slug_dan_published_at_dari_input_diabaikan(): void
    {
        $user = $this->buatUser();
        $orangLain = $this->buatUser(username: 'orang-lain');

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup([
                'author_id' => $orangLain->id,
                'slug' => 'slug-curian',
                'published_at' => '2000-01-01 00:00:00',
                'status' => 'draft',
            ]))
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertSame($user->id, $writeup->author_id);
        $this->assertSame('blind-sql-injection-di-login-portal', $writeup->slug);
        $this->assertNull($writeup->published_at);

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup([
                'title' => 'Judul Kedua',
                'author_id' => $orangLain->id,
                'published_at' => '2000-01-01 00:00:00',
                'status' => 'published',
            ]));

        $kedua = Writeup::query()->where('title', 'Judul Kedua')->firstOrFail();

        $this->assertSame($user->id, $kedua->author_id);
        $this->assertTrue($kedua->published_at->isAfter(now()->subMinute()));
    }

    // --- Slug ----------------------------------------------------------

    public function test_judul_kembar_menghasilkan_slug_unik(): void
    {
        $user = $this->buatUser();

        foreach (range(1, 3) as $_) {
            $this->actingAs($user)->post(route('writeups.store'), $this->dataWriteup(['title' => 'Judul Kembar']));
        }

        $this->assertSame(
            ['judul-kembar', 'judul-kembar-2', 'judul-kembar-3'],
            Writeup::query()->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_slug_write_up_yang_di_soft_delete_tidak_dipakai_ulang(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->post(route('writeups.store'), $this->dataWriteup(['title' => 'Judul Lama']));
        Writeup::query()->firstOrFail()->delete();

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['title' => 'Judul Lama']))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['judul-lama', 'judul-lama-2'],
            Writeup::withTrashed()->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_slug_tidak_pernah_melebihi_220_karakter(): void
    {
        $user = $this->buatUser();

        // "Æ" menjadi "ae" saat di-slug, jadi 200 karakter judul menghasilkan slug 400 karakter.
        foreach ([str_repeat('a', 200), str_repeat('Æ', 200)] as $judul) {
            foreach (range(1, 3) as $_) {
                $this->actingAs($user)
                    ->post(route('writeups.store'), $this->dataWriteup(['title' => $judul]))
                    ->assertSessionHasNoErrors();
            }
        }

        $slugs = Writeup::query()->pluck('slug');

        $this->assertCount(6, $slugs);
        $this->assertCount(6, $slugs->unique());

        foreach ($slugs as $slug) {
            $this->assertLessThanOrEqual(220, strlen($slug));
        }
    }

    public function test_judul_tanpa_huruf_atau_angka_memakai_slug_cadangan(): void
    {
        $this->actingAs($this->buatUser())
            ->post(route('writeups.store'), $this->dataWriteup(['title' => '!!!']))
            ->assertSessionHasNoErrors();

        $this->assertSame('writeup', Writeup::query()->firstOrFail()->slug);
    }

    // --- Validasi ------------------------------------------------------

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function inputTidakValid(): array
    {
        return [
            'difficulty tidak ada di enum' => [['difficulty' => 'godlike'], 'difficulty'],
            'difficulty kosong' => [['difficulty' => ''], 'difficulty'],
            'status tidak ada di enum' => [['status' => 'archived'], 'status'],
            'status kosong' => [['status' => ''], 'status'],
            'category_id tidak ada' => [['category_id' => 9999], 'category_id'],
            'category_id bukan angka' => [['category_id' => 'web'], 'category_id'],
            'tag tidak ada' => [['tags' => [9999]], 'tags.0'],
            'tag bukan angka' => [['tags' => ['xss']], 'tags.0'],
            'tag bukan array' => [['tags' => 'xss'], 'tags'],
            'tool tidak ada' => [['tools' => [9999]], 'tools.0'],
            'tag dobel' => [['tags' => [1, 1]], 'tags.0'],
            'judul kosong' => [['title' => ''], 'title'],
            'judul lebih dari 200 karakter' => [['title' => str_repeat('x', 201)], 'title'],
            'isi kosong' => [['content_md' => ''], 'content_md'],
            'isi lebih dari 200000 karakter' => [['content_md' => str_repeat('x', 200001)], 'content_md'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('inputTidakValid')]
    public function test_input_tidak_valid_ditolak(array $override, string $fieldError): void
    {
        $this->actingAs($this->buatUser())
            ->from(route('writeups.create'))
            ->post(route('writeups.store'), $this->dataWriteup($override))
            ->assertRedirect(route('writeups.create'))
            ->assertSessionHasErrors($fieldError);

        $this->assertSame(0, Writeup::query()->count());
    }

    public function test_lebih_dari_10_tag_atau_tool_ditolak(): void
    {
        foreach (range(1, 2) as $i) {
            Tag::query()->create(['name' => "Tag Tambahan {$i}", 'slug' => "tag-tambahan-{$i}"]);
            Tool::query()->create(['name' => "Tool Tambahan {$i}", 'slug' => "tool-tambahan-{$i}"]);
        }

        $user = $this->buatUser();

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['tags' => Tag::query()->limit(11)->pluck('id')->all()]))
            ->assertSessionHasErrors('tags');

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['tools' => Tool::query()->limit(11)->pluck('id')->all()]))
            ->assertSessionHasErrors('tools');

        $this->assertSame(0, Writeup::query()->count());

        // Tepat 10 masih boleh.
        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['tags' => Tag::query()->limit(10)->pluck('id')->all()]))
            ->assertSessionHasNoErrors();

        $this->assertSame(10, Writeup::query()->firstOrFail()->tags()->count());
    }

    public function test_input_lama_dipertahankan_dan_error_tampil_di_form(): void
    {
        // Mengikuti redirect seperti browser: kembali ke form dengan error dan old().
        $this->actingAs($this->buatUser())
            ->from(route('writeups.create'))
            ->followingRedirects()
            ->post(route('writeups.store'), $this->dataWriteup([
                'title' => 'Judul Saya',
                'content_md' => '',
                'difficulty' => 'hard',
            ]))
            ->assertOk()
            ->assertSee('value="Judul Saya"', false)
            ->assertSee('Isi write-up wajib diisi.')
            ->assertSeeInOrder(['value="hard"', 'checked'], false);

        $this->assertSame(0, Writeup::query()->count());
    }

    // --- Media ---------------------------------------------------------

    public function test_media_yang_dipakai_di_content_md_tertaut_ke_write_up(): void
    {
        $user = $this->buatUser();
        $orangLain = $this->buatUser(username: 'orang-lain');

        $dipakai = $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar('png')])
            ->assertCreated()
            ->json();
        $tidakDipakai = $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar('webp')])
            ->assertCreated()
            ->json();

        // Media milik user lain yang pathnya dikutip tetap tidak boleh ikut tertaut.
        $milikOrangLain = MediaUpload::query()->create([
            'uploader_id' => $orangLain->id,
            'original_name' => 'x.png',
            'stored_name' => 'contoh-orang-lain.png',
            'file_path' => 'writeups/2026/11/contoh-orang-lain.png',
            'file_ext' => 'png',
            'mime_type' => 'image/png',
            'file_size' => 100,
        ]);

        $konten = "## Bukti\n\n![bukti]({$dipakai['url']})\n\n![curian](/storage/{$milikOrangLain->file_path})\n";

        $this->actingAs($user)
            ->post(route('writeups.store'), $this->dataWriteup(['content_md' => $konten]))
            ->assertSessionHasNoErrors();

        $writeup = Writeup::query()->firstOrFail();

        $this->assertSame($writeup->id, MediaUpload::query()->findOrFail($dipakai['id'])->writeup_id);
        $this->assertNull(MediaUpload::query()->findOrFail($tidakDipakai['id'])->writeup_id);
        $this->assertNull($milikOrangLain->fresh()->writeup_id);
        $this->assertSame([$dipakai['id']], $writeup->mediaUploads()->pluck('id')->all());
    }

    public function test_media_yang_sudah_tertaut_tidak_dipindahkan(): void
    {
        $user = $this->buatUser();

        $media = $this->actingAs($user)
            ->postJson(route('writeups.media.store'), ['file' => $this->fileGambar('png')])
            ->json();
        $konten = "![a]({$media['url']})";

        $this->actingAs($user)->post(route('writeups.store'), $this->dataWriteup(['title' => 'Pertama', 'content_md' => $konten]));
        $pertama = Writeup::query()->where('title', 'Pertama')->firstOrFail();

        $this->actingAs($user)->post(route('writeups.store'), $this->dataWriteup(['title' => 'Kedua', 'content_md' => $konten]));

        $this->assertSame($pertama->id, MediaUpload::query()->findOrFail($media['id'])->writeup_id);
    }
}
