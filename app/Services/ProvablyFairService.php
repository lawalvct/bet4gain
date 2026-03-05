<?php

namespace App\Services;

use App\Models\ProvablyFairSeed;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Provably Fair crash point calculation service.
 *
 * Algorithm:
 *   h = HMAC_SHA256(server_seed, client_seed . ':' . nonce)
 *   seed = hexdec(first 8 hex chars of h)
 *   max  = 2^32 = 4294967296
 *   crash = max(1.0, (1 - house_edge) / (1 - seed / max))
 *
 * This is the same algorithm used by Crash / Aviator-style games.
 * A house edge of 3% (0.03) means the expected return is 97%.
 */
class ProvablyFairService
{
    public const HOUSE_EDGE = 0.03;
    public const MAX_SEED   = 4294967296.0; // 2^32

    /**
     * Calculate the crash point for a given seed combination.
     *
     * @param  string  $serverSeed   Raw server seed (not hashed)
     * @param  string  $clientSeed   Client-provided seed
     * @param  int     $nonce        Round nonce
     * @return float                 Crash point (≥ 1.00)
     */
    public static function calculateCrashPoint(
        string $serverSeed,
        string $clientSeed,
        int $nonce = 0
    ): float {
        $hmac = hash_hmac('sha256', "{$clientSeed}:{$nonce}", $serverSeed);

        // Use first 8 hex chars = 32-bit integer
        $seedInt = hexdec(substr($hmac, 0, 8));

        // Apply house edge
        $houseEdge = self::HOUSE_EDGE;
        $crash = (1 - $houseEdge) / (1 - ($seedInt / self::MAX_SEED));

        // Floor to 2 decimal places, minimum 1.00
        $crash = max(1.0, floor($crash * 100) / 100);

        return $crash;
    }

    /**
     * Generate a cryptographically secure server seed.
     */
    public static function generateServerSeed(): string
    {
        return bin2hex(random_bytes(32)); // 64-char hex
    }

    /**
     * Hash a server seed for public commitment (shown before round starts).
     */
    public static function hashServerSeed(string $serverSeed): string
    {
        return hash('sha256', $serverSeed);
    }

    /**
     * Generate a hash chain for future rounds.
     * Each seed is the SHA-256 of the previous one.
     *
     * @param  int  $count  Number of seeds to generate
     */
    public static function generateSeedChain(int $count = 100): array
    {
        $seeds = [];
        $current = self::generateServerSeed();

        for ($i = 0; $i < $count; $i++) {
            $seeds[] = $current;
            $current = hash('sha256', $current);
        }

        return $seeds;
    }

    /**
     * Get or create an active seed pair for a user.
     */
    public static function getActiveSeeds(?User $user): ProvablyFairSeed
    {
        $query = $user
            ? ProvablyFairSeed::where('user_id', $user->id)->where('is_active', true)
            : ProvablyFairSeed::whereNull('user_id')->where('is_active', true);

        return $query->first() ?? self::createNewSeedPair($user);
    }

    /**
     * Create a new active seed pair, deactivate old ones.
     */
    public static function createNewSeedPair(?User $user): ProvablyFairSeed
    {
        // Deactivate old seeds
        $query = $user
            ? ProvablyFairSeed::where('user_id', $user->id)
            : ProvablyFairSeed::whereNull('user_id');

        $query->where('is_active', true)->update(['is_active' => false, 'revealed_at' => now()]);

        $serverSeed = self::generateServerSeed();

        return ProvablyFairSeed::create([
            'user_id'          => $user?->id,
            'server_seed'      => $serverSeed,
            'server_seed_hash' => self::hashServerSeed($serverSeed),
            'client_seed'      => Str::random(16),
            'nonce'            => 0,
            'is_active'        => true,
        ]);
    }

    /**
     * Rotate seeds and increment nonce after a round.
     */
    public static function incrementNonce(ProvablyFairSeed $seed): void
    {
        $seed->increment('nonce');
    }

    /**
     * Reveal seed after use (for public verification).
     */
    public static function revealSeed(ProvablyFairSeed $seed): ProvablyFairSeed
    {
        $seed->update(['revealed_at' => now()]);
        return $seed;
    }

    /**
     * Verify a crash point independently (for provably fair verification page).
     */
    public static function verify(
        string $serverSeed,
        string $serverSeedHash,
        string $clientSeed,
        int $nonce
    ): array {
        $computedHash = self::hashServerSeed($serverSeed);
        $hashMatches  = hash_equals($computedHash, $serverSeedHash);
        $crashPoint   = self::calculateCrashPoint($serverSeed, $clientSeed, $nonce);

        return [
            'hash_matches' => $hashMatches,
            'crash_point'  => $crashPoint,
            'server_seed'  => $serverSeed,
            'client_seed'  => $clientSeed,
            'nonce'        => $nonce,
        ];
    }
}
