<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_status_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('group_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['status_page_id', 'monitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_status_page');
    }
};
