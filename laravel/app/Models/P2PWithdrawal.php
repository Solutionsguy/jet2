<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P2PWithdrawal extends Model
{
    use HasFactory;

    protected $table = 'p2p_withdrawals';

    protected $fillable = [
        'user_id', 'peer_id', 'amount', 'reference', 'status', 'matched_at', 'completed_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function peer()
    {
        return $this->belongsTo(P2PPeer::class, 'peer_id');
    }
}
