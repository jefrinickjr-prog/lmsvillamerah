<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['material_id', 'title', 'description', 'due_at'];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
