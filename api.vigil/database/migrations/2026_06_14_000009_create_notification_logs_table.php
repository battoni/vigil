<?php

use App\Modules\Incident\Enums\IncidentEvent;
use App\Modules\Notification\Enums\NotificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('notification_channels')->nullOnDelete();
            $table->enum('event', IncidentEvent::values());
            $table->enum('status', NotificationStatus::values());
            $table->string('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('incident_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
