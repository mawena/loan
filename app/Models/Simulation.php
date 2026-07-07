<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulation extends Model
{
    use HasFactory;

    public const TYPE_AMORTIZATION = 'amortization';
    public const TYPE_COMPARISON = 'comparison';
    public const TYPE_EARLY_REPAYMENT = 'early_repayment';
    public const TYPE_BORROWING_CAPACITY = 'borrowing_capacity';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'params',
        'results',
        'share_token',
        'ip_address',
    ];

    protected $casts = [
        'params' => 'array',
        'results' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
