<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_uptime_hourly', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_id');
            $table->dateTime('bucket_start');
            $table->unsignedInteger('checks_total')->default(0);
            $table->unsignedInteger('checks_up')->default(0);
            $table->decimal('uptime_ratio', 5, 4)->default(0);
            $table->unsignedInteger('p50_ms')->nullable();
            $table->unsignedInteger('p95_ms')->nullable();
            $table->unsignedInteger('max_ms')->nullable();
            $table->timestamps();

            $table->unique(['monitor_id', 'bucket_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_uptime_hourly');
    }
};
