<?php
// app/Models/Writeup.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WriteupDifficulty;
use App\Enums\WriteupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $author_id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $content_md
 * @property WriteupDifficulty $difficulty
 * @property WriteupStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int $views_count
 * @property-read User $author
 * @property-read Category $category
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, Tool> $tools
 * @property-read Collection<int, MediaUpload> $mediaUploads
 * @property-read Collection<int, WriteupView> $views
 */
class Writeup extends Model
{
    use SoftDeletes;

    protected $table = 'writeups';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'content_md',
        'difficulty',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => WriteupDifficulty::class,
            'status' => WriteupStatus::class,
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Jumlah tampilan. Memakai hasil withCount('views') bila sudah dimuat;
     * kalau belum, jatuh ke satu query count (hindari di dalam loop).
     *
     * @return Attribute<int, never>
     */
    protected function viewsCount(): Attribute
    {
        return Attribute::get(fn (?int $value): int => $value ?? $this->views()->count());
    }

    /**
     * @param  Builder<Writeup>  $query
     * @return Builder<Writeup>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', WriteupStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Writeup>  $query
     * @return Builder<Writeup>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', WriteupStatus::Draft->value);
    }

    /**
     * @param  Builder<Writeup>  $query
     * @return Builder<Writeup>
     */
    public function scopeByDifficulty(Builder $query, WriteupDifficulty|string $difficulty): Builder
    {
        $value = $difficulty instanceof WriteupDifficulty ? $difficulty->value : $difficulty;

        return $query->where('difficulty', $value);
    }

    /**
     * Penulis write-up (FK: author_id).
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Kategori write-up (FK: category_id).
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Tag write-up lewat pivot writeup_tags (pivot tanpa timestamps).
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'writeup_tags', 'writeup_id', 'tag_id');
    }

    /**
     * Tool yang dipakai lewat pivot writeup_tools (pivot tanpa timestamps).
     *
     * @return BelongsToMany<Tool, $this>
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'writeup_tools', 'writeup_id', 'tool_id');
    }

    /**
     * Media (gambar) yang termuat di write-up ini.
     *
     * @return HasMany<MediaUpload, $this>
     */
    public function mediaUploads(): HasMany
    {
        return $this->hasMany(MediaUpload::class, 'writeup_id');
    }

    /**
     * Catatan tampilan (satu baris per kali dilihat).
     *
     * @return HasMany<WriteupView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(WriteupView::class, 'writeup_id');
    }
}