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
        Schema::create('announces', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Title or name of the announcement
            $table->text('body'); // Main content of the announcement
            $table->string('image')->nullable(); // Optional image for the announcement
            $table->date('start_date'); // Start date for the announcement
            $table->date('end_date')->nullable(); // Optional end date for the announcement
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announces');
    }
};
