<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'business_name',
        'owner_id',
        'merchant_code',
    ];

    public function owner()
    {
        return $this->belongsTo(Client::class, 'owner_id');
    }
}