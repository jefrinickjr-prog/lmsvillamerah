<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AcademicPeriod extends Model { protected $guarded=[]; protected function casts():array{return ['starts_on'=>'date','ends_on'=>'date','is_active'=>'boolean','is_default'=>'boolean'];} public function classrooms(){return $this->hasMany(Classroom::class);} }
