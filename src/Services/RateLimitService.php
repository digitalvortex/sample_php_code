<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\RequestInterface;

/**
 * Rate Limiting Service
 * 
 * Provides comprehensive rate limiting functionality with multiple algorithms,
 * storage backends, and advanced features like progressive penalties.
 * PHP 8.4 compatible with strict typing.
 */
class RateLimitService
{
    private const STORAGE_PREFIX = 'rate_limit:';
    private const PENALTY_PREFIX = 'rate_penalty:';
    private const ATTEMPT_PREFIX = 'rate_attempts:';

    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/rate_limits';
        $this->ensureStorageDirectory();
    }

    /**
     * Check if a client has exceeded their rate limit.
     */
    public function isAllowed(
        string $clientId, 
        int $maxRequests, 
        int $windowSeconds, 
        string $algorithm = 'sliding_window'
    ): bool {
        return match ($algorithm) {
            'fixed_window' => $this->checkFixedWindow($clientId, $maxRequests, $windowSeconds),
            'sliding_window' => $this->checkSlidingWindow($clientId, $maxRequests, $windowSeconds),
            'token_bucket' => $this->checkTokenBucket($clientId, $maxRequests, $windowSeconds),
            default => throw new \InvalidArgumentException("Unknown algorithm: {$algorithm}")
        };
    }

    /**
     * Record a request for rate limiting.
     */
    public function recordRequest(
        string $clientId, 
        int $maxRequests, 
        int $windowSeconds, 
        string $algorithm = 'sliding_window'
    ): void {
        match ($algorithm) {
            'fixed_window' => $this->recordFixedWindow($clientId, $windowSeconds),
            'sliding_window' => $this->recordSlidingWindow($clientId, $windowSeconds),
            'token_bucket' => $this->recordTokenBucket($clientId, $maxRequests, $windowSeconds),
            default => throw new \InvalidArgumentException("Unknown algorithm: {$algorithm}")
        };
    }

    /**
     * Get current rate limit status.
     */
    public function getStatus(
        string $clientId, 
        int $maxRequests, 
        int $windowSeconds, 
        string $algorithm = 'sliding_window'
    ): array {
        $current = match ($algorithm) {
            'fixed_window' => $this->getCurrentFixedWindow($clientId, $windowSeconds),
            'sliding_window' => $this->getCurrentSlidingWindow($clientId, $windowSeconds),
            'token_bucket' => $this->getCurrentTokenBucket($clientId, $maxRequests, $windowSeconds),
            default => 0
        };

        $remaining = max(0, $maxRequests - $current);
        $resetTime = $this->getResetTime($clientId, $windowSeconds, $algorithm);

        return [
            'limit' => $maxRequests,
            'current' => $current,
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'retry_after' => max(0, $resetTime - time())
        ];
    }

    /**
     * Apply progressive penalties for repeated violations.
     */
    public function applyPenalty(string $clientId, int $baseWindowSeconds): int {
        $penaltyFile = $this->getPenaltyFile($clientId);
        $penalties = $this->readPenaltyData($penaltyFile);
        
        $now = time();
        $penalties[] = $now;
        
        // Remove penalties older than 24 hours
        $penalties = array_filter($penalties, fn($time) => $now - $time < 86400);
        
        $this->writePenaltyData($penaltyFile, $penalties);
        
        // Calculate penalty multiplier based on recent violations
        $recentViolations = count(array_filter($penalties, fn($time) => $now - $time < 3600));
        $multiplier = min(8, pow(2, $recentViolations - 1)); // Max 8x penalty
        
        return (int)($baseWindowSeconds * $multiplier);
    }

    /**
     * Record a failed authentication attempt.
     */
    public function recordFailedAttempt(string $clientId): void {
        $attemptFile = $this->getAttemptFile($clientId);
        $attempts = $this->readAttemptData($attemptFile);
        
        $now = time();
        $attempts[] = $now;
        
        // Keep only attempts from the last hour
        $attempts = array_filter($attempts, fn($time) => $now - $time < 3600);
        
        $this->writeAttemptData($attemptFile, $attempts);
    }

    /**
     * Get failed attempt count for progressive rate limiting.
     */
    public function getFailedAttemptCount(string $clientId): int {
        $attemptFile = $this->getAttemptFile($clientId);
        $attempts = $this->readAttemptData($attemptFile);
        
        $now = time();
        // Count attempts from the last hour
        return count(array_filter($attempts, fn($time) => $now - $time < 3600));
    }

    /**
     * Clear rate limit data for a client.
     */
    public function clearLimits(string $clientId): void {
        $patterns = [
            self::STORAGE_PREFIX . $clientId . ':fixed',
            self::STORAGE_PREFIX . $clientId . ':sliding',
            self::STORAGE_PREFIX . $clientId . ':bucket',
            self::PENALTY_PREFIX . $clientId,
            self::ATTEMPT_PREFIX . $clientId
        ];

        foreach ($patterns as $pattern) {
            $file = $this->storageDir . '/' . md5($pattern) . '.json';
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Clean up expired rate limit data.
     */
    public function cleanup(): int {
        $cleaned = 0;
        $files = glob($this->storageDir . '/*.json');
        
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400) { // Older than 24 hours
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Fixed window rate limiting implementation.
     */
    private function checkFixedWindow(string $clientId, int $maxRequests, int $windowSeconds): bool {
        $current = $this->getCurrentFixedWindow($clientId, $windowSeconds);
        return $current < $maxRequests;
    }

    private function getCurrentFixedWindow(string $clientId, int $windowSeconds): int {
        $key = self::STORAGE_PREFIX . $clientId . ':fixed';
        $file = $this->getStorageFile($key);
        
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return 0;
        }

        $currentWindowStart = (int)(floor(time() / $windowSeconds) * $windowSeconds);
        
        if (($data['window_start'] ?? 0) !== $currentWindowStart) {
            return 0;
        }

        return $data['count'] ?? 0;
    }

    private function recordFixedWindow(string $clientId, int $windowSeconds): void {
        $key = self::STORAGE_PREFIX . $clientId . ':fixed';
        $file = $this->getStorageFile($key);
        
        $windowStart = (int)(floor(time() / $windowSeconds) * $windowSeconds);
        $current = $this->getCurrentFixedWindow($clientId, $windowSeconds);
        
        $data = [
            'window_start' => $windowStart,
            'count' => $current + 1,
            'updated_at' => time()
        ];

        file_put_contents($file, json_encode($data));
    }

    /**
     * Sliding window rate limiting implementation.
     */
    private function checkSlidingWindow(string $clientId, int $maxRequests, int $windowSeconds): bool {
        $current = $this->getCurrentSlidingWindow($clientId, $windowSeconds);
        return $current < $maxRequests;
    }

    private function getCurrentSlidingWindow(string $clientId, int $windowSeconds): int {
        $key = self::STORAGE_PREFIX . $clientId . ':sliding';
        $file = $this->getStorageFile($key);
        
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['requests'])) {
            return 0;
        }

        $cutoff = time() - $windowSeconds;
        $validRequests = array_filter($data['requests'], fn($timestamp) => $timestamp > $cutoff);
        
        return count($validRequests);
    }

    private function recordSlidingWindow(string $clientId, int $windowSeconds): void {
        $key = self::STORAGE_PREFIX . $clientId . ':sliding';
        $file = $this->getStorageFile($key);
        
        $data = ['requests' => []];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && isset($existing['requests'])) {
                $data = $existing;
            }
        }

        $now = time();
        $cutoff = $now - $windowSeconds;
        
        // Remove old requests and add new one
        $data['requests'] = array_filter($data['requests'], fn($timestamp) => $timestamp > $cutoff);
        $data['requests'][] = $now;
        $data['updated_at'] = $now;

        file_put_contents($file, json_encode($data));
    }

    /**
     * Token bucket rate limiting implementation.
     */
    private function checkTokenBucket(string $clientId, int $maxRequests, int $windowSeconds): bool {
        $bucket = $this->getTokenBucket($clientId, $maxRequests, $windowSeconds);
        return $bucket['tokens'] > 0;
    }

    private function getCurrentTokenBucket(string $clientId, int $maxRequests, int $windowSeconds): int {
        $bucket = $this->getTokenBucket($clientId, $maxRequests, $windowSeconds);
        return $maxRequests - (int)$bucket['tokens'];
    }

    private function recordTokenBucket(string $clientId, int $maxRequests, int $windowSeconds): void {
        $bucket = $this->getTokenBucket($clientId, $maxRequests, $windowSeconds);
        $bucket['tokens'] = max(0, $bucket['tokens'] - 1);
        $bucket['updated_at'] = time();

        $key = self::STORAGE_PREFIX . $clientId . ':bucket';
        $file = $this->getStorageFile($key);
        file_put_contents($file, json_encode($bucket));
    }

    private function getTokenBucket(string $clientId, int $maxRequests, int $windowSeconds): array {
        $key = self::STORAGE_PREFIX . $clientId . ':bucket';
        $file = $this->getStorageFile($key);
        
        $now = time();
        $refillRate = $maxRequests / $windowSeconds; // tokens per second
        
        if (!file_exists($file)) {
            return [
                'tokens' => (float)$maxRequests,
                'updated_at' => $now
            ];
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return [
                'tokens' => (float)$maxRequests,
                'updated_at' => $now
            ];
        }

        // Refill tokens based on time elapsed
        $elapsed = $now - $data['updated_at'];
        $tokensToAdd = $elapsed * $refillRate;
        $data['tokens'] = min($maxRequests, $data['tokens'] + $tokensToAdd);
        $data['updated_at'] = $now;

        return $data;
    }

    /**
     * Get reset time for rate limit window.
     */
    private function getResetTime(string $clientId, int $windowSeconds, string $algorithm): int {
        return match ($algorithm) {
            'fixed_window' => (int)(ceil(time() / $windowSeconds) * $windowSeconds),
            'sliding_window' => time() + $windowSeconds,
            'token_bucket' => time() + $windowSeconds,
            default => time() + $windowSeconds
        };
    }

    /**
     * Ensure storage directory exists.
     */
    private function ensureStorageDirectory(): void {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Get storage file path for a key.
     */
    private function getStorageFile(string $key): string {
        return $this->storageDir . '/' . md5($key) . '.json';
    }

    /**
     * Get penalty file path for a client.
     */
    private function getPenaltyFile(string $clientId): string {
        $key = self::PENALTY_PREFIX . $clientId;
        return $this->getStorageFile($key);
    }

    /**
     * Get attempt file path for a client.
     */
    private function getAttemptFile(string $clientId): string {
        $key = self::ATTEMPT_PREFIX . $clientId;
        return $this->getStorageFile($key);
    }

    /**
     * Read penalty data from file.
     */
    private function readPenaltyData(string $file): array {
        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return $data['penalties'] ?? [];
    }

    /**
     * Write penalty data to file.
     */
    private function writePenaltyData(string $file, array $penalties): void {
        $data = [
            'penalties' => $penalties,
            'updated_at' => time()
        ];
        file_put_contents($file, json_encode($data));
    }

    /**
     * Read attempt data from file.
     */
    private function readAttemptData(string $file): array {
        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return $data['attempts'] ?? [];
    }

    /**
     * Write attempt data to file.
     */
    private function writeAttemptData(string $file, array $attempts): void {
        $data = [
            'attempts' => $attempts,
            'updated_at' => time()
        ];
        file_put_contents($file, json_encode($data));
    }
}