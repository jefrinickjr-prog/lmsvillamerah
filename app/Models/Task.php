<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['material_id', 'task_type', 'title', 'description', 'attachment_path', 'questions', 'due_at'];

    public const TYPES = [
        'assignment' => 'Tugas Upload / Instruksi',
        'essay' => 'Pembelajaran Esai',
        'multiple_choice' => 'Pilihan Ganda',
        'questionnaire' => 'Kuesioner',
    ];

    public static function typeOptions(): array
    {
        return self::TYPES;
    }

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? self::TYPES['assignment'];
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class)->withTimestamps();
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'questions' => 'array',
        ];
    }
}
