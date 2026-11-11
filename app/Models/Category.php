<?php
// app/Models/Category.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property-read Collection<int, Writeup> $writeups
 */
class Category extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Satu kategori mengelompokkan banyak write-up.
     *
     * @return HasMany<Writeup, $this>
     */
    public function writeups(): HasMany
    {
        return $this->hasMany(Writeup::class, 'category_id');
    }
}