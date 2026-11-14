<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Http\Requests\StoreWriteupRequest;
use App\Http\Requests\UpdateWriteupRequest;
use App\Models\Category;
use App\Models\MediaUpload;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use App\Models\Writeup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WriteupController extends Controller
{
    /**
     * Panjang maksimal kolom writeups.slug.
     */
    private const SLUG_MAX_LENGTH = 220;

    /**
     * Jumlah percobaan ulang bila dua request menghasilkan slug yang sama bersamaan.
     */
    private const SLUG_ATTEMPTS = 3;

    /**
     * Jumlah write-up per halaman pada dashboard.
     */
    private const PER_PAGE = 12;

    /**
     * Batas panjang kata kunci pencarian.
     */
    private const SEARCH_MAX_LENGTH = 100;

    /**
     * Dashboard "Arsip Write-Up": daftar write-up + pencarian dan filter di server.
     */
    public function index(Request $request): View|RedirectResponse
    {
        // Tab "saya" sudah dipindah ke halaman sendiri; tautan lama tetap berfungsi.
        // Tamu langsung ke login (bukan lewat redirect ganda).
        if ($this->queryString($request, 'tab') === 'saya') {
            return $request->user() === null
                ? redirect()->guest(route('login'))
                : redirect()->route('writeups.mine');
        }

        // Semua input filter divalidasi; nilai tidak valid diabaikan (dianggap "Semua").
        $keyword = mb_substr($this->queryString($request, 'q'), 0, self::SEARCH_MAX_LENGTH);

        $categories = Category::query()->orderBy('id')->get(['id', 'name', 'slug']);
        $category = $categories->firstWhere('slug', $this->queryString($request, 'kategori'));
        $level = WriteupDifficulty::tryFrom($this->queryString($request, 'level'));

        $writeups = Writeup::published()
            ->with(['author:id,username,display_name', 'category:id,name,slug', 'tags:id,name'])
            ->when($category !== null, fn (Builder $q) => $q->where('category_id', $category->id))
            ->when($level !== null, fn (Builder $q) => $q->byDifficulty($level))
            ->when($keyword !== '', fn (Builder $q) => $q->search($keyword))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('writeups.index', [
            'writeups' => $writeups,
            'excerpts' => $writeups->getCollection()
                ->mapWithKeys(fn (Writeup $w): array => [$w->id => $this->excerpt($w->content_md)]),
            'categories' => $categories,
            'levels' => WriteupDifficulty::cases(),
            'filters' => [
                'q' => $keyword,
                'kategori' => $category?->slug,
                'level' => $level?->value,
            ],
        ]);
    }

    /**
     * Ambil query string sebagai string; array atau tipe lain dianggap kosong.
     */
    private function queryString(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Ringkasan kartu dari Markdown: tanpa sintaks, gambar, judul, dan blok kode.
     */
    private function excerpt(string $markdown): string
    {
        // Cukup bagian awal; write-up bisa sangat panjang.
        $html = Str::markdown(mb_substr($markdown, 0, 3000), ['html_input' => 'strip']);

        // Judul/blok kode/tabel bukan ringkasan; gambar hilang bersama strip_tags.
        $html = preg_replace('#<(h[1-6]|pre|table)\b.*?</\1>#is', ' ', $html) ?? $html;
        $text = strip_tags(str_replace('<', ' <', $html));
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5)) ?? '');

        return Str::limit($text, 160);
    }

    /**
     * Halaman "Postingan Saya": semua write-up milik user login (published dan draft).
     */
    public function mine(Request $request): View
    {
        $writeups = Writeup::query()
            ->where('author_id', $request->user()->id)
            ->with(['category:id,name,slug', 'tags:id,name'])
            // Draft tidak punya published_at; urut menurut tanggal yang tampil di baris.
            ->orderByRaw('coalesce(published_at, created_at) desc')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('writeups.mine', ['writeups' => $writeups]);
    }

    /**
     * Halaman edit: form yang sama dengan create, berisi data write-up.
     * Otorisasi: middleware can:update,writeup pada route.
     */
    public function edit(Writeup $writeup): View
    {
        return view('writeups.edit', [
            'writeup' => $writeup->load(['tags:id,name', 'tools:id,name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'tools' => Tool::query()->orderBy('name')->get(['id', 'name']),
            'difficulties' => WriteupDifficulty::cases(),
        ]);
    }

    public function update(UpdateWriteupRequest $request, Writeup $writeup): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $status = WriteupStatus::from($data['status']);

        // Slug sengaja tidak diubah supaya URL stabil.
        DB::transaction(function () use ($user, $writeup, $data, $status): void {
            $writeup->update([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'content_md' => $data['content_md'],
                'difficulty' => $data['difficulty'],
                'status' => $status,
                'published_at' => $this->publishedAtBaru($writeup, $status),
            ]);

            $writeup->tags()->sync($data['tags'] ?? []);
            $writeup->tools()->sync($data['tools'] ?? []);

            $this->linkMedia($user, $writeup);
        });

        return redirect()
            ->route('writeups.mine')
            ->with('status', 'Write-up berhasil diperbarui.');
    }

    /**
     * Hapus write-up (soft delete) setelah password dikonfirmasi ulang.
     * Otorisasi dan pembatasan percobaan ada di middleware route.
     */
    public function destroy(Request $request, Writeup $writeup): RedirectResponse
    {
        try {
            $request->validateWithBag('writeupDeletion', [
                'password' => ['required', 'current_password'],
            ], [
                'password.required' => 'Password wajib diisi.',
                'password.current_password' => 'Password salah.',
            ]);
        } catch (ValidationException $e) {
            // Modal di halaman daftar dibuka lagi untuk write-up yang sama.
            $request->session()->flash('hapus_slug', $writeup->slug);

            throw $e;
        }

        // Soft delete (bukan forceDelete) agar slug tetap tercadangkan untuk uniqueSlug().
        $writeup->delete();

        return redirect()
            ->route('writeups.mine')
            ->with('status', 'Write-up berhasil dihapus.');
    }

    /**
     * published_at menurut transisi status: draft -> published = sekarang,
     * published -> published = dipertahankan, published -> draft = null
     * (konsisten dengan scope Writeup::published()).
     */
    private function publishedAtBaru(Writeup $writeup, WriteupStatus $status): ?Carbon
    {
        if ($status === WriteupStatus::Draft) {
            return null;
        }

        return $writeup->status === WriteupStatus::Published && $writeup->published_at !== null
            ? $writeup->published_at
            : now();
    }

    public function create(Request $request): View
    {
        // User banned tidak perlu melihat form yang pasti ditolak saat disimpan.
        abort_unless($request->user()->status === UserStatus::Active, 403);

        return view('writeups.create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'tools' => Tool::query()->orderBy('name')->get(['id', 'name']),
            'difficulties' => WriteupDifficulty::cases(),
        ]);
    }

    public function store(StoreWriteupRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $status = WriteupStatus::from($data['status']);

        $this->saveWithUniqueSlug($user, $data, $status);

        return redirect()
            ->route('dashboard')
            ->with('status', $status === WriteupStatus::Published
                ? 'Write-up berhasil dipublikasikan.'
                : 'Write-up berhasil disimpan sebagai draft.');
    }

    /**
     * Simpan write-up beserta relasinya dalam satu transaksi. Jika dua request
     * berebut slug yang sama, kolom UNIQUE menolaknya dan slug dihitung ulang.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveWithUniqueSlug(User $user, array $data, WriteupStatus $status): Writeup
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($user, $data, $status): Writeup {
                    $writeup = Writeup::create([
                        'author_id' => $user->id,
                        'category_id' => $data['category_id'],
                        'title' => $data['title'],
                        'slug' => $this->uniqueSlug($data['title']),
                        'content_md' => $data['content_md'],
                        'difficulty' => $data['difficulty'],
                        'status' => $status,
                        // published dan draft harus konsisten dengan scope Writeup::published().
                        'published_at' => $status === WriteupStatus::Published ? now() : null,
                    ]);

                    $writeup->tags()->sync($data['tags'] ?? []);
                    $writeup->tools()->sync($data['tools'] ?? []);

                    $this->linkMedia($user, $writeup);

                    return $writeup;
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::SLUG_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Slug dari judul; bila sudah dipakai (termasuk write-up yang di-soft-delete)
     * tambahkan akhiran -2, -3, dst. Hasil selalu <= 220 karakter.
     */
    private function uniqueSlug(string $title): string
    {
        // Sisakan ruang untuk akhiran angka.
        $base = rtrim(mb_substr(Str::slug($title), 0, self::SLUG_MAX_LENGTH - 10), '-');

        if ($base === '') {
            $base = 'writeup';
        }

        $taken = Writeup::withTrashed()
            ->where('slug', $base)
            ->orWhere('slug', 'like', $base.'-%')
            ->pluck('slug')
            ->all();

        $slug = $base;

        for ($n = 2; in_array($slug, $taken, true); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    /**
     * Tautkan media milik user yang belum punya write-up dan URL-nya muncul di content_md.
     */
    private function linkMedia(User $user, Writeup $writeup): void
    {
        $ids = $user->mediaUploads()
            ->whereNull('writeup_id')
            ->get(['id', 'file_path'])
            ->filter(fn (MediaUpload $media): bool => str_contains($writeup->content_md, $media->file_path))
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            $user->mediaUploads()->whereKey($ids)->update(['writeup_id' => $writeup->id]);
        }
    }
}
