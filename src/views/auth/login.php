<?php
declare(strict_types=1);

// Check for redirect or message handling
if (isset($data['redirect'])) {
    if (!empty($data['message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($data['message']) . '</div>';
    }
    echo '<script>window.location.href = "' . htmlspecialchars($data['redirect']) . '";</script>';
    return;
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Login to Your Account</h2>
            <p>Welcome back! Please sign in to continue.</p>
        </div>

        <?php if (!empty($data['message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($data['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['errors']['general'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($data['errors']['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token']) ?>">
            
            <?php if (!empty($data['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($data['redirect']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email or Username</label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($data['old_input']['email'] ?? '') ?>"
                    class="form-control <?= !empty($data['errors']['email']) ? 'error' : '' ?>"
                    placeholder="Enter your email or username"
                    required
                    autocomplete="username"
                >
                <?php if (!empty($data['errors']['email'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control <?= !empty($data['errors']['password']) ? 'error' : '' ?>"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <span class="show-text">Show</span>
                        <span class="hide-text" style="display: none;">Hide</span>
                    </button>
                </div>
                <?php if (!empty($data['errors']['password'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me" 
                        value="1"
                        <?= !empty($data['old_input']['remember_me']) ? 'checked' : '' ?>
                    >
                    <label for="remember_me">Remember me for 30 days</label>
                </div>
            </div>

            <?php if (!empty($data['errors']['csrf_token'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($data['errors']['csrf_token']) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-full">
                <span class="btn-text">Sign In</span>
                <span class="btn-loading" style="display: none;">Signing in...</span>
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="/register">Create one here</a></p>
            <p><a href="/password/reset">Forgot your password?</a></p>
        </div>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.auth-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    padding: 40px;
    width: 100%;
    max-width: 400px;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header h2 {
    color: #333;
    margin-bottom: 10px;
    font-size: 24px;
    font-weight: 600;
}

.auth-header p {
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.form-control.error {
    border-color: #e74c3c;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 12px;
    padding: 5px;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-wrapper input[type="checkbox"] {
    width: auto;
}

.checkbox-wrapper label {
    margin: 0;
    color: #666;
    font-size: 14px;
    cursor: pointer;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-full {
    width: 100%;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.error-message {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
}

.auth-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.auth-footer p {
    margin: 8px 0;
    font-size: 14px;
    color: #666;
}

.auth-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 30px 20px;
    }
    
    .auth-container {
        padding: 10px;
    }
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const showText = button.querySelector('.show-text');
    const hideText = button.querySelector('.hide-text');
    
    if (field.type === 'password') {
        field.type = 'text';
        showText.style.display = 'none';
        hideText.style.display = 'inline';
    } else {
        field.type = 'password';
        showText.style.display = 'inline';
        hideText.style.display = 'none';
    }
}

// Form submission handling
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    
    // Re-enable button after 5 seconds as failsafe
    setTimeout(() => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }, 5000);
});
</script>