<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MediaFileExt;
use App\Http\Requests\StoreMediaUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaUploadController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function store(StoreMediaUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $ext = MediaFileExt::tryFrom((string) $file->guessExtension());

        if ($ext === null) {
            throw ValidationException::withMessages([
                'file' => 'Format gambar harus jpg, jpeg, png, gif, atau webp.',
            ]);
        }

        $storedName = Str::uuid()->toString().'.'.$ext->value;
        $disk = Storage::disk('public');
        $path = $disk->putFileAs('writeups/'.now()->format('Y/m'), $file, $storedName);

        if ($path === false) {
            return response()->json(['message' => 'Gambar gagal disimpan. Coba lagi.'], 500);
        }

        try {
            $media = $request->user()->mediaUploads()->create([
                'writeup_id' => null,
                'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'stored_name' => $storedName,
                'file_path' => $path,
                'file_ext' => $ext,
                'mime_type' => (string) $file->getMimeType(),
                'file_size' => (int) $file->getSize(),
            ]);
        } catch (Throwable $e) {
            $disk->delete($path);

            throw $e;
        }

        return response()->json([
            'url' => $media->url,
            'id' => $media->id,
        ], 201);
    }
}
