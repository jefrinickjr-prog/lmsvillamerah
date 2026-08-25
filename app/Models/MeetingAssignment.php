<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingAssignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['meeting_date' => 'date', 'due_at' => 'datetime'];
    }

    public function classroom() { return $this->belongsTo(Classroom::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(MeetingSubmission::class); }
}
