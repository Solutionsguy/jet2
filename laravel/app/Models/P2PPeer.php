<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P2PPeer extends Model
{
    use HasFactory;

    protected $table = 'p2p_peers';

    protected $fillable = [
        'name', 'phone', 'status', 'min_limit', 'max_limit', 'success_rate', 'avg_time'
    ];
}
