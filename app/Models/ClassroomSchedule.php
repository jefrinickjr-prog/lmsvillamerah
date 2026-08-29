<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClassroomSchedule extends Model { protected $guarded=[]; public const DAYS=[0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu']; public function classroom(){return $this->belongsTo(Classroom::class);} public function getDayLabelAttribute():string{return self::DAYS[$this->day_of_week]??'-';} public function getDayNameAttribute():string{return $this->day_label;} }
