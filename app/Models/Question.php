<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Question extends Model { use SoftDeletes; protected $guarded=[]; public function subject(){return $this->belongsTo(Subject::class);} public function topic(){return $this->belongsTo(Topic::class);} public function options(){return $this->hasMany(QuestionOption::class)->orderBy('option_label');} public function exams(){return $this->belongsToMany(Exam::class)->withPivot('order');} }
