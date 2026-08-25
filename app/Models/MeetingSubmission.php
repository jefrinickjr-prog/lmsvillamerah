<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSubmission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'graded_at' => 'datetime'];
    }

    public function assignment() { return $this->belongsTo(MeetingAssignment::class, 'meeting_assignment_id'); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function grader() { return $this->belongsTo(User::class, 'graded_by'); }
}
