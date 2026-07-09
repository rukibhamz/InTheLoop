<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->boolean('show_in_thread')->default(true)->after('email_pending');
        });

        DB::table('email_messages')
            ->where('direction', 'outbound')
            ->whereNotNull('body_html')
            ->update(['show_in_thread' => false]);
    }

    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table) {
            $table->dropColumn('show_in_thread');
        });
    }
};
