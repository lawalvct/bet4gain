<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 10: Security, Anti-Cheat & Responsible Gaming columns.
     */
    public function up(): void
    {
        // ── Users table: responsible gaming + security fields ──
        Schema::table('users', function (Blueprint $table) {
            // Responsible gaming
            $table->boolean('self_excluded')->default(false)->after('is_banned');
            $table->timestamp('self_excluded_until')->nullable()->after('self_excluded');
            $table->decimal('daily_deposit_limit', 16, 2)->nullable()->after('self_excluded_until');
            $table->decimal('weekly_deposit_limit', 16, 2)->nullable()->after('daily_deposit_limit');
            $table->decimal('monthly_deposit_limit', 16, 2)->nullable()->after('weekly_deposit_limit');
            $table->decimal('daily_bet_limit', 16, 2)->nullable()->after('monthly_deposit_limit');
            $table->timestamp('cooldown_until')->nullable()->after('daily_bet_limit');

            // Security tracking
            $table->string('last_login_ip', 45)->nullable()->after('last_seen_at');
            $table->string('registration_ip', 45)->nullable()->after('last_login_ip');
            $table->text('browser_fingerprint')->nullable()->after('registration_ip');
            $table->boolean('is_flagged')->default(false)->after('browser_fingerprint');
            $table->text('flag_reason')->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');
        });

        // ── Login history / IP tracking table ──
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('browser_fingerprint')->nullable();
            $table->boolean('successful')->default(true);
            $table->string('failure_reason')->nullable();
            $table->string('country', 3)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index('browser_fingerprint');
        });

        // ── Suspicious activity flags ──
        Schema::create('suspicious_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->comment('multi_account, win_streak, rapid_withdrawal, bot_behavior, ip_change');
            $table->string('severity', 20)->default('medium')->comment('low, medium, high, critical');
            $table->json('details')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['severity', 'reviewed']);
            $table->index('created_at');
        });

        // ── IP whitelist/blacklist ──
        Schema::create('ip_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('type', 10)->comment('whitelist or blacklist');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['ip_address', 'type']);
            $table->index('type');
        });

        // ── Deposit tracking for responsible gaming limits ──
        Schema::create('deposit_limits_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->string('period', 10)->comment('daily, weekly, monthly');
            $table->date('period_date');
            $table->timestamps();

            $table->index(['user_id', 'period', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_limits_tracking');
        Schema::dropIfExists('ip_restrictions');
        Schema::dropIfExists('suspicious_activities');
        Schema::dropIfExists('login_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'self_excluded',
                'self_excluded_until',
                'daily_deposit_limit',
                'weekly_deposit_limit',
                'monthly_deposit_limit',
                'daily_bet_limit',
                'cooldown_until',
                'last_login_ip',
                'registration_ip',
                'browser_fingerprint',
                'is_flagged',
                'flag_reason',
                'flagged_at',
            ]);
        });
    }
};
