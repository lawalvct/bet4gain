<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_bet_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('bet_amount', 16, 4);
            $table->decimal('auto_cashout_at', 10, 4)->default(2.00);
            $table->decimal('stop_on_loss', 16, 4)->nullable();
            $table->decimal('stop_on_profit', 16, 4)->nullable();
            $table->decimal('increase_on_loss_percent', 5, 2)->nullable();
            $table->decimal('increase_on_win_percent', 5, 2)->nullable();
            $table->unsignedInteger('max_rounds')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_bet_configs');
    }
};
