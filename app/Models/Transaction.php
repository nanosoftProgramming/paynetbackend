<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'price',
        'status',
        'phone',
        'type',
    ];

    // علاقة أن المعاملة تنتمي لمستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة أن المعاملة تنتمي لمحافظة/محفظة
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}