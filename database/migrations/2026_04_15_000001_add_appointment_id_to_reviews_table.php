<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('review_id')
                ->constrained('appointments', 'appointment_id')
                ->cascadeOnDelete();

            $table->unique('appointment_id', 'reviews_appointment_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_appointment_id_unique');
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
