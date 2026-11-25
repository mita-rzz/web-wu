<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\MediaUpload;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use App\Models\Writeup;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class WriteupShowTest extends TestCase
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
            'content_md' => "## Ringkasan\n\nIsi **tebal** untuk {$judul}.",
            'difficulty' => WriteupDifficulty::Easy,
            'status' => $status,
            'published_at' => $status === WriteupStatus::Published ? now()->subDay() : null,
        ], $atribut));
    }

    private function buatTool(string $nama): Tool
    {
        return Tool::firstOrCreate(['slug' => str($nama)->slug()->toString()], ['name' => $nama]);
    }

    private function buatTag(string $nama): Tag
    {
        return Tag::firstOrCreate(['slug' => str($nama)->slug()->toString()], ['name' => $nama]);
    }

    private function buatMedia(User $penulis, Writeup $writeup, string $path, string $namaAsli): MediaUpload
    {
        return MediaUpload::create([
            'uploader_id' => $penulis->id,
            'writeup_id' => $writeup->id,
            'original_name' => $namaAsli,
            'stored_name' => basename($path),
            'file_path' => $path,
            'file_ext' => 'png',
            'mime_type' => 'image/png',
            'file_size' => 100,
        ]);
    }

    private function badanHtml(string $html): string
    {
        preg_match('#<div class="prose-solvelog mt-8">(.*)</div>\s*</article>#s', $html, $cocok);

        return $cocok[1] ?? '';
    }

    public function test_write_up_published_bisa_dibuka_tamu(): void
    {
        $penulis = $this->buatUser(username: 'suki');
        $writeup = $this->buatWriteup($penulis, 'Analisis PCAP Eksfiltrasi DNS', [
            'difficulty' => WriteupDifficulty::Medium,
            'published_at' => '2026-11-13 10:00:00',
            'content_md' => "## Observasi Awal\n\nLonjakan permintaan **TXT** menuju domain eksternal.",
        ], 'forensics');
        $writeup->tools()->sync([$this->buatTool('Wireshark')->id, $this->buatTool('tshark')->id]);
        $writeup->tags()->sync([$this->buatTag('IDOR')->id]);

        $this->get(route('writeups.show', $writeup))
            ->assertOk()
            ->assertSee('Analisis PCAP Eksfiltrasi DNS - SolveLog', false)
            ->assertSee('Forensics')
            ->assertSee('>medium</span>', false)
            ->assertSee('suki')
            ->assertSee('Dipublikasikan pada 13 Nov 2026')
            ->assertSee('Wireshark')
            ->assertSee('tshark')
            ->assertSee('#IDOR')
            ->assertSee('Observasi Awal')
            ->assertSee('<strong>TXT</strong>', false)
            ->assertDontSee('Draft</span>', false)
            ->assertSee(route('dashboard'), false);
    }

    public function test_tools_dan_tags_diurutkan_menurut_nama(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Urutan Taksonomi');
        $writeup->tools()->sync([$this->buatTool('Zeta')->id, $this->buatTool('Alfa')->id]);
        $writeup->tags()->sync([$this->buatTag('zz-tag')->id, $this->buatTag('aa-tag')->id]);

        $this->get(route('writeups.show', $writeup))
            ->assertSeeInOrder(['>Alfa<', '>Zeta<'], false)
            ->assertSeeInOrder(['#aa-tag', '#zz-tag']);
    }

    public function test_draft_404_untuk_tamu_dan_user_lain(): void
    {
        $penulis = $this->buatUser(username: 'penulis');
        $lain = $this->buatUser(username: 'lain');
        $draft = $this->buatWriteup($penulis, 'Draft Rahasia', ['status' => WriteupStatus::Draft]);

        $this->get(route('writeups.show', $draft))->assertNotFound();
        $this->actingAs($lain)->get(route('writeups.show', $draft))->assertNotFound();
    }

    public function test_draft_200_untuk_penulis_dengan_badge_draft_dan_belum_dipublikasikan(): void
    {
        $penulis = $this->buatUser(username: 'penulis');
        $draft = $this->buatWriteup($penulis, 'Draft Saya', ['status' => WriteupStatus::Draft]);

        $this->actingAs($penulis)->get(route('writeups.show', $draft))
            ->assertOk()
            ->assertSee('Draft Saya')
            ->assertSee('Draft</span>', false)
            ->assertSee('Belum dipublikasikan')
            ->assertDontSee('Dipublikasikan pada');
    }

    public function test_slug_tidak_ada_soft_delete_dan_terjadwal_404_untuk_tamu(): void
    {
        $penulis = $this->buatUser();
        $dihapus = $this->buatWriteup($penulis, 'Sudah Dihapus');
        $terjadwal = $this->buatWriteup($penulis, 'Belum Waktunya', ['published_at' => now()->addDay()]);
        $dihapus->delete();

        $this->get('/writeups/slug-tidak-ada')->assertNotFound();
        $this->get(route('writeups.show', $dihapus))->assertNotFound();
        $this->get(route('writeups.show', $terjadwal))->assertNotFound();
    }

    public function test_soft_delete_tetap_404_bahkan_untuk_penulis(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Dihapus Penulis');
        $writeup->delete();

        $this->actingAs($penulis)->get('/writeups/dihapus-penulis')->assertNotFound();
    }

    public function test_script_dan_html_mentah_di_markdown_tidak_lolos(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji XSS', [
            'content_md' => "Awal <script>alert('xss')</script> akhir\n\n<div onclick=\"x()\">blok mentah</div>\n\n<img src=x onerror=alert(1)>",
        ]);

        $respons = $this->get(route('writeups.show', $writeup))->assertOk();
        $badan = $this->badanHtml($respons->getContent());

        $this->assertStringNotContainsString('<script', $badan);
        $this->assertStringNotContainsString('<div onclick', $badan);
        $this->assertStringNotContainsString('onerror', $badan);
        $this->assertStringNotContainsString('<div', $badan);
        $this->assertStringContainsString('Awal', $badan);
    }

    public function test_judul_dan_nama_taksonomi_di_header_di_escape(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, '<b>Judul</b> & "Tebal"');
        $writeup->tags()->sync([$this->buatTag('<i>tag</i>')->id]);

        $this->get(route('writeups.show', $writeup))
            ->assertOk()
            ->assertDontSee('<b>Judul</b>', false)
            ->assertDontSee('<i>tag</i>', false)
            ->assertSee('&lt;b&gt;Judul&lt;/b&gt;', false);
    }

    public function test_karakter_utf8_di_badan_tidak_rusak(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji UTF-8', [
            'content_md' => "Café “kutipan” — 日本語 🔐 `kode é` dan ![gambar é](/x.png)\n\nTeks akhir ñ.",
        ]);

        $respons = $this->get(route('writeups.show', $writeup))->assertOk();
        $badan = $this->badanHtml($respons->getContent());

        $this->assertStringContainsString('Café “kutipan” — 日本語 🔐', $badan);
        $this->assertStringContainsString('<code>kode é</code>', $badan);
        $this->assertStringContainsString('Teks akhir ñ.', $badan);
        $respons->assertSee('alt="gambar é"', false);
    }

    public function test_tautan_javascript_tidak_aktif(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji Tautan', [
            'content_md' => '[klik](javascript:alert(1)) dan [vbs](vbscript:x) dan [data](data:text/html;base64,PHNjcmlwdD4=)',
        ]);

        $respons = $this->get(route('writeups.show', $writeup))->assertOk();
        $badan = $this->badanHtml($respons->getContent());

        $this->assertStringNotContainsString('javascript:', $badan);
        $this->assertStringNotContainsString('vbscript:', $badan);
        $this->assertStringNotContainsString('data:text/html', $badan);
        $this->assertStringContainsString('klik', $badan);
    }

    public function test_tautan_eksternal_dibuka_di_tab_baru_dengan_rel_aman(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji Tautan Luar', [
            'content_md' => '[luar](https://contoh.test/halaman) dan [dalam](/writeups/lain) dan [jangkar](#bagian)',
        ]);

        $badan = $this->badanHtml($this->get(route('writeups.show', $writeup))->assertOk()->getContent());

        $this->assertMatchesRegularExpression('#<a href="https://contoh\.test/halaman"[^>]*target="_blank"[^>]*>#', $badan);
        $this->assertMatchesRegularExpression('#<a href="https://contoh\.test/halaman"[^>]*rel="noopener noreferrer"[^>]*>#', $badan);
        $this->assertStringContainsString('<a href="/writeups/lain">', $badan);
        $this->assertStringContainsString('<a href="#bagian">', $badan);
    }

    public function test_gambar_markdown_pindah_ke_galeri_dan_tidak_ada_di_badan(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji Galeri', [
            'content_md' => "Paragraf awal.\n\n![Pola query DNS](/storage/writeups/2026/11/abc.png)\n\n![Foto luar](https://contoh.test/a.png)\n\nParagraf akhir.",
        ]);
        $this->buatMedia($penulis, $writeup, 'writeups/2026/11/abc.png', 'dns-query-pattern.png');

        $respons = $this->get(route('writeups.show', $writeup))->assertOk();
        $html = $respons->getContent();

        $respons->assertSee('<figure', false)
            ->assertSee('src="/storage/writeups/2026/11/abc.png"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Pola query DNS')
            ->assertSee('dns-query-pattern.png')
            ->assertSee('src="https://contoh.test/a.png"', false)
            ->assertSee('Foto luar');

        $this->assertSame(2, substr_count($html, '<img'));

        $badan = $this->badanHtml($html);
        $this->assertStringNotContainsString('<img', $badan);
        $this->assertStringContainsString('Paragraf awal.', $badan);
        $this->assertStringContainsString('Paragraf akhir.', $badan);
        $this->assertSame(2, substr_count($badan, '<p>'));
        $this->assertStringNotContainsString('<p></p>', $badan);
    }

    public function test_gambar_dengan_src_tidak_aman_dibuang(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Uji Src Gambar', [
            'content_md' => "![a](ftp://contoh.test/a.png)\n\n![b](//contoh.test/b.png)\n\n![c](javascript:alert(1))\n\n![d](relatif/e.png)\n\nTeks.",
        ]);

        $respons = $this->get(route('writeups.show', $writeup))->assertOk();

        $this->assertSame(0, substr_count($respons->getContent(), '<img'));
        $respons->assertDontSee('<figure', false)
            ->assertDontSee('ftp://', false)
            ->assertDontSee('//contoh.test/b.png', false)
            ->assertDontSee('relatif/e.png', false);
    }

    public function test_tanpa_tools_dan_tags_blok_taksonomi_tidak_muncul(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Tanpa Taksonomi');

        $this->get(route('writeups.show', $writeup))
            ->assertOk()
            ->assertDontSee('Tools</span>', false)
            ->assertDontSee('Tags</span>', false)
            ->assertDontSee('border-borderMain/60', false);
    }

    public function test_hanya_tools_atau_hanya_tags_menampilkan_bagian_masing_masing(): void
    {
        $penulis = $this->buatUser();
        $hanyaTools = $this->buatWriteup($penulis, 'Hanya Tools');
        $hanyaTools->tools()->sync([$this->buatTool('Burp Suite')->id]);
        $hanyaTags = $this->buatWriteup($penulis, 'Hanya Tags');
        $hanyaTags->tags()->sync([$this->buatTag('SQLi')->id]);

        $this->get(route('writeups.show', $hanyaTools))
            ->assertSee('Tools</span>', false)
            ->assertSee('Burp Suite')
            ->assertDontSee('Tags</span>', false);

        $this->get(route('writeups.show', $hanyaTags))
            ->assertSee('Tags</span>', false)
            ->assertSee('#SQLi')
            ->assertDontSee('Tools</span>', false);
    }

    public function test_kartu_dashboard_mengarah_ke_halaman_detail(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Kartu Bisa Diklik');

        $this->get('/')
            ->assertOk()
            ->assertSee('<a href="'.route('writeups.show', $writeup).'"', false)
            ->assertDontSee('<article tabindex="0"', false);
    }

    public function test_judul_postingan_saya_mengarah_ke_halaman_detail_dan_menu_tidak_di_dalam_tautan(): void
    {
        $penulis = $this->buatUser();
        $writeup = $this->buatWriteup($penulis, 'Judul Bisa Diklik');
        $draft = $this->buatWriteup($penulis, 'Draft Bisa Diklik', ['status' => WriteupStatus::Draft]);

        $respons = $this->actingAs($penulis)->get(route('writeups.mine'))
            ->assertOk()
            ->assertSee(route('writeups.show', $writeup), false)
            ->assertSee(route('writeups.show', $draft), false)
            ->assertSee('Draft</span>', false)
            ->assertSee(route('writeups.edit', $writeup), false);

        $this->assertMatchesRegularExpression(
            '#<h2[^>]*>\s*<a href="'.preg_quote(route('writeups.show', $writeup), '#').'"[^>]*>Judul Bisa Diklik</a>\s*</h2>#',
            $respons->getContent(),
        );
    }

    public function test_draft_milik_penulis_bisa_dibuka_dari_postingan_saya(): void
    {
        $penulis = $this->buatUser();
        $draft = $this->buatWriteup($penulis, 'Draft Dari Daftar', ['status' => WriteupStatus::Draft]);

        $this->actingAs($penulis)->get(route('writeups.mine'))
            ->assertSee(route('writeups.show', $draft), false);

        $this->actingAs($penulis)->get(route('writeups.show', $draft))->assertOk();
    }

    public function test_jumlah_query_konstan_tanpa_n_plus_1(): void
    {
        $penulis = $this->buatUser();

        $kecil = $this->buatWriteup($penulis, 'Kecil', ['content_md' => '![a](/storage/writeups/k0.png)']);
        $kecil->tools()->sync([$this->buatTool('T0')->id]);
        $kecil->tags()->sync([$this->buatTag('g0')->id]);
        $this->buatMedia($penulis, $kecil, 'writeups/k0.png', 'k0.png');

        $isi = '';
        $besar = $this->buatWriteup($penulis, 'Besar');
        foreach (range(1, 8) as $i) {
            $this->buatMedia($penulis, $besar, "writeups/b{$i}.png", "b{$i}.png");
            $isi .= "![gambar {$i}](/storage/writeups/b{$i}.png)\n\n";
        }
        $besar->update(['content_md' => $isi]);
        $besar->tools()->sync(collect(range(1, 8))->map(fn (int $i): int => $this->buatTool("Tool{$i}")->id)->all());
        $besar->tags()->sync(collect(range(1, 8))->map(fn (int $i): int => $this->buatTag("tag{$i}")->id)->all());

        $hitung = function (Writeup $w): int {
            DB::enableQueryLog();
            $this->get(route('writeups.show', $w))->assertOk();
            $n = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::disableQueryLog();

            return $n;
        };

        $nKecil = $hitung($kecil);
        $nBesar = $hitung($besar);

        $this->assertSame($nKecil, $nBesar);
        $this->assertLessThanOrEqual(8, $nBesar);
    }

    public function test_writeups_create_tetap_menuju_form_create(): void
    {
        $this->get('/writeups/create')->assertRedirect(route('login'));

        $this->actingAs($this->buatUser())->get('/writeups/create')
            ->assertOk()
            ->assertViewIs('writeups.create');
    }
}
