<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // <--- هذا السطر كان مفقوداً

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
        'phone_number',
        'total_price',
        'amount',
        'defualt_unit',
        'price_dollar',
        'total_price_dollar',       // <--- أضف هذا الحقل
    'defualt_unit_total_price', // <--- أضف هذا الحقل
    'amount_dollar',            // <--- أضف هذا الحقل
    'defualt_unit_amount',      // <--- أضف هذا الحقل
        'price',
        'status',
        'currency', 
    ];

    // علاقة أن المحفظة تنتمي لمستخدم واحد
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function transactions()
{
    return $this->hasMany(Transaction::class);
}

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? date('Y-m-d H:i:s', strtotime($value)) : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? date('Y-m-d H:i:s', strtotime($value)) : null,
        );
    }
}