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
        Schema::create('doctor_time_offs', function (Blueprint $table) {
            $table->id('time_off_id');

            $table->foreignId('doctor_id')
                ->constrained('doctors', 'doctor_id')
                ->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            $table->index(['doctor_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_time_offs');
    }
};
