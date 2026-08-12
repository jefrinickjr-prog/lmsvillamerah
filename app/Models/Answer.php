<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Answer extends Model { protected $guarded=[]; public function question(){return $this->belongsTo(Question::class);} public function attempt(){return $this->belongsTo(Attempt::class);} }
