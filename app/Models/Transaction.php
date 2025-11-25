<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'client_id',
        'beneficiary_id',
        'amount',
        'type',
        'description',
        'status',
        'client_phone',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'beneficiary_id');
    }
}