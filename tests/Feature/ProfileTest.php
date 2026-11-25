<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Models\Category;
use App\Models\MediaUpload;
use App\Models\Tag;
use App\Models\User;
use App\Models\Writeup;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\MembuatDataUji;
use Tests\TestCase;

class ProfileTest extends TestCase
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
    private function buatWriteup(User $penulis, string $judul, array $atribut = []): Writeup
    {
        $status = $atribut['status'] ?? WriteupStatus::Published;

        return Writeup::create(array_merge([
            'author_id' => $penulis->id,
            'category_id' => Category::where('slug', 'web')->value('id'),
            'title' => $judul,
            'slug' => str($judul)->slug()->toString(),
            'content_md' => 'Isi '.$judul,
            'difficulty' => WriteupDifficulty::Easy,
            'status' => $status,
            'published_at' => $status === WriteupStatus::Published ? now()->subDay() : null,
        ], $atribut));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function dataProfil(User $user, array $ubah = []): array
    {
        return array_merge([
            'username' => $user->username,
            'display_name' => $user->display_name,
            'bio' => $user->bio,
            'email' => $user->email,
        ], $ubah);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function kirimProfil(User $user, array $data): TestResponse
    {
        return $this->actingAs($user)->from('/profile')->patch('/profile', $data);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
        $this->patch('/profile', [])->assertRedirect(route('login'));
        $this->delete('/profile', [])->assertRedirect(route('login'));
        $this->put('/password', [])->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_dengan_data_ringkasan_milik_sendiri(): void
    {
        $saya = $this->buatUser(username: 'suki');
        $saya->forceFill(['bio' => "Baris satu\nBaris dua <b>tebal</b>"])->save();
        $lain = $this->buatUser(username: 'lain');

        $this->buatWriteup($saya, 'Mudah Satu');
        $this->buatWriteup($saya, 'Mudah Dua');
        $this->buatWriteup($saya, 'Sulit Satu', ['difficulty' => WriteupDifficulty::Hard]);
        $this->buatWriteup($saya, 'Draft Satu', ['status' => WriteupStatus::Draft]);
        $this->buatWriteup($saya, 'Terjadwal', ['published_at' => now()->addDay()]);
        $this->buatWriteup($lain, 'Milik Orang Lain');
        $this->buatWriteup($lain, 'Draft Orang Lain', ['status' => WriteupStatus::Draft]);

        $respons = $this->actingAs($saya)->get('/profile')
            ->assertOk()
            ->assertSee('Profil - SolveLog', false)
            ->assertSee('Penulis suki')
            ->assertSee('&#64;suki', false)
            ->assertSee('Baris satu')
            ->assertSee('&lt;b&gt;tebal&lt;/b&gt;', false)
            ->assertDontSee('<b>tebal</b>', false)
            ->assertSee('Bergabung sejak '.now()->locale('id')->translatedFormat('d M Y'))
            ->assertSee('Mudah Satu')
            ->assertSee('Sulit Satu')
            ->assertSee('Write-up terbaru')
            ->assertDontSee('Milik Orang Lain')
            ->assertDontSee('Draft Orang Lain')
            ->assertViewHas('jumlahTerbit', 3)
            ->assertViewHas('jumlahDraft', 1)
            ->assertViewHas('perLevel', ['easy' => 2, 'medium' => 0, 'hard' => 1, 'insane' => 0]);

        $respons->assertDontSee('Draft Satu')->assertDontSee('Terjadwal');
        $respons->assertSee(route('writeups.show', 'mudah-satu'), false);
    }

    public function test_ringkasan_memuat_maksimal_lima_write_up_terbaru(): void
    {
        $saya = $this->buatUser();

        foreach (range(1, 7) as $i) {
            $this->buatWriteup($saya, "Tulisan-{$i}", ['published_at' => now()->subDays(10 - $i)]);
        }

        $this->actingAs($saya)->get('/profile')
            ->assertViewHas('terbaru', fn ($daftar): bool => $daftar->count() === 5
                && $daftar->first()->title === 'Tulisan-7'
                && $daftar->last()->title === 'Tulisan-3');
    }

    public function test_empty_state_untuk_user_tanpa_write_up(): void
    {
        $this->actingAs($this->buatUser())->get('/profile')
            ->assertOk()
            ->assertSee('Belum ada bio.')
            ->assertSee('Belum ada write-up yang kamu publikasikan.')
            ->assertSee(route('writeups.create'), false)
            ->assertSee(route('writeups.mine'), false);
    }

    public function test_tab_awal_ringkasan_dan_pengaturan_setelah_status_atau_error(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->get('/profile')
            ->assertSee("tab: 'ringkasan'", false)
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tabpanel"', false);

        $this->actingAs($user)->withSession(['status' => 'Profil berhasil diperbarui.'])->get('/profile')
            ->assertSee("tab: 'pengaturan'", false);
    }

    public function test_header_memuat_tautan_username_ke_profil_dengan_status_aktif(): void
    {
        $user = $this->buatUser(username: 'suki');

        $pola = '#<a href="'.preg_quote(route('profile.edit'), '#').'"\s+(aria-current="page")?\s*title="Penulis suki"#';

        $this->assertMatchesRegularExpression($pola, $this->actingAs($user)->get('/')->getContent());

        $this->assertSame(0, preg_match_all('#aria-current="page"\s+title="Penulis suki"#', $this->actingAs($user)->get('/')->getContent()));
        preg_match_all('#aria-current="page"\s+title="Penulis suki"#', $this->actingAs($user)->get('/profile')->getContent(), $cocok);
        $this->assertCount(2, $cocok[0]);
    }

    public function test_update_username_nama_bio_dan_email_berhasil(): void
    {
        $user = $this->buatUser();

        $this->kirimProfil($user, $this->dataProfil($user, [
            'username' => '  baru.user_1 ',
            'display_name' => '  Nama Baru ',
            'bio' => "  Bio baru\ndua baris ",
            'email' => ' Baru@Example.COM ',
            'current_password' => 'password',
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $user->refresh();
        $this->assertSame('baru.user_1', $user->username);
        $this->assertSame('Nama Baru', $user->display_name);
        $this->assertSame("Bio baru\ndua baris", $user->bio);
        $this->assertSame('baru@example.com', $user->email);

        $this->actingAs($user)->get('/profile')->assertSee('Profil berhasil diperbarui.');
    }

    public function test_redirect_tidak_menambah_fragment(): void
    {
        $user = $this->buatUser();

        $respons = $this->kirimProfil($user, $this->dataProfil($user, ['display_name' => 'Ganti']));

        $this->assertStringNotContainsString('#', (string) $respons->headers->get('Location'));
    }

    public function test_bio_kosong_disimpan_sebagai_null(): void
    {
        $user = $this->buatUser();
        $user->forceFill(['bio' => 'lama'])->save();

        $this->kirimProfil($user, $this->dataProfil($user, ['bio' => "   \n "]))->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->bio);
    }

    public function test_username_duplikat_ditolak_termasuk_beda_huruf_besar_kecil(): void
    {
        $user = $this->buatUser(username: 'saya');
        $this->buatUser(username: 'Orang');

        $this->kirimProfil($user, $this->dataProfil($user, ['username' => 'Orang']))
            ->assertSessionHasErrors(['username' => 'Username sudah dipakai.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['username' => 'orang']))
            ->assertSessionHasErrors(['username' => 'Username sudah dipakai.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['username' => 'ORANG']))
            ->assertSessionHasErrors('username');

        $this->assertSame('saya', $user->refresh()->username);
    }

    public function test_format_username_tidak_valid_ditolak(): void
    {
        $user = $this->buatUser();

        foreach (['ab', 'pakai spasi', 'tanda@', 'hurufé', str_repeat('a', 51), '<b>x</b>'] as $buruk) {
            $this->kirimProfil($user, $this->dataProfil($user, ['username' => $buruk]))
                ->assertSessionHasErrors('username');
        }

        $this->assertSame('penulis', $user->refresh()->username);

        $this->kirimProfil($user, $this->dataProfil($user, ['username' => 'A-b_c.9']))->assertSessionHasNoErrors();
    }

    public function test_username_milik_sendiri_tidak_dianggap_duplikat(): void
    {
        $user = $this->buatUser(username: 'Suki');
        $user->forceFill(['email' => 'suki@example.com'])->save();

        $this->kirimProfil($user, $this->dataProfil($user))->assertSessionHasNoErrors();
        $this->kirimProfil($user, $this->dataProfil($user, ['username' => 'suki']))->assertSessionHasNoErrors();

        $this->assertSame('suki', $user->refresh()->username);
    }

    public function test_validasi_nama_bio_dan_email_dengan_pesan_indonesia(): void
    {
        $user = $this->buatUser();
        $lain = $this->buatUser(username: 'lain');

        $this->kirimProfil($user, $this->dataProfil($user, ['display_name' => '']))
            ->assertSessionHasErrors(['display_name' => 'Nama tampilan wajib diisi.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['display_name' => str_repeat('x', 101)]))
            ->assertSessionHasErrors(['display_name' => 'Nama tampilan maksimal 100 karakter.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['bio' => str_repeat('x', 501)]))
            ->assertSessionHasErrors(['bio' => 'Bio maksimal 500 karakter.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['email' => 'bukan-email', 'current_password' => 'password']))
            ->assertSessionHasErrors(['email' => 'Format email tidak valid.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['email' => $lain->email, 'current_password' => 'password']))
            ->assertSessionHasErrors(['email' => 'Email sudah dipakai.']);
    }

    public function test_ganti_email_tanpa_atau_dengan_password_salah_ditolak(): void
    {
        $user = $this->buatUser();

        $this->kirimProfil($user, $this->dataProfil($user, ['email' => 'baru@example.com']))
            ->assertSessionHasErrors(['current_password' => 'Password saat ini wajib diisi untuk mengganti email.']);

        $this->kirimProfil($user, $this->dataProfil($user, ['email' => 'baru@example.com', 'current_password' => 'salah']))
            ->assertSessionHasErrors(['current_password' => 'Password saat ini salah.']);

        $this->assertSame('penulis@example.com', $user->refresh()->email);
    }

    public function test_ganti_email_dengan_password_benar_mereset_verifikasi(): void
    {
        $user = $this->buatUser();
        $this->assertNotNull($user->email_verified_at);

        $this->kirimProfil($user, $this->dataProfil($user, ['email' => 'baru@example.com', 'current_password' => 'password']))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_tidak_berubah_tidak_mereset_verifikasi_dan_tidak_butuh_password(): void
    {
        $user = $this->buatUser();

        $this->kirimProfil($user, $this->dataProfil($user, ['display_name' => 'Ganti Nama', 'email' => ' PENULIS@example.com ']))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Ganti Nama', $user->display_name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_current_password_tidak_disimpan_dan_role_status_tidak_bisa_diubah(): void
    {
        $user = $this->buatUser();

        $this->kirimProfil($user, $this->dataProfil($user, [
            'role' => 'admin',
            'status' => 'banned',
            'password_hash' => 'diretas',
            'email_verified_at' => null,
            'current_password' => 'password',
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(UserRole::Author, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('password', $user->password_hash));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_ganti_password_berhasil_dan_tersimpan_ter_hash(): void
    {
        $user = $this->buatUser(username: 'suki');

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'PasswordBaru-123',
            'password_confirmation' => 'PasswordBaru-123',
        ])
            ->assertSessionHasNoErrors('updatePassword')
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'Password berhasil diganti.');

        $user->refresh();
        $this->assertNotSame('PasswordBaru-123', $user->password_hash);
        $this->assertTrue(Hash::check('PasswordBaru-123', $user->password_hash));
        $this->assertFalse(Hash::check('password', $user->password_hash));

        Auth::logout();
        $this->post('/login', ['email' => 'suki@example.com', 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post('/login', ['email' => 'suki@example.com', 'password' => 'PasswordBaru-123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_ganti_password_dengan_password_lama_salah_ditolak(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'salah',
            'password' => 'PasswordBaru-123',
            'password_confirmation' => 'PasswordBaru-123',
        ])->assertSessionHasErrors(['current_password' => 'Password saat ini salah.'], errorBag: 'updatePassword');

        $this->assertTrue(Hash::check('password', $user->refresh()->password_hash));
    }

    public function test_ganti_password_dengan_konfirmasi_tidak_cocok_ditolak(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'PasswordBaru-123',
            'password_confirmation' => 'Lainnya-456',
        ])->assertSessionHasErrors(['password' => 'Konfirmasi password baru tidak cocok.'], errorBag: 'updatePassword');

        $this->assertTrue(Hash::check('password', $user->refresh()->password_hash));
    }

    public function test_password_baru_harus_berbeda_dan_memenuhi_aturan_default(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['password' => 'Password baru harus berbeda dari password saat ini.'], errorBag: 'updatePassword');

        $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password', errorBag: 'updatePassword');
    }

    public function test_hapus_akun_berhasil_untuk_user_tanpa_write_up(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_hapus_akun_ditolak_bila_password_salah(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'salah'])
            ->assertSessionHasErrors(['password' => 'Password salah.'], errorBag: 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_hapus_akun_ditolak_bila_masih_punya_write_up_termasuk_soft_deleted(): void
    {
        $pesan = 'Akun yang masih memiliki write-up atau gambar tidak bisa dihapus. Hubungi admin.';
        $user = $this->buatUser();
        $writeup = $this->buatWriteup($user, 'Akan Dihapus');

        $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors(['password' => $pesan], errorBag: 'userDeletion');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $writeup->delete();

        $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors(['password' => $pesan], errorBag: 'userDeletion');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_hapus_akun_ditolak_bila_masih_punya_media(): void
    {
        $user = $this->buatUser();

        MediaUpload::create([
            'uploader_id' => $user->id,
            'writeup_id' => null,
            'original_name' => 'a.png',
            'stored_name' => 'a.png',
            'file_path' => 'writeups/a.png',
            'file_ext' => 'png',
            'mime_type' => 'image/png',
            'file_size' => 10,
        ]);

        $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrors('password', errorBag: 'userDeletion');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_tombol_hapus_dinonaktifkan_dengan_alasan_bila_punya_konten(): void
    {
        $bersih = $this->buatUser(username: 'bersih');
        $berisi = $this->buatUser(username: 'berisi');
        $this->buatWriteup($berisi, 'Punya Tulisan');

        $this->actingAs($bersih)->get('/profile')
            ->assertSee('Zona berbahaya')
            ->assertSee('role="dialog"', false)
            ->assertDontSee('id="alasan-hapus"', false);

        $this->actingAs($berisi)->get('/profile')
            ->assertSee('Zona berbahaya')
            ->assertSee('id="alasan-hapus"', false)
            ->assertSee('Akun yang masih memiliki write-up atau gambar tidak bisa dihapus. Hubungi admin.')
            ->assertDontSee('role="dialog"', false);
    }

    public function test_tidak_ada_n_plus_1_pada_halaman_profile(): void
    {
        $saya = $this->buatUser();
        $tag = Tag::firstOrCreate(['slug' => 'xss'], ['name' => 'XSS']);

        $hitung = function () use ($saya): int {
            DB::enableQueryLog();
            $this->actingAs($saya)->get('/profile')->assertOk();
            $n = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::disableQueryLog();

            return $n;
        };

        $this->buatWriteup($saya, 'Satu')->tags()->sync([$tag->id]);
        $sedikit = $hitung();

        foreach (range(1, 12) as $i) {
            $this->buatWriteup($saya, "Banyak {$i}", ['published_at' => now()->subHours($i)])->tags()->sync([$tag->id]);
        }
        $banyak = $hitung();

        $this->assertSame($sedikit, $banyak);
        $this->assertLessThanOrEqual(14, $banyak);
    }
}
