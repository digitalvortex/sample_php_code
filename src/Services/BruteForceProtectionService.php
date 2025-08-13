<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Brute Force Protection Service
 * 
 * Advanced protection against brute force attacks with pattern detection,
 * automatic IP blacklisting, and sophisticated attack analysis.
 * PHP 8.4 compatible with strict typing.
 */
class BruteForceProtectionService
{
    private const BLACKLIST_PREFIX = 'blacklist:';
    private const ATTACK_PATTERN_PREFIX = 'attack_pattern:';
    private const SUSPICIOUS_ACTIVITY_PREFIX = 'suspicious:';
    
    private string $storageDir;
    private SecurityLoggerService $logger;
    
    public function __construct(
        SecurityLoggerService $logger,
        ?string $storageDir = null
    ) {
        $this->logger = $logger;
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/security';
        $this->ensureStorageDirectory();
    }
    
    /**
     * Check if an IP address is blacklisted.
     */
    public function isBlacklisted(string $ip): bool
    {
        $blacklistFile = $this->getBlacklistFile($ip);
        
        if (!file_exists($blacklistFile)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($blacklistFile), true);
        if (!$data) {
            return false;
        }
        
        $now = time();
        $expiresAt = $data['expires_at'] ?? 0;
        
        // Check if blacklist has expired
        if ($expiresAt > 0 && $now > $expiresAt) {
            unlink($blacklistFile);
            return false;
        }
        
        return true;
    }
    
