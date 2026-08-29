<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClassroomEnrollment extends Model { protected $guarded=[]; protected function casts():array{return ['joined_at'=>'datetime','left_at'=>'datetime'];} public function classroom(){return $this->belongsTo(Classroom::class);} public function student(){return $this->belongsTo(User::class,'student_id');} public function assigner(){return $this->belongsTo(User::class,'assigned_by');} }
