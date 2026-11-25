<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaFileExt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $uploader_id
 * @property int|null $writeup_id
 * @property string $original_name
 * @property string $stored_name
 * @property string $file_path
 * @property MediaFileExt $file_ext
 * @property string $mime_type
 * @property int $file_size
 * @property Carbon|null $created_at
 * @property-read string $url
 * @property-read User $uploader
 * @property-read Writeup|null $writeup
 */
class MediaUpload extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'media_uploads';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uploader_id',
        'writeup_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_ext',
        'mime_type',
        'file_size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_ext' => MediaFileExt::class,
            'file_size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * URL publik file, dari disk "public".
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->file_path));
    }

    /**
     * Pengguna yang mengunggah (FK: uploader_id).
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Write-up tempat media dimuat. Nullable: media bisa belum tertaut.
     *
     * @return BelongsTo<Writeup, $this>
     */
    public function writeup(): BelongsTo
    {
        return $this->belongsTo(Writeup::class, 'writeup_id');
    }
}