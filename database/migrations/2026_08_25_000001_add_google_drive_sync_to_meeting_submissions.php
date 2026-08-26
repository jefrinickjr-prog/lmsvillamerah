<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meeting_submissions', function (Blueprint $table): void {
            $table->string('drive_sync_status', 20)->default('disabled')->after('work_path');
            $table->string('drive_file_id')->nullable()->after('drive_sync_status');
            $table->text('drive_web_view_link')->nullable()->after('drive_file_id');
            $table->text('drive_sync_error')->nullable()->after('drive_web_view_link');
            $table->timestamp('drive_synced_at')->nullable()->after('drive_sync_error');
        });

        Schema::create('google_drive_folders', function (Blueprint $table): void {
            $table->id();
            $table->string('path_hash', 64)->unique();
            $table->text('folder_path');
            $table->string('folder_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_folders');
        Schema::table('meeting_submissions', function (Blueprint $table): void {
            $table->dropColumn(['drive_sync_status', 'drive_file_id', 'drive_web_view_link', 'drive_sync_error', 'drive_synced_at']);
        });
    }
};
