<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RainGiveaway extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'total_amount',
        'amount_per_user',
        'num_winners',
        'status',
        'started_at',
        'completed_at',
        'winners'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_per_user' => 'decimal:2',
        'num_winners' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'winners' => 'array'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(RainParticipant::class, 'rain_id');
    }

    public function winnerUsers()
    {
        return $this->participants()->where('is_winner', true);
    }
}
