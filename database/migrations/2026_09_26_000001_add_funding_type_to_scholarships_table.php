<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            if (!Schema::hasColumn('scholarships', 'funding_type')) {
                $table->string('funding_type')->nullable()->after('type_label');
            }
        });

        $fundingMap = [
            'Cagayan Valley Assoc. of Hawaii' => 'International',
            'University Based Scholarship' => 'In-house',
            'ACEF – GIAHEP' => 'Local-Government',
            'DOST SEI Project Strand' => 'Local-Government',
            'Tulong Dunong Program' => 'Local-Government',
            'LGU Aparri' => 'Local-Government',
            'Natalged A Lasam' => 'Local-Government',
            'Andres Tamayo Scholarship' => 'Local-Private',
            'Gokongwei Brothers Foundation' => 'Local-Private',
        ];

        foreach ($fundingMap as $name => $fundingType) {
            DB::table('scholarships')
                ->where('type', 'academic')
                ->where('name', $name)
                ->update(['funding_type' => $fundingType]);
        }

        DB::table('scholarships')
            ->where('type', 'academic')
            ->whereNotIn('name', array_keys($fundingMap))
            ->update(['funding_type' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            if (Schema::hasColumn('scholarships', 'funding_type')) {
                $table->dropColumn('funding_type');
            }
        });
    }
};
