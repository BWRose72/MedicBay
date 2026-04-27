<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('doctors', 'name')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        if (
            Schema::hasColumn('patients', 'medical_record_number')
            && ! Schema::hasColumn('patients', 'personal_identification_number')
        ) {
            Schema::table('patients', function (Blueprint $table) {
                $table->renameColumn('medical_record_number', 'personal_identification_number');
            });
        }

        if (Schema::hasColumn('patients', 'name')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'name')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('name')->nullable()->after('user_id');
            });
        }

        if (
            Schema::hasColumn('patients', 'personal_identification_number')
            && ! Schema::hasColumn('patients', 'medical_record_number')
        ) {
            Schema::table('patients', function (Blueprint $table) {
                $table->renameColumn('personal_identification_number', 'medical_record_number');
            });
        }

        if (! Schema::hasColumn('doctors', 'name')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->string('name')->nullable()->after('specialisation_id');
            });
        }
    }
};
