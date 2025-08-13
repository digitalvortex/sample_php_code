<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Security Monitoring Service
 * 
 * Advanced security monitoring with real-time threat detection,
 * alerting, and comprehensive security metrics.
 * PHP 8.4 compatible with strict typing.
 */
class SecurityMonitoringService
{
    private const METRICS_PREFIX = 'security_metrics:';
    private const ALERT_PREFIX = 'security_alerts:';
    private const THREAT_PREFIX = 'threat_detection:';
    
    private string $storageDir;
    private SecurityLoggerService $logger;
    private BruteForceProtectionService $bruteForceProtection;
    
    // Threat detection thresholds
    private const THREAT_THRESHOLDS = [
        'failed_logins_per_hour' => 50,
        'blacklist_events_per_hour' => 10,
        'suspicious_activities_per_hour' => 25,
        'unique_attacking_ips_per_hour' => 5,
        'scanning_attempts_per_hour' => 20
    ];
    
    public function __construct(
        SecurityLoggerService $logger,
        BruteForceProtectionService $bruteForceProtection,
        ?string $storageDir = null
    ) {
        $this->logger = $logger;
        $this->bruteForceProtection = $bruteForceProtection;
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/security_monitoring';
        $this->ensureStorageDirectory();
    }
    
    /**
     * Record security metric.
     */
    public function recordMetric(string $metric, mixed $value, array $context = []): void
    {
        $timestamp = time();
        $hour = (int)floor($timestamp / 3600);
        
        $metricsFile = $this->getMetricsFile($metric, $hour);
        $metrics = $this->readMetrics($metricsFile);
        
        $metrics[] = [
            'timestamp' => $timestamp,
            'value' => $value,
            'context' => $context
        ];
        
        $this->writeMetrics($metricsFile, $metrics);
        
        // Check for threshold violations
        $this->checkThresholds($metric, $metrics);
    }
    
