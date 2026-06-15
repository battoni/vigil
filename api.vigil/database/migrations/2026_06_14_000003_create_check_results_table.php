<?php

use App\Modules\Monitor\Enums\CheckOutcome;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * High-volume table. Deliberately has NO foreign key on monitor_id: MySQL
     * forbids foreign keys on partitioned tables (partitioning is applied by a
     * later MySQL-only migration). Referential integrity is enforced in
     * CheckResultRepository. See PLAN.md §3 and §11.
     */
    public function up(): void
    {
        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_id');
            $table->dateTime('checked_at');
            $table->enum('result', CheckOutcome::values());
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('error')->nullable();
            $table->string('region')->default('local');

            $table->index(['monitor_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
