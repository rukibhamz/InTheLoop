<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('mailbox');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('graph_message_id')->unique();
            $table->string('internet_message_id')->nullable()->unique();
            $table->string('conversation_id')->nullable()->index();
            $table->string('folder')->default('inbox');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->text('graph_announcement_mailboxes')->nullable()->after('graph_monitored_mailboxes');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('graph_announcement_mailboxes');
        });

        Schema::dropIfExists('announcements');
    }
};
