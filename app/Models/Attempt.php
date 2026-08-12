<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attempt extends Model { protected $guarded=[]; protected function casts():array{return ['started_at'=>'datetime','submitted_at'=>'datetime'];} public function exam(){return $this->belongsTo(Exam::class);} public function student(){return $this->belongsTo(User::class,'student_id');} public function answers(){return $this->hasMany(Answer::class);} }
