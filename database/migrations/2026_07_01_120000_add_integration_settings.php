<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('graph_tenant_id')->nullable()->after('accent_color');
            $table->text('graph_client_id')->nullable();
            $table->text('graph_client_secret')->nullable();
            $table->string('graph_default_sender_mailbox')->nullable();
            $table->text('graph_monitored_mailboxes')->nullable();
            $table->string('microsoft_tenant_id')->nullable();
            $table->text('microsoft_client_id')->nullable();
            $table->text('microsoft_client_secret')->nullable();
            $table->boolean('sso_enabled')->default(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('shared_mailbox_email')->nullable()->after('department');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->string('approval_token_hash', 64)->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('approval_token_hash');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shared_mailbox_email');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'graph_tenant_id',
                'graph_client_id',
                'graph_client_secret',
                'graph_default_sender_mailbox',
                'graph_monitored_mailboxes',
                'microsoft_tenant_id',
                'microsoft_client_id',
                'microsoft_client_secret',
                'sso_enabled',
            ]);
        });
    }
};
