<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'userid',
        'amount',
        'freebet_amount',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'freebet_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($wallet) {
            if ($wallet->isDirty('amount')) {
                $wallet->user()->update(['balance' => $wallet->amount]);
            }
        });
    }
}

