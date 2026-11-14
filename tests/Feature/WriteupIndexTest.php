<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\Writeup;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class WriteupIndexTest extends TestCase
{
    use MembuatDataUji;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaxonomySeeder::class);
        $this->withoutVite();
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatWriteup(User $penulis, string $judul, array $atribut = [], string $kategori = 'web'): Writeup
    {
        $status = $atribut['status'] ?? WriteupStatus::Published;

        return Writeup::create(array_merge([
            'author_id' => $penulis->id,
            'category_id' => Category::where('slug', $kategori)->value('id'),
            'title' => $judul,
            'slug' => str($judul)->slug()->toString(),
            'content_md' => "## Ringkasan\n\nIsi **tebal** dan ![gambar](/x.png) [tautan](https://contoh.test) untuk {$judul}.",
            'difficulty' => WriteupDifficulty::Easy,
            'status' => $status,
            'published_at' => $status === WriteupStatus::Published ? now()->subDay() : null,
        ], $atribut));
    }

    public function test_hanya_write_up_published_yang_tampil(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Judul Terbit');
        $this->buatWriteup($penulis, 'Judul Rahasia Draft', ['status' => WriteupStatus::Draft]);
        $this->buatWriteup($penulis, 'Judul Terjadwal', ['published_at' => now()->addDay()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Judul Terbit')
            ->assertDontSee('Judul Rahasia Draft')
            ->assertDontSee('Judul Terjadwal')
            ->assertSee('1 write-up');
    }

    public function test_draft_milik_user_login_tidak_bocor_ke_tab_semua(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Draft Pribadi', ['status' => WriteupStatus::Draft]);

        $this->actingAs($penulis)->get('/')
            ->assertOk()
            ->assertDontSee('Draft Pribadi')
            ->assertSee('0 write-up');
    }

    public function test_dashboard_menampilkan_data_kartu_dengan_benar(): void
    {
        $penulis = $this->buatUser(username: 'suki');
        $writeup = $this->buatWriteup($penulis, 'Bypass Login', [
            'difficulty' => WriteupDifficulty::Insane,
            'published_at' => '2026-11-13 10:00:00',
        ]);
        $writeup->tags()->sync([Tag::create(['name' => 'SQLi', 'slug' => 'sqli'])->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('13 Nov 2026')
            ->assertSee('suki')
            ->assertSee('#SQLi')
            ->assertSee('Insane')
            ->assertSee('Isi tebal dan tautan untuk Bypass Login.')
            ->assertDontSee('**tebal**', false)
            ->assertDontSee('/x.png', false);
    }

    public function test_filter_kategori(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Tulisan Web', [], 'web');
        $this->buatWriteup($penulis, 'Tulisan Crypto', [], 'crypto');

        $this->get('/?kategori=crypto')
            ->assertSee('Tulisan Crypto')
            ->assertDontSee('Tulisan Web')
            ->assertSee('1 write-up');

        // Nilai kosong atau slug tidak dikenal = Semua.
        $this->get('/?kategori=')->assertSee('2 write-up');
        $this->get('/?kategori=tidak-ada')->assertSee('2 write-up');
    }

    public function test_filter_level_dan_nilai_tidak_valid_diabaikan(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Tulisan Mudah', ['difficulty' => WriteupDifficulty::Easy]);
        $this->buatWriteup($penulis, 'Tulisan Gila', ['difficulty' => WriteupDifficulty::Insane]);

        $this->get('/?level=insane')
            ->assertSee('Tulisan Gila')
            ->assertDontSee('Tulisan Mudah')
            ->assertSee('1 write-up');

        $this->get('/?level=bukan-level')->assertOk()->assertSee('2 write-up');
        $this->get('/?level[]=insane&q[]=x&kategori[]=web&tab[]=saya')->assertOk()->assertSee('2 write-up');
    }

    public function test_pencarian_judul_dan_nama_tag(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Eksploitasi Heap');
        $berTag = $this->buatWriteup($penulis, 'Tulisan Lain');
        $berTag->tags()->sync([Tag::create(['name' => 'Ret2libc', 'slug' => 'ret2libc'])->id]);

        $this->get('/?q=heap')->assertSee('Eksploitasi Heap')->assertDontSee('Tulisan Lain')->assertSee('1 write-up');
        $this->get('/?q=ret2')->assertSee('Tulisan Lain')->assertDontSee('Eksploitasi Heap')->assertSee('1 write-up');
        $this->get('/?q=tidak-ketemu')->assertSee('0 write-up')->assertSee('Tidak ada write-up yang cocok');
    }

    public function test_wildcard_like_pada_pencarian_diperlakukan_sebagai_teks_biasa(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Tulisan Biasa');

        $this->get('/?q=%25')->assertOk()->assertSee('0 write-up');
        $this->get('/?q=_')->assertOk()->assertSee('0 write-up');
    }

    public function test_filter_digabung(): void
    {
        $penulis = $this->buatUser();
        $this->buatWriteup($penulis, 'Web Mudah', ['difficulty' => WriteupDifficulty::Easy], 'web');
        $this->buatWriteup($penulis, 'Web Sulit', ['difficulty' => WriteupDifficulty::Hard], 'web');
        $this->buatWriteup($penulis, 'Pwn Sulit', ['difficulty' => WriteupDifficulty::Hard], 'pwn');

        $this->get('/?kategori=web&level=hard&q=sulit')
            ->assertSee('Web Sulit')
            ->assertDontSee('Web Mudah')
            ->assertDontSee('Pwn Sulit')
            ->assertSee('1 write-up');
    }

    public function test_paginasi_12_per_halaman_dan_hitungan_total(): void
    {
        $penulis = $this->buatUser();

        foreach (range(1, 14) as $i) {
            $this->buatWriteup($penulis, sprintf('Kasus-%02d', $i), ['published_at' => now()->subDays($i)]);
        }

        $this->get('/')
            ->assertSee('14 write-up')
            ->assertSee('Kasus-01')
            ->assertSee('Kasus-12')
            ->assertDontSee('Kasus-13')
            ->assertDontSee('Kasus-14');

        $this->get('/?page=2')
            ->assertSee('14 write-up')
            ->assertSee('Kasus-13')
            ->assertSee('Kasus-14')
            ->assertDontSee('Kasus-01');
    }

    public function test_tab_saya_menolak_tamu(): void
    {
        $this->get('/?tab=saya')->assertRedirect(route('login'));
    }

    public function test_tab_saya_lama_diarahkan_ke_halaman_postingan_saya(): void
    {
        $this->actingAs($this->buatUser())->get('/?tab=saya')
            ->assertRedirect(route('writeups.mine'));
    }

    public function test_dashboard_tidak_lagi_menampilkan_draft_atau_milik_sendiri_sebagai_tab(): void
    {
        $saya = $this->buatUser(username: 'saya');
        $this->buatWriteup($saya, 'Punya Saya Draft', ['status' => WriteupStatus::Draft]);

        $this->actingAs($saya)->get('/')
            ->assertOk()
            ->assertDontSee('Punya Saya Draft')
            ->assertDontSee('tab=saya', false);
    }

    public function test_halaman_postingan_saya_menampilkan_semua_status_milik_sendiri_saja(): void
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
            ->assertDontSee('Draft Orang Lain');
    }

    public function test_halaman_postingan_saya_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->buatUser())->get(route('writeups.mine'))
            ->assertOk()
            ->assertSee('Belum ada write-up yang kamu publikasikan.')
            ->assertSee('Buat Write-Up Pertama');
    }

    public function test_banner_flash_menampilkan_tautan_postingan_saya_untuk_user_login(): void
    {
        $this->actingAs($this->buatUser())
            ->withSession(['status' => 'Write-up berhasil dipublikasikan.'])
            ->get('/')
            ->assertSee('Write-up berhasil dipublikasikan.')
            ->assertSee('Lihat postingan saya')
            ->assertSee(route('writeups.mine'));
    }

    public function test_banner_flash_untuk_tamu_tanpa_tautan_postingan_saya(): void
    {
        $this->withSession(['status' => 'Halo'])->get('/')
            ->assertSee('Halo')
            ->assertDontSee('Lihat postingan saya');
    }

    public function test_header_tamu_dan_user_login(): void
    {
        $this->get('/')->assertSee('Login')->assertDontSee('Logout');

        $this->actingAs($this->buatUser(username: 'suki'))->get('/')
            ->assertSee('suki')
            ->assertSee('Logout')
            ->assertSee('+ Buat Write-Up');
    }

    public function test_chip_dari_database_dan_rute_lama_tetap_berfungsi(): void
    {
        $this->get('/')
            ->assertSee('Reverse') // kategori dari tabel categories
            ->assertSee('Insane'); // dari WriteupDifficulty::cases()

        $this->get('/dashboard')->assertRedirect('/');
        $this->assertSame(url('/'), route('dashboard'));
    }

    public function test_tidak_ada_n_plus_1(): void
    {
        $penulis = $this->buatUser();
        $tag = Tag::firstOrCreate(['slug' => 'xss'], ['name' => 'XSS']);

        foreach (range(1, 8) as $i) {
            $this->buatWriteup($penulis, "Tulisan {$i}")->tags()->sync([$tag->id]);
        }

        \DB::enableQueryLog();
        $this->get('/')->assertOk();
        $jumlah = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Jumlah query tetap, tidak bergantung pada banyaknya kartu.
        $this->assertLessThanOrEqual(10, $jumlah);
    }
}
