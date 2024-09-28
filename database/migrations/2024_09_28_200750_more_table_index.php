<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['categoryId']);
        });

        Schema::table('user_devices', function (Blueprint $table) {
            $table->index(['userId']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['categoryId']);
        });

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex(['userId']);
        });
    }
};