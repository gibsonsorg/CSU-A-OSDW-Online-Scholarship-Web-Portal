<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->string('type_label')->nullable()->after('type');
            $table->text('short_information')->nullable()->after('type_label');
            $table->text('description')->nullable()->after('short_information');
            $table->json('requirements')->nullable()->after('description');
            $table->json('required_documents')->nullable()->after('requirements');
            $table->string('logo_path')->nullable()->after('required_documents');
        });
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropColumn([
                'type_label',
                'short_information',
                'description',
                'requirements',
                'required_documents',
                'logo_path',
            ]);
        });
    }
};
