<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->timestamp('sent_at');
            $table->unique(['moderator_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_deliveries');
    }
};
