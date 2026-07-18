<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveStreamSession extends Model
{
    public const MAX_PARTICIPANTS = 20;

    protected $fillable = ['classroom_id', 'title', 'meeting_url', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'live_stream_participants')->withTimestamps();
    }
}
