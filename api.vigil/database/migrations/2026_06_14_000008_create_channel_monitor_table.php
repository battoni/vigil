<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_monitor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('notification_channels')->cascadeOnDelete();
            $table->string('min_severity')->nullable();
            $table->boolean('notify_on_recovery')->default(true);
            $table->timestamps();

            $table->unique(['monitor_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_monitor');
    }
};
