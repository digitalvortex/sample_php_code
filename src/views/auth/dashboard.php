<?php
declare(strict_types=1);

$user = $data['user'];
$sessionInfo = $data['session_info'];
$fullName = $data['full_name'];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($user->getAttribute('first_name')) ?>!</h1>
            <p>Here's your account overview and recent activity.</p>
        </div>
        
        <div class="user-actions">
            <a href="/profile" class="btn btn-secondary">Edit Profile</a>
            <a href="/logout" class="btn btn-outline">Sign Out</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- User Information Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Account Information</h3>
                <span class="status-badge active">Active</span>
            </div>
            <div class="card-content">
                <div class="info-row">
                    <span class="label">Full Name:</span>
                    <span class="value"><?= htmlspecialchars($fullName) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Username:</span>
                    <span class="value"><?= htmlspecialchars($user->getAttribute('username')) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?= htmlspecialchars($user->getAttribute('email')) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Member Since:</span>
                    <span class="value">
                        <?php 
                        $createdAt = $user->getAttribute('created_at');
                        echo $createdAt ? date('F j, Y', strtotime($createdAt)) : 'Unknown';
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Session Information Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Current Session</h3>
                <span class="status-badge secure">Secure</span>
            </div>
            <div class="card-content">
                <div class="info-row">
                    <span class="label">Login Time:</span>
                    <span class="value">
                        <?php 
                        echo $sessionInfo['login_time'] ? 
                            date('M j, Y g:i A', $sessionInfo['login_time']) : 'Unknown';
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Last Activity:</span>
                    <span class="value">
                        <?php 
                        echo $sessionInfo['last_activity'] ? 
                            date('M j, Y g:i A', $sessionInfo['last_activity']) : 'Unknown';
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Session Expires:</span>
                    <span class="value">
                        <?php 
                        if ($sessionInfo['time_remaining'] > 0) {
                            $minutes = floor($sessionInfo['time_remaining'] / 60);
                            echo "In {$minutes} minutes";
                        } else {
                            echo 'Expired';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">IP Address:</span>
                    <span class="value"><?= htmlspecialchars($sessionInfo['ip_address'] ?? 'Unknown') ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-content">
                <div class="action-grid">
                    <a href="/profile" class="action-item">
                        <div class="action-icon">👤</div>
                        <div class="action-text">
                            <h4>Edit Profile</h4>
                            <p>Update your personal information</p>
                        </div>
                    </a>
                    
                    <a href="/password/change" class="action-item">
                        <div class="action-icon">🔒</div>
                        <div class="action-text">
                            <h4>Change Password</h4>
                            <p>Update your account security</p>
                        </div>
                    </a>
                    
                    <a href="/api-keys" class="action-item">
                        <div class="action-icon">🔑</div>
                        <div class="action-text">
                            <h4>API Keys</h4>
                            <p>Manage your API access</p>
                        </div>
                    </a>
                    
                    <a href="/sessions" class="action-item">
                        <div class="action-icon">📱</div>
                        <div class="action-text">
                            <h4>Active Sessions</h4>
                            <p>View and manage sessions</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <a href="/activity" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon success">✓</div>
                        <div class="activity-content">
                            <div class="activity-title">Successful login</div>
                            <div class="activity-meta">
                                <?= date('M j, Y g:i A', $sessionInfo['login_time'] ?? time()) ?> • 
                                IP: <?= htmlspecialchars($sessionInfo['ip_address'] ?? 'Unknown') ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon info">ℹ</div>
                        <div class="activity-content">
                            <div class="activity-title">Account created</div>
                            <div class="activity-meta">
                                <?php 
                                $createdAt = $user->getAttribute('created_at');
                                echo $createdAt ? date('M j, Y g:i A', strtotime($createdAt)) : 'Unknown time';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Warning Modal (if session is expiring soon) -->
    <?php if (($sessionInfo['time_remaining'] ?? 0) < 300 && ($sessionInfo['time_remaining'] ?? 0) > 0): ?>
    <div class="session-warning" id="sessionWarning">
        <div class="warning-content">
            <h4>Session Expiring Soon</h4>
            <p>Your session will expire in <?= floor($sessionInfo['time_remaining'] / 60) ?> minutes. Would you like to extend it?</p>
            <div class="warning-actions">
                <button onclick="extendSession()" class="btn btn-primary btn-sm">Extend Session</button>
                <button onclick="closeWarning()" class="btn btn-outline btn-sm">Dismiss</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.welcome-section h1 {
    color: #333;
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 600;
}

.welcome-section p {
    color: #666;
    margin: 0;
    font-size: 16px;
}

.user-actions {
    display: flex;
    gap: 10px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.full-width {
    grid-column: 1 / -1;
}

.card-header {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.card-content {
    padding: 20px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.secure {
    background: #cce5ff;
    color: #004085;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    color: #666;
    font-weight: 500;
}

.info-row .value {
    color: #333;
    font-weight: 400;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.action-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.action-item:hover {
    border-color: #667eea;
    background: #f8f9ff;
    transform: translateY(-1px);
}

.action-icon {
    font-size: 24px;
    margin-right: 15px;
    width: 40px;
    text-align: center;
}

.action-text h4 {
    margin: 0 0 4px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
}

.action-text p {
    margin: 0;
    color: #666;
    font-size: 12px;
}

.activity-list {
    space-y: 15px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 15px 0;
    border-bottom: 1px solid #f1f3f4;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 16px;
    font-weight: bold;
}

.activity-icon.success {
    background: #d4edda;
    color: #155724;
}

.activity-icon.info {
    background: #cce5ff;
    color: #004085;
}

.activity-title {
    font-weight: 500;
    color: #333;
    margin-bottom: 4px;
}

.activity-meta {
    color: #666;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid #e1e5e9;
    color: #666;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.session-warning {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    max-width: 300px;
}

.warning-content h4 {
    margin: 0 0 10px 0;
    color: #856404;
    font-size: 16px;
}

.warning-content p {
    margin: 0 0 15px 0;
    color: #856404;
    font-size: 14px;
}

.warning-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .user-actions {
        justify-content: flex-start;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .session-warning {
        left: 10px;
        right: 10px;
        max-width: none;
    }
}
</style>

<script>
// Session management
let sessionCheckInterval;

// Start checking session status
function startSessionCheck() {
    sessionCheckInterval = setInterval(checkSession, 60000); // Check every minute
}

// Check session status
function checkSession() {
    fetch('/api/session-check')
        .then(response => response.json())
        .then(data => {
            if (!data.authenticated) {
                // Session expired, redirect to login
                alert('Your session has expired. Please log in again.');
                window.location.href = '/login';
            }
        })
        .catch(error => {
            console.warn('Session check failed:', error);
        });
}

// Extend session
function extendSession() {
    fetch('/api/session-check')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                document.getElementById('sessionWarning').style.display = 'none';
                // Optionally show success message
            }
        })
        .catch(error => {
            console.warn('Session extension failed:', error);
        });
}

// Close warning
function closeWarning() {
    document.getElementById('sessionWarning').style.display = 'none';
}

// Initialize session checking
document.addEventListener('DOMContentLoaded', function() {
    startSessionCheck();
});

// Cleanup interval on page unload
window.addEventListener('beforeunload', function() {
    if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
    }
});
</script>