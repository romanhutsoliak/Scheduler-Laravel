<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('userId');
            $table->string('deviceId');
            $table->string('platform')->nullable()->default(null);
            $table->string('manufacturer')->nullable()->default(null);
            $table->string('model')->nullable()->default(null);
            $table->string('appVersion')->nullable()->default(null);
            $table->string('notificationToken');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_devices');
    }
};
