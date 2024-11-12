<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'file_url', 'section_id', 'teacher_id'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_activity_student','student_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function teacher()
{
    return $this->belongsTo(Teacher::class);
}
}