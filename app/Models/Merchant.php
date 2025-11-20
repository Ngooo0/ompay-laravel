<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'merchant_code', 'business_name', 'category', 'commission_rate', 'is_active'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
