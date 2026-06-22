<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_type')->default('assignment')->after('material_id');
            $table->string('attachment_path')->nullable()->after('description');
            $table->json('questions')->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['task_type', 'attachment_path', 'questions']);
        });
    }
};
