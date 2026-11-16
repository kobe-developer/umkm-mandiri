<?php

namespace App\Services;

use App\Models\User;
use App\Support\BusinessRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Verifikasi PIN owner untuk persetujuan void/refund oleh kasir.
 * Percobaan salah dibatasi per kasir dan secara global (semua kasir),
 * dengan lockout panjang, dan dicatat di log aktivitas.
 */
class OwnerPinVerifier
{
    private const GLOBAL_KEY = 'owner-pin:global';

    public function __construct(private readonly ActivityLogger $logger) {}

    public function verify(?string $pin, User $requester): User
    {
        $userKey = 'owner-pin:'.$requester->id;
        $this->assertNotLocked($userKey, config('store.pin_max_attempts'));
        $this->assertNotLocked(self::GLOBAL_KEY, config('store.pin_max_attempts_global'));

        $owner = $pin === null ? null : User::owners()->active()->whereNotNull('pin_hash')->get()
            ->first(fn (User $owner) => Hash::check($pin, $owner->pin_hash));

        if (! $owner) {
            $this->recordFailure($userKey, $requester);
            BusinessRule::fail('PIN owner salah.', 'pin');
        }

        RateLimiter::clear($userKey);

        return $owner;
    }

    private function assertNotLocked(string $key, int $maxAttempts): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            BusinessRule::fail("Terlalu banyak percobaan PIN salah. Coba lagi dalam {$minutes} menit atau minta owner melakukan void.", 'pin');
        }
    }

    private function recordFailure(string $userKey, User $requester): void
    {
        $lockout = config('store.pin_lockout_seconds');
        RateLimiter::hit($userKey, $lockout);
        RateLimiter::hit(self::GLOBAL_KEY, $lockout);

        $this->logger->log('pin_failed', $requester, null, null, "PIN owner salah dimasukkan oleh {$requester->username}");
        Log::warning('Owner PIN verification failed', ['user_id' => $requester->id]);
    }
}
