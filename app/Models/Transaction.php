<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // <--- هذا السطر كان مفقوداً


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
        "bank_id",
        'phone_number',
        'defualt_unit', // <--- أضف هذا الحقل
    'price_dollar', // <--- أضف هذا الحقل
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
    public function bank()
    {
        return $this->belongsTo(Bank::class);
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