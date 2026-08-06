<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['program_type', 'delivery_mode', 'title', 'branch', 'description', 'teacher_id'];

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
}
