<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = ['classroom_id', 'title', 'content', 'youtube_embed_url'];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
