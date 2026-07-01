<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_admin');
            $table->boolean('two_factor_enabled')->default(false)->after('is_active');
        });

        Schema::table('report_categories', function (Blueprint $table) {
            $table->foreignId('default_recipient_id')->nullable()->after('description')->constrained('recipients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_recipient_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'two_factor_enabled']);
        });
    }
};
