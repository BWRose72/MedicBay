<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');

            $table->foreignId('doctor_id')
                ->constrained('doctors', 'doctor_id')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id')
                ->cascadeOnDelete();

            $table->dateTime('start_time');
            $table->boolean('has_left_review')->default(false);
            $table->string('status')->default('scheduled');

            $table->timestamps();

            // Composite index for scheduling and conflict checks
            $table->index(['doctor_id', 'start_time'], 'idx_doctor_start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
