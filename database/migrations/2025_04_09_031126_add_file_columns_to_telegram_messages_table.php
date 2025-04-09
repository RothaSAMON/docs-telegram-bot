<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_messages', 'file_url')) {
                $table->string('file_url')->nullable();
            }
            if (!Schema::hasColumn('telegram_messages', 'file_type')) {
                $table->string('file_type')->nullable();
            }
        });
    }
    
    public function down(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'file_type']);
        });
    }
};