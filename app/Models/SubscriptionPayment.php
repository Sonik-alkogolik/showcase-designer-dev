<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'paid_for_month',
        'payment_received_at',
        'amount',
        'payment_method',
        'receipt_file',
        'note',
    ];

    protected $casts = [
        'paid_for_month' => 'date',
        'payment_received_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

