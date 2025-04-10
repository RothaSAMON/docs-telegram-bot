<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->string('media_group_id')->nullable()->after('file_type');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropColumn('media_group_id');
        });
    }
};