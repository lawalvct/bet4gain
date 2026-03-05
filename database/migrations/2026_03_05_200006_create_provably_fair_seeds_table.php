<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provably_fair_seeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('server_seed');
            $table->string('server_seed_hash');
            $table->string('client_seed');
            $table->unsignedInteger('nonce')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provably_fair_seeds');
    }
};
