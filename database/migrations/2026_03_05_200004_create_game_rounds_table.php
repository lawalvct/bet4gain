<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('round_hash')->unique()->comment('Provably fair hash');
            $table->string('server_seed');
            $table->string('client_seed')->nullable();
            $table->unsignedInteger('nonce')->default(0);
            $table->decimal('crash_point', 10, 4);
            $table->string('status')->default('waiting')->comment('waiting, betting, running, crashed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('crashed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('crashed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
