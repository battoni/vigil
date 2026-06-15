<?php

use App\Modules\Monitor\Enums\MonitorStatus;
use App\Modules\Monitor\Enums\MonitorType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', MonitorType::values());
            $table->string('target');
            $table->json('config')->nullable();
            $table->unsignedInteger('interval_seconds')->default(60);
            $table->unsignedInteger('timeout_ms')->default(10000);
            $table->unsignedTinyInteger('confirmation_threshold')->default(2);
            $table->unsignedTinyInteger('recovery_threshold')->default(1);
            $table->enum('status', MonitorStatus::values())->default(MonitorStatus::PENDING->value);
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('consecutive_successes')->default(0);
            $table->string('heartbeat_token')->nullable()->unique();
            $table->dateTime('last_ping_at')->nullable();
            $table->dateTime('last_checked_at')->nullable();
            $table->dateTime('next_check_at')->nullable();
            $table->dateTime('leased_until')->nullable();
            $table->timestamps();

            $table->index('next_check_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
