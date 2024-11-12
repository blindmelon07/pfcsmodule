<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_activities', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('student_id')->constrained()->onDelete('cascade'); // Link to student
            $table->foreignId('section_id')->nullable()->constrained()->onDelete('set null'); // Link to section
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null'); // Link to teacher
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_url')->nullable(); // For uploaded files
            $table->timestamps();
            $table->softDeletes(); // If you want to enable soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_activities');
    }
};
