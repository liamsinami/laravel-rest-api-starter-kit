<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FcmTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FcmToken extends Model
{
    /** @use HasFactory<FcmTokenFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_name',
        'last_used_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * The user who owns this device token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
