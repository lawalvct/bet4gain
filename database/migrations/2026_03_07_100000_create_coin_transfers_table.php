<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 16, 4);
            $table->decimal('fee', 16, 4)->default(0);
            $table->decimal('net_amount', 16, 4)->comment('Amount recipient receives after fee');
            $table->string('reference', 64)->unique();
            $table->enum('type', ['gift', 'transfer'])->default('transfer');
            $table->string('note', 255)->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transfers');
    }
};