    /**
     * Get security metrics for a time period.
     */
    public function getMetrics(
        string $metric, 
        int $hoursBack = 24
    ): array {
        $currentHour = (int)floor(time() / 3600);
        $allMetrics = [];
        
        for ($i = 0; $i < $hoursBack; $i++) {
            $hour = $currentHour - $i;
            $metricsFile = $this->getMetricsFile($metric, $hour);
            $hourMetrics = $this->readMetrics($metricsFile);
            
            $allMetrics = array_merge($allMetrics, $hourMetrics);
        }
        
        // Sort by timestamp
        usort($allMetrics, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        return $allMetrics;
    }
    
    /**
     * Generate security dashboard data.
     */
    public function getDashboardData(int $hoursBack = 24): array
    {
        $currentTime = time();
        $startTime = $currentTime - ($hoursBack * 3600);
        
        return [
            'summary' => $this->getSecuritySummary($hoursBack),
            'hourly_breakdown' => $this->getHourlyBreakdown($hoursBack),
            'top_attacking_ips' => $this->getTopAttackingIps($hoursBack),
            'attack_patterns' => $this->getAttackPatterns($hoursBack),
            'threat_level' => $this->calculateThreatLevel($hoursBack),
            'blacklisted_ips' => $this->bruteForceProtection->getBlacklistedIps(),
            'recent_alerts' => $this->getRecentAlerts($hoursBack),
            'generated_at' => $currentTime
        ];
    }
    
    /**
     * Get security summary.
     */
    private function getSecuritySummary(int $hoursBack): array
    {
        $failedLogins = $this->getMetrics('failed_login', $hoursBack);
        $blacklistEvents = $this->getMetrics('ip_blacklisted', $hoursBack);
        $suspiciousActivities = $this->getMetrics('suspicious_activity', $hoursBack);
        $rateLimitViolations = $this->getMetrics('rate_limit_exceeded', $hoursBack);
        
        return [
            'failed_logins' => count($failedLogins),
            'blacklist_events' => count($blacklistEvents),
            'suspicious_activities' => count($suspiciousActivities),
            'rate_limit_violations' => count($rateLimitViolations),
            'unique_attacking_ips' => $this->countUniqueIps($failedLogins),
            'total_security_events' => count($failedLogins) + count($blacklistEvents) + 
                                     count($suspiciousActivities) + count($rateLimitViolations)
        ];
    }
    
    /**
     * Get hourly breakdown of security events.
     */
    private function getHourlyBreakdown(int $hoursBack): array
    {
        $currentHour = (int)floor(time() / 3600);
        $breakdown = [];
        
        for ($i = 0; $i < $hoursBack; $i++) {
            $hour = $currentHour - $i;
            $hourStart = $hour * 3600;
            
            $breakdown[] = [
                'hour' => $hourStart,
                'hour_label' => date('Y-m-d H:00', $hourStart),
                'failed_logins' => count($this->getMetricsForHour('failed_login', $hour)),
                'blacklist_events' => count($this->getMetricsForHour('ip_blacklisted', $hour)),
                'suspicious_activities' => count($this->getMetricsForHour('suspicious_activity', $hour)),
                'rate_limit_violations' => count($this->getMetricsForHour('rate_limit_exceeded', $hour))
            ];
        }
        
        return array_reverse($breakdown);
    }
    
    /**
     * Get top attacking IP addresses.
     */
    private function getTopAttackingIps(int $hoursBack, int $limit = 10): array
    {
        $failedLogins = $this->getMetrics('failed_login', $hoursBack);
        $ipCounts = [];
        
        foreach ($failedLogins as $event) {
            $ip = $event['context']['ip'] ?? 'unknown';
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
        }
        
        arsort($ipCounts);
        
        $topIps = [];
        $count = 0;
        foreach ($ipCounts as $ip => $attempts) {
            if ($count >= $limit) break;
            
            $topIps[] = [
                'ip' => $ip,
                'attempts' => $attempts,
                'is_blacklisted' => $this->bruteForceProtection->isBlacklisted($ip),
                'threat_score' => $this->calculateIpThreatScore($ip, $hoursBack)
            ];
            $count++;
        }
        
        return $topIps;
    }
    
    /**
     * Get attack patterns analysis.
     */
    private function getAttackPatterns(int $hoursBack): array
    {
        $events = $this->getMetrics('suspicious_activity', $hoursBack);
        $patterns = [
            'brute_force' => 0,
            'scanning' => 0,
            'user_agent_rotation' => 0,
            'distributed_attack' => 0,
            'credential_stuffing' => 0
        ];
        
        foreach ($events as $event) {
            $activity = $event['context']['activity'] ?? '';
            
            switch ($activity) {
                case 'authentication_failure':
                    $patterns['brute_force']++;
                    break;
                case 'endpoint_scanning':
                    $patterns['scanning']++;
                    break;
                case 'user_agent_rotation':
                    $patterns['user_agent_rotation']++;
                    break;
                case 'distributed_attack':
                    $patterns['distributed_attack']++;
                    break;
                case 'credential_stuffing':
                    $patterns['credential_stuffing']++;
                    break;
            }
        }
        
        return $patterns;
    }
    
    /**
     * Calculate overall threat level.
     */
    private function calculateThreatLevel(int $hoursBack): array
    {
        $summary = $this->getSecuritySummary($hoursBack);
        $blacklistedCount = count($this->bruteForceProtection->getBlacklistedIps());
        
        $score = 0;
        $factors = [];
        
        // Factor in failed logins
        if ($summary['failed_logins'] > 100) {
            $score += 30;
            $factors[] = 'High number of failed logins';
        } elseif ($summary['failed_logins'] > 50) {
            $score += 15;
            $factors[] = 'Moderate failed login activity';
        }
        
        // Factor in blacklisted IPs
        if ($blacklistedCount > 10) {
            $score += 25;
            $factors[] = 'Multiple blacklisted IPs';
        } elseif ($blacklistedCount > 5) {
            $score += 10;
            $factors[] = 'Several blacklisted IPs';
        }
        
        // Factor in unique attacking IPs
        if ($summary['unique_attacking_ips'] > 20) {
            $score += 20;
            $factors[] = 'Distributed attack detected';
        } elseif ($summary['unique_attacking_ips'] > 10) {
            $score += 10;
            $factors[] = 'Multiple attacking sources';
        }
        
        // Factor in rate limit violations
        if ($summary['rate_limit_violations'] > 200) {
            $score += 15;
            $factors[] = 'High rate limit violations';
        }
        
        // Determine threat level
        if ($score >= 70) {
            $level = 'critical';
            $color = '#dc2626';
        } elseif ($score >= 40) {
            $level = 'high';
            $color = '#ea580c';
        } elseif ($score >= 20) {
            $level = 'medium';
            $color = '#d97706';
        } elseif ($score >= 10) {
            $level = 'low';
            $color = '#65a30d';
        } else {
            $level = 'minimal';
            $color = '#16a34a';
        }
        
        return [
            'level' => $level,
            'score' => $score,
            'color' => $color,
            'factors' => $factors,
            'recommendation' => $this->getThreatRecommendation($level)
        ];
    }
    
    /**
     * Create security alert.
     */
    public function createAlert(
        string $type, 
        string $severity, 
        string $message, 
        array $context = []
    ): void {
        $alert = [
            'id' => uniqid(),
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'created_at' => time(),
            'acknowledged' => false
        ];
        
        $alertFile = $this->getAlertFile();
        $alerts = $this->readAlerts($alertFile);
        $alerts[] = $alert;
        
        // Keep only recent alerts (last 7 days)
        $cutoff = time() - (7 * 86400);
        $alerts = array_filter($alerts, fn($a) => $a['created_at'] > $cutoff);
        
        $this->writeAlerts($alertFile, $alerts);
        
        $this->logger->logSecurityEvent('SECURITY_ALERT_CREATED', [
            'alert_id' => $alert['id'],
            'type' => $type,
            'severity' => $severity,
            'message' => $message
        ]);
    }
    
    /**
     * Get recent alerts.
     */
    public function getRecentAlerts(int $hoursBack = 24, int $limit = 50): array
    {
        $alertFile = $this->getAlertFile();
        $alerts = $this->readAlerts($alertFile);
        
        $cutoff = time() - ($hoursBack * 3600);
        $recentAlerts = array_filter($alerts, fn($a) => $a['created_at'] > $cutoff);
        
        // Sort by creation time (most recent first)
        usort($recentAlerts, fn($a, $b) => $b['created_at'] <=> $a['created_at']);
        
        return array_slice($recentAlerts, 0, $limit);
    }
    
    /**
     * Check threshold violations and create alerts.
     */
    private function checkThresholds(string $metric, array $metrics): void
    {
        $hourMetrics = array_filter($metrics, fn($m) => $m['timestamp'] > time() - 3600);
        $count = count($hourMetrics);
        
        $thresholdKey = $metric . '_per_hour';
        $threshold = self::THREAT_THRESHOLDS[$thresholdKey] ?? null;
        
        if ($threshold && $count >= $threshold) {
            $this->createAlert(
                'threshold_violation',
                'high',
                "Threshold exceeded: {$count} {$metric} events in the last hour (threshold: {$threshold})",
                [
                    'metric' => $metric,
                    'count' => $count,
                    'threshold' => $threshold,
                    'hour' => date('Y-m-d H:00')
                ]
            );
        }
    }
    
    /**
     * Calculate threat score for specific IP.
     */
    private function calculateIpThreatScore(string $ip, int $hoursBack): int
    {
        $score = 0;
        
        // Check failed login attempts
        $failedLogins = $this->getMetrics('failed_login', $hoursBack);
        $ipFailures = array_filter($failedLogins, fn($e) => ($e['context']['ip'] ?? '') === $ip);
        $score += count($ipFailures) * 2;
        
        // Check if blacklisted
        if ($this->bruteForceProtection->isBlacklisted($ip)) {
            $score += 50;
        }
        
        // Check suspicious activities
        $suspicious = $this->getMetrics('suspicious_activity', $hoursBack);
        $ipSuspicious = array_filter($suspicious, fn($e) => ($e['context']['ip'] ?? '') === $ip);
        $score += count($ipSuspicious) * 5;
        
        return min(100, $score); // Cap at 100
    }
    
    /**
     * Get threat recommendation based on level.
     */
    private function getThreatRecommendation(string $level): string
    {
        return match ($level) {
            'critical' => 'Immediate action required. Consider implementing emergency security measures.',
            'high' => 'Enhanced monitoring recommended. Review and strengthen security policies.',
            'medium' => 'Monitor closely. Consider adjusting rate limiting thresholds.',
            'low' => 'Normal security posture. Continue regular monitoring.',
            'minimal' => 'Security status is good. No immediate action required.',
            default => 'Continue monitoring security metrics.'
        };
    }
    
    /**
     * Count unique IP addresses in events.
     */
    private function countUniqueIps(array $events): int
    {
        $ips = array_unique(array_map(fn($e) => $e['context']['ip'] ?? 'unknown', $events));
        return count(array_filter($ips, fn($ip) => $ip !== 'unknown'));
    }
    
    /**
     * Get metrics for specific hour.
     */
    private function getMetricsForHour(string $metric, int $hour): array
    {
        $metricsFile = $this->getMetricsFile($metric, $hour);
        return $this->readMetrics($metricsFile);
    }
    
    /**
     * Clean up old metrics and alerts.
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        
        // Clean old metric files (older than 7 days)
        $metricFiles = glob($this->storageDir . '/metrics_*.json');
        if ($metricFiles !== false) {
            $cutoff = time() - (7 * 86400);
            foreach ($metricFiles as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        // Clean old alerts
        $alertFile = $this->getAlertFile();
        if (file_exists($alertFile)) {
            $alerts = $this->readAlerts($alertFile);
            $originalCount = count($alerts);
            
            $cutoff = time() - (7 * 86400);
            $alerts = array_filter($alerts, fn($a) => $a['created_at'] > $cutoff);
            
            $this->writeAlerts($alertFile, $alerts);
            $cleaned += $originalCount - count($alerts);
        }
        
        return $cleaned;
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
     * Get metrics file path.
     */
    private function getMetricsFile(string $metric, int $hour): string
    {
        return $this->storageDir . '/metrics_' . $metric . '_' . $hour . '.json';
    }
    
    /**
     * Get alert file path.
     */
    private function getAlertFile(): string
    {
        return $this->storageDir . '/alerts.json';
    }
    
    /**
     * Read metrics from file.
     */
    private function readMetrics(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        return $data['metrics'] ?? [];
    }
    
    /**
     * Write metrics to file.
     */
    private function writeMetrics(string $file, array $metrics): void
    {
        $data = [
            'metrics' => $metrics,
            'updated_at' => time()
        ];
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Read alerts from file.
     */
    private function readAlerts(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        return $data['alerts'] ?? [];
    }
    
    /**
     * Write alerts to file.
     */
    private function writeAlerts(string $file, array $alerts): void
    {
        $data = [
            'alerts' => $alerts,
            'updated_at' => time()
        ];
        file_put_contents($file, json_encode($data));
    }
}