    /**
     * Add IP address to blacklist.
     */
    public function blacklistIp(
        string $ip, 
        string $reason, 
        int $durationSeconds = 3600
    ): void {
        $blacklistFile = $this->getBlacklistFile($ip);
        $now = time();
        
        $data = [
            'ip' => $ip,
            'blacklisted_at' => $now,
            'expires_at' => $durationSeconds > 0 ? $now + $durationSeconds : 0,
            'reason' => $reason,
            'permanent' => $durationSeconds === 0
        ];
        
        file_put_contents($blacklistFile, json_encode($data));
        
        $this->logger->logSecurityEvent('IP_BLACKLISTED', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_seconds' => $durationSeconds,
            'expires_at' => $data['expires_at']
        ]);
    }
    
    /**
     * Remove IP address from blacklist.
     */
    public function removeFromBlacklist(string $ip): bool
    {
        $blacklistFile = $this->getBlacklistFile($ip);
        
        if (!file_exists($blacklistFile)) {
            return false;
        }
        
        unlink($blacklistFile);
        
        $this->logger->logSecurityEvent('IP_UNBLACKLISTED', [
            'ip' => $ip
        ]);
        
        return true;
    }
    
    /**
     * Analyze attack patterns and determine if IP should be blacklisted.
     */
    public function analyzeAttackPattern(
        string $ip, 
        string $endpoint, 
        string $userAgent
    ): array {
        $pattern = $this->getAttackPattern($ip);
        $now = time();
        
        // Add current attempt
        $pattern['attempts'][] = [
            'timestamp' => $now,
            'endpoint' => $endpoint,
            'user_agent' => $userAgent
        ];
        
        // Keep only attempts from last 24 hours
        $cutoff = $now - 86400;
        $pattern['attempts'] = array_filter(
            $pattern['attempts'], 
            fn($attempt) => $attempt['timestamp'] > $cutoff
        );
        
        // Update pattern data
        $pattern['last_attempt'] = $now;
        $pattern['total_attempts'] = count($pattern['attempts']);
        
        // Analyze for suspicious patterns
        $analysis = $this->performPatternAnalysis($pattern);
        
        // Update stored pattern
        $this->storeAttackPattern($ip, $pattern);
        
        return $analysis;
    }
    
    /**
     * Perform detailed pattern analysis.
     */
    private function performPatternAnalysis(array $pattern): array
    {
        $analysis = [
            'threat_level' => 'low',
            'should_blacklist' => false,
            'blacklist_duration' => 0,
            'reasons' => []
        ];
        
        $attempts = $pattern['attempts'];
        $totalAttempts = count($attempts);
        $now = time();
        
        if ($totalAttempts === 0) {
            return $analysis;
        }
        
        // Check for high frequency attacks (many attempts in short time)
        $recentAttempts = array_filter(
            $attempts, 
            fn($attempt) => $now - $attempt['timestamp'] < 300 // Last 5 minutes
        );
        
        if (count($recentAttempts) >= 20) {
            $analysis['threat_level'] = 'critical';
            $analysis['should_blacklist'] = true;
            $analysis['blacklist_duration'] = 7200; // 2 hours
            $analysis['reasons'][] = 'High frequency attack detected';
        } elseif (count($recentAttempts) >= 10) {
            $analysis['threat_level'] = 'high';
            $analysis['should_blacklist'] = true;
            $analysis['blacklist_duration'] = 3600; // 1 hour
            $analysis['reasons'][] = 'Rapid attack pattern detected';
        }
        
        // Check for persistent attacks over time
        $hourlyAttempts = [];
        foreach ($attempts as $attempt) {
            $hour = (int)floor($attempt['timestamp'] / 3600);
            $hourlyAttempts[$hour] = ($hourlyAttempts[$hour] ?? 0) + 1;
        }
        
        $hoursWithAttempts = count($hourlyAttempts);
        if ($hoursWithAttempts >= 6 && $totalAttempts >= 30) {
            $analysis['threat_level'] = 'high';
            $analysis['should_blacklist'] = true;
            $analysis['blacklist_duration'] = 14400; // 4 hours
            $analysis['reasons'][] = 'Persistent attack over multiple hours';
        }
        
        // Check for endpoint diversity (scanning behavior)
        $uniqueEndpoints = array_unique(array_column($attempts, 'endpoint'));
        if (count($uniqueEndpoints) >= 5 && $totalAttempts >= 15) {
            $analysis['threat_level'] = 'medium';
            $analysis['reasons'][] = 'Scanning behavior detected';
            
            if ($analysis['threat_level'] !== 'high' && $analysis['threat_level'] !== 'critical') {
                $analysis['should_blacklist'] = true;
                $analysis['blacklist_duration'] = 1800; // 30 minutes
            }
        }
        
        // Check for user agent rotation (bot behavior)
        $uniqueUserAgents = array_unique(array_column($attempts, 'user_agent'));
        if (count($uniqueUserAgents) >= 3 && $totalAttempts >= 10) {
            $analysis['reasons'][] = 'Multiple user agents detected';
            
            if ($analysis['threat_level'] === 'low') {
                $analysis['threat_level'] = 'medium';
            }
        }
        
        // Check for authentication endpoint targeting
        $authAttempts = array_filter(
            $attempts, 
            fn($attempt) => str_contains($attempt['endpoint'], '/login') || 
                          str_contains($attempt['endpoint'], '/register')
        );
        
        if (count($authAttempts) >= 10) {
            $analysis['reasons'][] = 'Authentication endpoint targeting';
            
            if ($analysis['threat_level'] === 'low') {
                $analysis['threat_level'] = 'medium';
            }
            
            if (!$analysis['should_blacklist'] && count($authAttempts) >= 15) {
                $analysis['should_blacklist'] = true;
                $analysis['blacklist_duration'] = 2700; // 45 minutes
            }
        }
        
        return $analysis;
    }
    
    /**
     * Record suspicious activity for monitoring.
     */
    public function recordSuspiciousActivity(
        string $ip, 
        string $activity, 
        array $context = []
    ): void {
        $activityFile = $this->getSuspiciousActivityFile($ip);
        $activities = $this->readSuspiciousActivities($activityFile);
        
        $activities[] = [
            'timestamp' => time(),
            'activity' => $activity,
            'context' => $context
        ];
        
        // Keep only activities from last 7 days
        $cutoff = time() - (7 * 86400);
        $activities = array_filter(
            $activities, 
            fn($act) => $act['timestamp'] > $cutoff
        );
        
        $this->writeSuspiciousActivities($activityFile, $activities);
        
        $this->logger->logSecurityEvent('SUSPICIOUS_ACTIVITY', [
            'ip' => $ip,
            'activity' => $activity,
            'context' => $context
        ]);
    }
    
    /**
     * Get attack pattern for IP address.
     */
    private function getAttackPattern(string $ip): array
    {
        $patternFile = $this->getAttackPatternFile($ip);
        
        if (!file_exists($patternFile)) {
            return [
                'ip' => $ip,
                'first_seen' => time(),
                'last_attempt' => 0,
                'total_attempts' => 0,
                'attempts' => []
            ];
        }
        
        $data = json_decode(file_get_contents($patternFile), true);
        return $data ?: [
            'ip' => $ip,
            'first_seen' => time(),
            'last_attempt' => 0,
            'total_attempts' => 0,
            'attempts' => []
        ];
    }
    
    /**
     * Store attack pattern for IP address.
     */
    private function storeAttackPattern(string $ip, array $pattern): void
    {
        $patternFile = $this->getAttackPatternFile($ip);
        file_put_contents($patternFile, json_encode($pattern));
    }
    
    /**
     * Get all blacklisted IPs.
     */
    public function getBlacklistedIps(): array
    {
        $blacklisted = [];
        $files = glob($this->storageDir . '/blacklist_*.json');
        
        if ($files === false) {
            return [];
        }
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && !$this->isExpired($data)) {
                $blacklisted[] = $data;
            }
        }
        
        return $blacklisted;
    }
    
    /**
     * Clean up expired blacklist entries and old data.
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        
        // Clean blacklist entries
        $blacklistFiles = glob($this->storageDir . '/blacklist_*.json');
        if ($blacklistFiles !== false) {
            foreach ($blacklistFiles as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && $this->isExpired($data)) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        // Clean old attack patterns (older than 7 days)
        $patternFiles = glob($this->storageDir . '/attack_pattern_*.json');
        if ($patternFiles !== false) {
            $cutoff = time() - (7 * 86400);
            foreach ($patternFiles as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Check if blacklist entry is expired.
     */
    private function isExpired(array $data): bool
    {
        if ($data['permanent'] ?? false) {
            return false;
        }
        
        $expiresAt = $data['expires_at'] ?? 0;
        return $expiresAt > 0 && time() > $expiresAt;
    }
    
    /**
     * Ensure storage directory exists.
     */
    private function ensureStorageDirectory(): void
    {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Get blacklist file path for IP.
     */
    private function getBlacklistFile(string $ip): string
    {
        $key = self::BLACKLIST_PREFIX . $ip;
        return $this->storageDir . '/blacklist_' . md5($key) . '.json';
    }
    
    /**
     * Get attack pattern file path for IP.
     */
    private function getAttackPatternFile(string $ip): string
    {
        $key = self::ATTACK_PATTERN_PREFIX . $ip;
        return $this->storageDir . '/attack_pattern_' . md5($key) . '.json';
    }
    
    /**
     * Get suspicious activity file path for IP.
     */
    private function getSuspiciousActivityFile(string $ip): string
    {
        $key = self::SUSPICIOUS_ACTIVITY_PREFIX . $ip;
        return $this->storageDir . '/suspicious_' . md5($key) . '.json';
    }
    
    /**
     * Read suspicious activities from file.
     */
    private function readSuspiciousActivities(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        return $data['activities'] ?? [];
    }
    
    /**
     * Write suspicious activities to file.
     */
    private function writeSuspiciousActivities(string $file, array $activities): void
    {
        $data = [
            'activities' => $activities,
            'updated_at' => time()
        ];
        file_put_contents($file, json_encode($data));
    }
}