<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use App\Http\Requests\StoreWriteupRequest;
use App\Models\Category;
use App\Models\MediaUpload;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use App\Models\Writeup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
