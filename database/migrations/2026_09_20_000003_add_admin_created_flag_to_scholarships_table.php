<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->boolean('is_admin_created')->default(true)->after('status');
        });

        DB::table('scholarships')
            ->whereIn('name', [
                'Cagayan Valley Assoc. of Hawaii',
                'University Based Scholarship',
                'ACEF – GIAHEP',
            ])
            ->update(['is_admin_created' => false]);
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropColumn('is_admin_created');
        });
    }
};
