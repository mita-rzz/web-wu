<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $writeup_id
 * @property Carbon $viewed_at
 * @property-read Writeup $writeup
 */
class WriteupView extends Model
{
    public $timestamps = false;

    protected $table = 'writeup_views';

    /**
     * viewed_at diisi default database (useCurrent), jadi tidak di-fillable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'writeup_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Write-up yang dilihat.
     *
     * @return BelongsTo<Writeup, $this>
     */
    public function writeup(): BelongsTo
    {
        return $this->belongsTo(Writeup::class, 'writeup_id');
    }
}