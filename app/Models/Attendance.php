<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['classroom_id', 'student_id', 'date', 'week_start', 'present'];

    protected $casts = [
        'date' => 'date',
        'week_start' => 'date',
        'present' => 'boolean',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
