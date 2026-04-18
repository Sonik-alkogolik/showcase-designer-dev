<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends Model
{
    protected $fillable = [
        'user_id',
        'shop_id',
        'status',
        'source_filename',
        'file_path',
        'mapping',
        'image_base_url',
        'limit',
        'current_count_before_import',
        'available_slots_before_import',
        'total_rows',
        'imported_count',
        'success_count',
        'failed_count',
        'skipped_due_to_limit',
        'failures',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'mapping' => 'array',
        'failures' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
