<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClassProgram extends Model { protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function category(){return $this->belongsTo(ProgramCategory::class,'program_category_id');} public function classrooms(){return $this->hasMany(Classroom::class);} }
