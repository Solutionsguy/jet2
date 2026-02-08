<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Userbit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'userid',
        'amount',
        'type',
        'gameid',
        'section_no',
        'wallet_type',
        'status',
        'cashout_multiplier'
    ];
}
