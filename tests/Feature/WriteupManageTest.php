<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use App\Models\Writeup;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class WriteupManageTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatWriteup(User $penulis, string $judul, array $atribut = []): Writeup
    {
        $status = $atribut['status'] ?? WriteupStatus::Published;

        return Writeup::create(array_merge([
            'author_id' => $penulis->id,
            'category_id' => Category::where('slug', 'web')->value('id'),
            'title' => $judul,
            'slug' => str($judul)->slug()->toString(),
            'content_md' => "## Ringkasan\n\nIsi awal untuk {$judul}.",
            'difficulty' => WriteupDifficulty::Easy,
            'status' => $status,
            'published_at' => $status === WriteupStatus::Published ? now()->subDay() : null,
        ], $atribut));
    }

    private function pilihanTercentang(string $html, string $nama, int $id): bool
    {
        return preg_match('/name="'.$nama.'\[\]" value="'.$id.'"(?:(?!<span).)*?\schecked(?=[\s>])/s', $html) === 1;
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function dataUpdate(array $override = []): array
    {
        return $this->dataWriteup(array_merge([
            'title' => 'Judul Sesudah Diubah',
            'status' => 'published',
        ], $override));
    }

    public function test_tamu_ditolak_di_semua_rute_kelola(): void
    {
        $writeup = $this->buatWriteup($this->buatUser(), 'Punya Penulis');

        $this->get(route('writeups.mine'))->assertRedirect(route('login'));
        $this->get(route('writeups.edit', $writeup))->assertRedirect(route('login'));
        $this->put(route('writeups.update', $writeup), $this->dataUpdate())->assertRedirect(route('login'));
        $this->delete(route('writeups.destroy', $writeup), ['password' => 'password'])->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($writeup);
        $this->assertSame('Punya Penulis', $writeup->fresh()->title);
    }

    public function test_mine_hanya_menampilkan_milik_sendiri_termasuk_draft(): void
    {
        $saya = $this->buatUser(username: 'saya');
        $lain = $this->buatUser(username: 'lain');
        $this->buatWriteup($saya, 'Punya Saya Terbit');
        $this->buatWriteup($saya, 'Punya Saya Draft', ['status' => WriteupStatus::Draft]);
        $this->buatWriteup($lain, 'Punya Orang Lain');
        $this->buatWriteup($lain, 'Draft Orang Lain', ['status' => WriteupStatus::Draft]);

        $this->actingAs($saya)->get(route('writeups.mine'))
            ->assertOk()
            ->assertSee('Postingan Saya')
            ->assertSee('Punya Saya Terbit')
            ->assertSee('Punya Saya Draft')
            ->assertSee('Draft</span>', false)
            ->assertDontSee('Punya Orang Lain')
            ->assertDontSee('Draft Orang Lain')
            ->assertDontSee('Isi awal untuk')
            ->assertSee('Update')
            ->assertSee('Delete');
    }

    public function test_mine_urut_menurut_tanggal_tampil_dan_tidak_ada_n_plus_1(): void
    {
        $saya = $this->buatUser();
        $tag = Tag::firstOrCreate(['slug' => 'xss'], ['name' => 'XSS']);

        $hitungQuery = function () use ($saya): int {
            DB::enableQueryLog();
            $this->actingAs($saya)->get(route('writeups.mine'))->assertOk();
            $jumlah = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::disableQueryLog();

            return $jumlah;
        };

        $this->buatWriteup($saya, 'Tulisan Terlama Sekali', ['published_at' => now()->subDays(10)])->tags()->sync([$tag->id]);
        $this->buatWriteup($saya, 'Tulisan Terbaru Sekali', ['published_at' => now()->subDay()])->tags()->sync([$tag->id]);

        $this->actingAs($saya)->get(route('writeups.mine'))->assertSeeInOrder(['Tulisan Terbaru Sekali', 'Tulisan Terlama Sekali']);

        $sedikit = $hitungQuery();

        foreach (range(1, 6) as $i) {
            $this->buatWriteup($saya, "Tambahan {$i}")->tags()->sync([$tag->id]);
        }

        $this->assertSame($sedikit, $hitungQuery());
    }

    public function test_mine_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->buatUser())->get(route('writeups.mine'))
            ->assertOk()
            ->assertSee('Belum ada write-up yang kamu publikasikan.')
            ->assertSee('Buat Write-Up Pertama')
            ->assertSee(route('writeups.create'));
    }

    public function test_header_postingan_saya_aktif_hanya_di_halamannya(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->get(route('writeups.mine'))
            ->assertSee('aria-current="page"', false)
            ->assertSee('border-accent bg-accent/10 text-accent', false);

        $this->actingAs($user)->get('/')->assertDontSee('aria-current="page"', false);
    }

    public function test_pemilik_bisa_membuka_edit_dan_form_terisi(): void
    {
        $saya = $this->buatUser();
        $tag = Tag::where('slug', 'xss')->first();
        $tool = Tool::where('slug', 'nmap')->first();
        $writeup = $this->buatWriteup($saya, 'Judul Awal Edit', [
            'category_id' => Category::where('slug', 'pwn')->value('id'),
            'difficulty' => WriteupDifficulty::Hard,
            'content_md' => "## Isi\n\nKonten markdown awal.",
        ]);
        $writeup->tags()->sync([$tag->id]);
        $writeup->tools()->sync([$tool->id]);

        $html = $this->actingAs($saya)->get(route('writeups.edit', $writeup))
            ->assertOk()
            ->assertSee('Edit write-up')
            ->assertSee('Batal')
            ->assertSee(route('writeups.mine'))
            ->assertSee('action="'.route('writeups.update', $writeup).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Judul Awal Edit"', false)
            ->assertSee('Konten markdown awal.')
            ->assertSee('Sisipkan gambar')
            ->assertSee('Simpan perubahan')
            ->assertSee('Jadikan draft')
            ->assertDontSee('Simpan sebagai draft')
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.Category::where('slug', 'pwn')->value('id').'"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/name="difficulty" value="hard"[^>]*checked/', $html);
        $this->assertTrue($this->pilihanTercentang($html, 'tags', $tag->id));
        $this->assertTrue($this->pilihanTercentang($html, 'tools', $tool->id));

        $this->assertFalse($this->pilihanTercentang($html, 'tags', Tag::where('slug', 'idor')->value('id')));
        $this->assertFalse($this->pilihanTercentang($html, 'tools', Tool::where('slug', 'ffuf')->value('id')));
    }

    public function test_edit_draft_menampilkan_tombol_draft_dan_publikasikan(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Masih Draft', ['status' => WriteupStatus::Draft]);

        $this->actingAs($saya)->get(route('writeups.edit', $writeup))
            ->assertOk()
            ->assertSee('Simpan sebagai draft')
            ->assertSee('Publikasikan')
            ->assertDontSee('Simpan perubahan')
            ->assertDontSee('Jadikan draft');
    }

    public function test_edit_write_up_yang_dihapus_menghasilkan_404(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Sudah Dihapus');
        $writeup->delete();

        $this->actingAs($saya)->get(route('writeups.edit', $writeup))->assertNotFound();
    }

    public function test_user_lain_mendapat_403_di_edit_update_dan_destroy(): void
    {
        $writeup = $this->buatWriteup($this->buatUser(username: 'pemilik'), 'Bukan Milikmu');
        $lain = $this->buatUser(username: 'lain');

        $this->actingAs($lain)->get(route('writeups.edit', $writeup))->assertForbidden();
        $this->actingAs($lain)->put(route('writeups.update', $writeup), $this->dataUpdate())->assertForbidden();
        $this->actingAs($lain)->delete(route('writeups.destroy', $writeup), ['password' => 'password'])->assertForbidden();

        $this->assertNotSoftDeleted($writeup);
        $this->assertSame('Bukan Milikmu', $writeup->fresh()->title);
    }

    public function test_user_banned_mendapat_403_walau_pemilik(): void
    {
        $banned = $this->buatUser(UserStatus::Banned, 'terblokir');
        $writeup = $this->buatWriteup($banned, 'Milik Terblokir');

        $this->actingAs($banned)->get(route('writeups.edit', $writeup))->assertForbidden();
        $this->actingAs($banned)->put(route('writeups.update', $writeup), $this->dataUpdate())->assertForbidden();
        $this->actingAs($banned)->delete(route('writeups.destroy', $writeup), ['password' => 'password'])->assertForbidden();

        $this->assertNotSoftDeleted($writeup);
        $this->assertSame('Milik Terblokir', $writeup->fresh()->title);
    }

    public function test_update_valid_mengubah_field_sync_tag_dan_tool_serta_slug_tetap(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Judul Sebelum Diubah');
        $slugAwal = $writeup->slug;
        $writeup->tags()->sync([Tag::where('slug', 'xss')->value('id'), Tag::where('slug', 'idor')->value('id')]);
        $writeup->tools()->sync([Tool::where('slug', 'nmap')->value('id')]);

        $tagBaru = Tag::where('slug', 'ssrf')->value('id');
        $toolBaru = [Tool::where('slug', 'ffuf')->value('id'), Tool::where('slug', 'python')->value('id')];
        $kategoriBaru = Category::where('slug', 'crypto')->value('id');

        $this->actingAs($saya)
            ->put(route('writeups.update', $writeup), $this->dataUpdate([
                'category_id' => $kategoriBaru,
                'difficulty' => 'insane',
                'content_md' => 'Isi baru yang lengkap.',
                'tags' => [$tagBaru],
                'tools' => $toolBaru,
            ]))
            ->assertRedirect(route('writeups.mine'))
            ->assertSessionHas('status', 'Write-up berhasil diperbarui.');

        $baru = $writeup->fresh();
        $this->assertSame('Judul Sesudah Diubah', $baru->title);
        $this->assertSame($slugAwal, $baru->slug);
        $this->assertSame($kategoriBaru, $baru->category_id);
        $this->assertSame(WriteupDifficulty::Insane, $baru->difficulty);
        $this->assertSame('Isi baru yang lengkap.', $baru->content_md);
        $this->assertSame([$tagBaru], $baru->tags()->pluck('tags.id')->all());
        $this->assertEqualsCanonicalizing($toolBaru, $baru->tools()->pluck('tools.id')->all());
    }

    public function test_update_tanpa_tag_dan_tool_mengosongkan_relasi(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Punya Relasi');
        $writeup->tags()->sync([Tag::where('slug', 'xss')->value('id')]);
        $writeup->tools()->sync([Tool::where('slug', 'nmap')->value('id')]);

        $this->actingAs($saya)->put(route('writeups.update', $writeup), $this->dataUpdate())
            ->assertRedirect(route('writeups.mine'));

        $this->assertSame(0, $writeup->fresh()->tags()->count());
        $this->assertSame(0, $writeup->fresh()->tools()->count());
    }

    public function test_update_tidak_valid_ditolak_dan_data_tidak_berubah(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Judul Di Database');
        $writeup->tags()->sync([Tag::where('slug', 'xss')->value('id')]);
        $edit = route('writeups.edit', $writeup);

        $this->actingAs($saya)->from($edit)
            ->put(route('writeups.update', $writeup), $this->dataUpdate([
                'category_id' => 999999,
                'content_md' => '',
            ]))
            ->assertRedirect($edit)
            ->assertSessionHasErrors(['category_id', 'content_md']);

        $this->assertSame('Judul Di Database', $writeup->fresh()->title);
        $this->assertSame(1, $writeup->fresh()->tags()->count());
    }

    public function test_update_tidak_valid_menampilkan_error_dan_old_mengalahkan_database(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Judul Di Database');
        $writeup->tags()->sync([Tag::where('slug', 'xss')->value('id')]);
        $edit = route('writeups.edit', $writeup);

        $html = $this->actingAs($saya)->from($edit)->followingRedirects()
            ->put(route('writeups.update', $writeup), $this->dataUpdate([
                'title' => 'Judul Dari Input Gagal',
                'category_id' => 999999,
                'content_md' => '',
                'tags' => [],
            ]))
            ->assertOk()
            ->assertSee('Kategori tidak valid.')
            ->assertSee('Isi write-up wajib diisi.')
            ->assertSee('value="Judul Dari Input Gagal"', false)
            ->assertDontSee('value="Judul Di Database"', false)
            ->getContent();

        $this->assertFalse($this->pilihanTercentang($html, 'tags', Tag::where('slug', 'xss')->value('id')));
    }

    public function test_update_tidak_bisa_mengubah_slug_atau_penulis_lewat_input(): void
    {
        $saya = $this->buatUser();
        $lain = $this->buatUser(username: 'lain');
        $writeup = $this->buatWriteup($saya, 'Slug Harus Tetap');

        $this->actingAs($saya)->put(route('writeups.update', $writeup), $this->dataUpdate([
            'slug' => 'slug-curian',
            'author_id' => $lain->id,
        ]))->assertRedirect(route('writeups.mine'));

        $baru = $writeup->fresh();
        $this->assertSame('slug-harus-tetap', $baru->slug);
        $this->assertSame($saya->id, $baru->author_id);
    }

    public function test_draft_ke_published_mengisi_published_at(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Draft Mau Terbit', ['status' => WriteupStatus::Draft]);
        $this->assertNull($writeup->published_at);

        $this->actingAs($saya)->put(route('writeups.update', $writeup), $this->dataUpdate(['status' => 'published']))
            ->assertRedirect(route('writeups.mine'));

        $baru = $writeup->fresh();
        $this->assertSame(WriteupStatus::Published, $baru->status);
        $this->assertNotNull($baru->published_at);
        $this->assertTrue($baru->published_at->between(now()->subMinute(), now()->addMinute()));
        $this->assertTrue(Writeup::published()->whereKey($baru->id)->exists());
    }

    public function test_published_ke_published_mempertahankan_published_at(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Sudah Terbit', ['published_at' => '2026-01-02 10:00:00']);

        $this->actingAs($saya)->put(route('writeups.update', $writeup), $this->dataUpdate(['status' => 'published']))
            ->assertRedirect(route('writeups.mine'));

        $this->assertSame('2026-01-02 10:00:00', $writeup->fresh()->published_at->format('Y-m-d H:i:s'));
    }

    public function test_published_ke_draft_mengosongkan_published_at(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Mau Jadi Draft');

        $this->actingAs($saya)->put(route('writeups.update', $writeup), $this->dataUpdate(['status' => 'draft']))
            ->assertRedirect(route('writeups.mine'));

        $baru = $writeup->fresh();
        $this->assertSame(WriteupStatus::Draft, $baru->status);
        $this->assertNull($baru->published_at);
        $this->assertFalse(Writeup::published()->whereKey($baru->id)->exists());
    }

    public function test_destroy_tanpa_password_gagal_dan_data_masih_ada(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Jangan Dihapus');
        $mine = route('writeups.mine');

        $this->actingAs($saya)->from($mine)->delete(route('writeups.destroy', $writeup))
            ->assertRedirect($mine)
            ->assertSessionHasErrors(['password'], errorBag: 'writeupDeletion')
            ->assertSessionHas('hapus_slug', $writeup->slug);

        $this->assertNotSoftDeleted($writeup);
    }

    public function test_destroy_dengan_password_salah_gagal(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Tetap Aman');
        $mine = route('writeups.mine');

        $this->actingAs($saya)->from($mine)
            ->delete(route('writeups.destroy', $writeup), ['password' => 'password-salah'])
            ->assertRedirect($mine)
            ->assertSessionHasErrors(['password' => 'Password salah.'], errorBag: 'writeupDeletion');

        $this->assertNotSoftDeleted($writeup);
    }

    public function test_setelah_password_salah_modal_terbuka_lagi_untuk_write_up_yang_sama(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Tetap Aman');
        $lain = $this->buatWriteup($saya, 'Tulisan Lain Lagi');

        $this->actingAs($saya)->from(route('writeups.mine'))->followingRedirects()
            ->delete(route('writeups.destroy', $writeup), ['password' => 'password-salah'])
            ->assertOk()
            ->assertSee('\u0022show\u0022:true', false)
            ->assertSee('action="'.route('writeups.destroy', $writeup).'"', false)
            ->assertDontSee('action="'.route('writeups.destroy', $lain).'"', false)
            ->assertSee('Password salah.');

        $this->assertNotSoftDeleted($writeup);
        $this->assertNotSoftDeleted($lain);
    }

    public function test_modal_hapus_tertutup_secara_default_dan_hanya_satu_untuk_seluruh_halaman(): void
    {
        $saya = $this->buatUser();
        $this->buatWriteup($saya, 'Satu');
        $this->buatWriteup($saya, 'Dua');
        $this->buatWriteup($saya, 'Tiga');

        $html = $this->actingAs($saya)->get(route('writeups.mine'))->assertOk()
            ->assertSee('\u0022show\u0022:false', false)
            ->assertDontSee('Password salah.')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Hapus Write-Up?'));
        $this->assertSame(3, substr_count($html, 'aria-haspopup="true"'));
    }

    public function test_destroy_dengan_password_benar_soft_delete_dan_hilang_dari_dashboard(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Akan Dihapus');
        $this->get('/')->assertSee('Akan Dihapus');

        $this->actingAs($saya)->delete(route('writeups.destroy', $writeup), ['password' => 'password'])
            ->assertRedirect(route('writeups.mine'))
            ->assertSessionHas('status', 'Write-up berhasil dihapus.');

        $this->assertSoftDeleted($writeup);
        $this->assertTrue(Writeup::withTrashed()->where('slug', $writeup->slug)->exists());

        $this->get('/')->assertDontSee('Akan Dihapus');
        $this->actingAs($saya)->get(route('writeups.mine'))->assertDontSee('Akan Dihapus');
    }

    public function test_destroy_dibatasi_lima_percobaan_per_menit(): void
    {
        $saya = $this->buatUser();
        $writeup = $this->buatWriteup($saya, 'Dilindungi Throttle');

        foreach (range(1, 5) as $i) {
            $this->actingAs($saya)->delete(route('writeups.destroy', $writeup), ['password' => 'salah'])
                ->assertSessionHasErrors(['password'], errorBag: 'writeupDeletion');
        }

        $this->actingAs($saya)->delete(route('writeups.destroy', $writeup), ['password' => 'password'])
            ->assertStatus(429);

        $this->assertNotSoftDeleted($writeup);
    }
}
