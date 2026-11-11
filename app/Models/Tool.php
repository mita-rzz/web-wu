<?php
// app/Models/Tool.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property-read Collection<int, Writeup> $writeups
 */
class Tool extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tools';

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
     * Write-up yang memakai tool ini lewat pivot writeup_tools.
     *
     * @return BelongsToMany<Writeup, $this>
     */
    public function writeups(): BelongsToMany
    {
        return $this->belongsToMany(Writeup::class, 'writeup_tools', 'tool_id', 'writeup_id');
    }
}