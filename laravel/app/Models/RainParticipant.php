<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RainParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'rain_id',
        'user_id',
        'amount_won',
        'is_winner'
    ];

    protected $casts = [
        'amount_won' => 'decimal:2',
        'is_winner' => 'boolean'
    ];

    public function rain()
    {
        return $this->belongsTo(RainGiveaway::class, 'rain_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
