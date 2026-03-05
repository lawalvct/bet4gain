<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_round_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 4);
            $table->string('currency', 10)->default('COINS')->comment('COINS or DEMO');
            $table->decimal('auto_cashout_at', 10, 4)->nullable();
            $table->decimal('cashed_out_at', 10, 4)->nullable();
            $table->decimal('payout', 16, 4)->nullable();
            $table->boolean('is_auto')->default(false)->comment('Was this auto-cashout?');
            $table->string('status')->default('pending')->comment('pending, active, won, lost, cancelled');
            $table->unsignedTinyInteger('bet_slot')->default(1)->comment('1 or 2 for dual bet support');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['game_round_id', 'status']);
            $table->unique(['user_id', 'game_round_id', 'bet_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
