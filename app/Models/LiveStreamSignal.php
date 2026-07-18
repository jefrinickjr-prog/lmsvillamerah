<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveStreamSignal extends Model
{
    protected $fillable = ['live_stream_session_id', 'from_user_id', 'to_user_id', 'type', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
