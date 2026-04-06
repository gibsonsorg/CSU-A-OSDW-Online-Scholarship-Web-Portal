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
        if (! Schema::hasColumn('users', 'id_document')) {
            Schema::table('users', function (Blueprint $table) {
                // add column if not exists; place after student_id when possible
                $table->string('id_document')->nullable()->after('student_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'id_document')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('id_document');
            });
        }
    }
};
