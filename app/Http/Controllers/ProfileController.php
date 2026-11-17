<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WriteupDifficulty;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Writeup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Jumlah write-up terbaru pada tab Ringkasan.
     */
    private const TERBARU = 5;

    /**
     * Halaman profil: ringkasan + pengaturan akun.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Satu query ber-group; jumlah terbit = jumlah semua level.
        $perLevel = array_fill_keys(array_column(WriteupDifficulty::cases(), 'value'), 0);

        Writeup::published()
            ->where('author_id', $user->id)
            ->selectRaw('difficulty, count(*) as total')
            ->groupBy('difficulty')
            ->get()
            ->each(function (Writeup $baris) use (&$perLevel): void {
                $perLevel[$baris->difficulty->value] = (int) $baris->getAttribute('total');
            });

        return view('profile.edit', [
            'user' => $user,
            'jumlahTerbit' => array_sum($perLevel),
            'jumlahDraft' => Writeup::draft()->where('author_id', $user->id)->count(),
            'perLevel' => $perLevel,
            'terbaru' => Writeup::published()
                ->where('author_id', $user->id)
                ->with(['category:id,name,slug', 'tags:id,name'])
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(self::TERBARU)
                ->get(),
            'punyaKonten' => $this->punyaKonten($user),
        ]);
    }

    /**
     * Ubah username, nama tampilan, bio, dan email.
     * role/status tidak pernah diambil dari input.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // current_password hanya untuk validasi; tidak disimpan.
        $user->fill(Arr::only($request->validated(), ['username', 'display_name', 'bio', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Hapus akun. Ditolak bila masih punya write-up (termasuk yang di-soft-delete)
     * atau media, karena FK-nya restrictOnDelete.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.current_password' => 'Password salah.',
        ]);

        $user = $request->user();

        if ($this->punyaKonten($user)) {
            throw ValidationException::withMessages([
                'password' => 'Akun yang masih memiliki write-up atau gambar tidak bisa dihapus. Hubungi admin.',
            ])->errorBag('userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Apakah user masih punya write-up (termasuk soft-deleted) atau media upload.
     */
    private function punyaKonten(User $user): bool
    {
        return $user->writeups()->withTrashed()->exists() || $user->mediaUploads()->exists();
    }
}
