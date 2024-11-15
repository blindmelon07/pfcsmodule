<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TomatoPHP\FilamentMediaManager\Traits\InteractsWithMediaFolders;

class Guardian extends Model
{
    use InteractsWithMediaFolders;
    use HasFactory;

    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withTimestamps(); // This will automatically update `created_at` and `updated_at` in the pivot table
    }
}
