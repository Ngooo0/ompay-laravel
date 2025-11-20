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

    /**
     * Vérifier si l'OTP est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si l'OTP est utilisé
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Marquer l'OTP comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    /**
     * Scope pour les OTP non utilisés et non expirés
     */
    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
                    ->where('expires_at', '>', now());
    }
}