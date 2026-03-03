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
        Schema::create('doctor_working_hour', function (Blueprint $table) {
            $table->id('working_hours_id');
            $table->foreignId('doctor_id')
                ->constrained('doctors', 'doctor_id')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 0–6 (iso monday=1, sunday=7)
            $table->time('start_time');
            $table->time('end_time');
            $table->time('effective_from');
            $table->time('effective_to')->nullable();

            $table->timestamps();

            $table->index(['doctor_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors_working_hour');
    }
};
