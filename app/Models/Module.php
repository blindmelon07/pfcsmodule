<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'file_url', 'section_id', 'teacher_id'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'module_student', 'module_id', 'student_id');
    }

    public function sectionStudents()
    {
        return $this->hasManyThrough(Student::class, Section::class, 'id', 'section_id', 'section_id', 'id');
    }
}
