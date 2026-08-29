<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['class_program_id', 'branch_id', 'academic_period_id', 'program_type', 'delivery_mode', 'title', 'section_name', 'capacity', 'is_active', 'branch', 'description', 'teacher_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'capacity' => 'integer'];
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class)->withTimestamps();
    }

    public function primaryMaterials()
    {
        return $this->hasMany(Material::class);
    }

    public function liveStreamSessions()
    {
        return $this->hasMany(LiveStreamSession::class);
    }

    public function meetingAssignments()
    {
        return $this->hasMany(MeetingAssignment::class);
    }

    public function program() { return $this->belongsTo(ClassProgram::class, 'class_program_id'); }
    public function branchMaster() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function academicPeriod() { return $this->belongsTo(AcademicPeriod::class); }
    public function schedules() { return $this->hasMany(ClassroomSchedule::class)->orderBy('day_of_week')->orderBy('starts_at'); }
    public function enrollments() { return $this->hasMany(ClassroomEnrollment::class); }
    public function activeEnrollments() { return $this->hasMany(ClassroomEnrollment::class)->where('status', 'active'); }
    public function students() { return $this->belongsToMany(User::class, 'classroom_enrollments', 'classroom_id', 'student_id')->withPivot(['status','joined_at','left_at','assigned_by','notes'])->withTimestamps(); }
    public function activeStudents() { return $this->students()->wherePivot('status', 'active'); }
    public function getDisplayNameAttribute(): string { return $this->title.' — '.($this->section_name ?: 'Umum'); }
}
