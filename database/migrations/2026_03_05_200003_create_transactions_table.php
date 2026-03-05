<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('deposit, withdrawal, purchase_coins, sell_coins, bet, win, refund, bonus');
            $table->decimal('amount', 16, 4);
            $table->string('currency', 10)->default('NGN')->comment('NGN or COINS');
            $table->string('reference')->unique();
            $table->string('gateway')->nullable()->comment('paystack, nomba');
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending')->comment('pending, completed, failed, reversed');
            $table->json('metadata')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
