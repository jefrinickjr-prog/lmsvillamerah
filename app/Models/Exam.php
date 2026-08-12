<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Exam extends Model { protected $guarded=[]; protected function casts():array{return ['randomize_questions'=>'boolean','randomize_options'=>'boolean','allow_retake'=>'boolean','allow_resume'=>'boolean'];} public function subject(){return $this->belongsTo(Subject::class);} public function questions(){return $this->belongsToMany(Question::class)->withPivot('order')->orderByPivot('order');} public function attempts(){return $this->hasMany(Attempt::class);} }
