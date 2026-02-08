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
}

