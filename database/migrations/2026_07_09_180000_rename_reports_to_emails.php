<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reports')) {
            return;
        }

        Schema::rename('report_categories', 'email_categories');
        Schema::rename('reports', 'emails');

        $this->renameChildTable('report_participants', 'email_participants');
        $this->renameChildTable('report_messages', 'email_messages');
        $this->renameChildTable('report_events', 'email_events');

        if (Schema::hasTable('attachments')) {
            DB::table('attachments')
                ->where('attachable_type', 'App\\Models\\Report')
                ->update(['attachable_type' => 'App\\Models\\Email']);

            DB::table('attachments')
                ->where('attachable_type', 'App\\Models\\ReportMessage')
                ->update(['attachable_type' => 'App\\Models\\EmailMessage']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('emails') || Schema::hasTable('reports')) {
            return;
        }

        if (Schema::hasTable('attachments')) {
            DB::table('attachments')
                ->where('attachable_type', 'App\\Models\\Email')
                ->update(['attachable_type' => 'App\\Models\\Report']);

            DB::table('attachments')
                ->where('attachable_type', 'App\\Models\\EmailMessage')
                ->update(['attachable_type' => 'App\\Models\\ReportMessage']);
        }

        $this->renameChildTableBack('email_participants', 'report_participants');
        $this->renameChildTableBack('email_messages', 'report_messages');
        $this->renameChildTableBack('email_events', 'report_events');

        Schema::rename('emails', 'reports');
        Schema::rename('email_categories', 'report_categories');
    }

    private function renameChildTable(string $from, string $to): void
    {
        if (! Schema::hasTable($from)) {
            return;
        }

        $this->dropForeignKeysOnColumn($from, 'report_id');
        Schema::rename($from, $to);

        if (Schema::hasColumn($to, 'report_id')) {
            Schema::table($to, function (Blueprint $table) {
                $table->renameColumn('report_id', 'email_id');
            });
        }

        Schema::table($to, function (Blueprint $table) {
            $table->foreign('email_id')->references('id')->on('emails')->cascadeOnDelete();
        });
    }

    private function renameChildTableBack(string $from, string $to): void
    {
        if (! Schema::hasTable($from)) {
            return;
        }

        $this->dropForeignKeysOnColumn($from, 'email_id');

        if (Schema::hasColumn($from, 'email_id')) {
            Schema::table($from, function (Blueprint $table) {
                $table->renameColumn('email_id', 'report_id');
            });
        }

        Schema::rename($from, $to);

        Schema::table($to, function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('reports')->cascadeOnDelete();
        });
    }

    private function dropForeignKeysOnColumn(string $table, string $column): void
    {
        try {
            $foreignKeys = Schema::getForeignKeys($table);
        } catch (\Throwable) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            } catch (\Throwable) {
                // Ignore if FK already gone
            }

            return;
        }

        foreach ($foreignKeys as $fk) {
            $cols = $fk['columns'] ?? [];
            if (! in_array($column, $cols, true)) {
                continue;
            }

            $name = $fk['name'] ?? null;
            if (! $name) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropForeign($name);
                });
            } catch (\Throwable) {
                // Ignore
            }
        }
    }
};
