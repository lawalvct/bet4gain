<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period')->comment('daily, weekly, monthly, alltime');
            $table->decimal('total_wagered', 16, 4)->default(0);
            $table->decimal('total_won', 16, 4)->default(0);
            $table->decimal('total_profit', 16, 4)->default(0);
            $table->decimal('best_multiplier', 10, 4)->default(0);
            $table->unsignedInteger('total_games')->default(0);
            $table->unsignedInteger('win_count')->default(0);
            $table->timestamp('calculated_at')->nullable();

            $table->unique(['user_id', 'period']);
            $table->index(['period', 'total_profit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
