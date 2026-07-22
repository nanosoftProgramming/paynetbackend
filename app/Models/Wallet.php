<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
        'phone_number',
        'total_price',
        'amount',
        'price',
        'status',
    ];

    // علاقة أن المحفظة تنتمي لمستخدم واحد
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}