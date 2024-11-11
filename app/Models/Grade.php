<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'subject_id', 'grade', 'remarks'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getAverageAttribute()
    {
        $total = $this->first_quarter + $this->second_quarter + $this->third_quarter + $this->fourth_quarter;

        return $total / 4;
    }
}
