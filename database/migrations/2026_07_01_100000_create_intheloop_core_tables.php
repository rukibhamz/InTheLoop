<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shared_mailbox_email')->unique();
            $table->string('department')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('directory_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('azure_object_id')->nullable()->unique();
            $table->string('display_name');
            $table->string('email')->unique();
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('report_categories');
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('pending');
            $table->string('conversation_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('report_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('mailbox');
            $table->string('from_email');
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('graph_message_id')->nullable();
            $table->string('internet_message_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_events');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('report_messages');
        Schema::dropIfExists('report_participants');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_categories');
        Schema::dropIfExists('directory_contacts');
        Schema::dropIfExists('recipients');
    }
};
