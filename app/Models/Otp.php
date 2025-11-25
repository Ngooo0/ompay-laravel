<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp_code',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                     ->whereNull('used_at');
    }

    public function markAsUsed()
    {
        $this->used_at = now();
        $this->save();
    }
}