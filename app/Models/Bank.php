<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // <--- هذا السطر كان مفقوداً

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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