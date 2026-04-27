<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'medical_record_number')) {
                $table->renameColumn('medical_record_number', 'personal_identification_number');
            }

            if (Schema::hasColumn('patients', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'name')) {
                $table->string('name')->nullable()->after('user_id');
            }

            if (Schema::hasColumn('patients', 'personal_identification_number')) {
                $table->renameColumn('personal_identification_number', 'medical_record_number');
            }
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'name')) {
                $table->string('name')->nullable()->after('specialisation_id');
            }
        });
    }
};
