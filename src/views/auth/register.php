<?php
declare(strict_types=1);

// Check for redirect or message handling
if (isset($redirect)) {
    if (!empty($message)) {
        echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
    }
    echo '<script>window.location.href = "' . htmlspecialchars($redirect) . '";</script>';
    return;
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Your Account</h2>
            <p>Join us today! Fill out the form below to get started.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register" class="auth-form" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        value="<?= htmlspecialchars($data['old_input']['first_name'] ?? '') ?>"
                        class="form-control <?= !empty($data['errors']['first_name']) ? 'error' : '' ?>"
                        placeholder="Enter your first name"
                        required
                        autocomplete="given-name"
                    >
                    <?php if (!empty($data['errors']['first_name'])): ?>
                        <div class="error-message"><?= htmlspecialchars($data['errors']['first_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        value="<?= htmlspecialchars($data['old_input']['last_name'] ?? '') ?>"
                        class="form-control <?= !empty($data['errors']['last_name']) ? 'error' : '' ?>"
                        placeholder="Enter your last name"
                        required
                        autocomplete="family-name"
                    >
                    <?php if (!empty($data['errors']['last_name'])): ?>
                        <div class="error-message"><?= htmlspecialchars($data['errors']['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?= htmlspecialchars($data['old_input']['username'] ?? '') ?>"
                    class="form-control <?= !empty($data['errors']['username']) ? 'error' : '' ?>"
                    placeholder="Choose a username"
                    required
                    autocomplete="username"
                    minlength="3"
                    maxlength="30"
                    pattern="[a-zA-Z0-9_]+"
                >
                <div class="field-help">3-30 characters, letters, numbers, and underscores only</div>
                <div class="validation-message" id="username-validation"></div>
                <?php if (!empty($data['errors']['username'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($data['old_input']['email'] ?? '') ?>"
                    class="form-control <?= !empty($data['errors']['email']) ? 'error' : '' ?>"
                    placeholder="Enter your email address"
                    required
                    autocomplete="email"
                >
                <div class="validation-message" id="email-validation"></div>
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
                        placeholder="Create a strong password"
                        required
                        autocomplete="new-password"
                        minlength="8"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <span class="show-text">Show</span>
                        <span class="hide-text" style="display: none;">Hide</span>
                    </button>
                </div>
                
                <div class="password-strength">
                    <div class="strength-meter">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <div class="strength-text" id="strength-text">Enter password to check strength</div>
                </div>

                <div class="password-requirements">
                    <p>Password must contain:</p>
                    <ul>
                        <?php foreach ($data['password_requirements'] as $requirement): ?>
                            <li><?= htmlspecialchars($requirement) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if (!empty($data['errors']['password'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        class="form-control <?= !empty($data['errors']['password_confirmation']) ? 'error' : '' ?>"
                        placeholder="Confirm your password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                        <span class="show-text">Show</span>
                        <span class="hide-text" style="display: none;">Hide</span>
                    </button>
                </div>
                <div class="validation-message" id="password-confirmation-validation"></div>
                <?php if (!empty($data['errors']['password_confirmation'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['password_confirmation']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input 
                        type="checkbox" 
                        id="accept_terms" 
                        name="accept_terms" 
                        value="1"
                        required
                        <?= !empty($data['old_input']['accept_terms']) ? 'checked' : '' ?>
                    >
                    <label for="accept_terms">
                        I agree to the <a href="/terms" target="_blank">Terms of Service</a> 
                        and <a href="/privacy" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                <?php if (!empty($data['errors']['accept_terms'])): ?>
                    <div class="error-message"><?= htmlspecialchars($data['errors']['accept_terms']) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($data['errors']['csrf_token'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($data['errors']['csrf_token']) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                <span class="btn-text">Create Account</span>
                <span class="btn-loading" style="display: none;">Creating Account...</span>
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="/login">Sign in here</a></p>
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
    max-width: 500px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
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

.form-control.success {
    border-color: #27ae60;
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

.field-help {
    font-size: 12px;
    color: #888;
    margin-top: 3px;
}

.validation-message {
    font-size: 12px;
    margin-top: 5px;
}

.validation-message.success {
    color: #27ae60;
}

.validation-message.error {
    color: #e74c3c;
}

.password-strength {
    margin-top: 8px;
}

.strength-meter {
    height: 4px;
    background: #e1e5e9;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-bar {
    height: 100%;
    transition: width 0.3s, background-color 0.3s;
    border-radius: 2px;
}

.strength-text {
    font-size: 12px;
    font-weight: 500;
}

.password-requirements {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 12px;
}

.password-requirements p {
    margin: 0 0 5px 0;
    font-weight: 500;
    color: #666;
}

.password-requirements ul {
    margin: 0;
    padding-left: 15px;
    color: #888;
}

.password-requirements li {
    margin: 2px 0;
}

.checkbox-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.checkbox-wrapper input[type="checkbox"] {
    width: auto;
    margin-top: 3px;
}

.checkbox-wrapper label {
    margin: 0;
    color: #666;
    font-size: 14px;
    cursor: pointer;
    line-height: 1.4;
}

.checkbox-wrapper a {
    color: #667eea;
    text-decoration: none;
}

.checkbox-wrapper a:hover {
    text-decoration: underline;
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

.btn-primary:hover:not(:disabled) {
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

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .auth-card {
        padding: 30px 20px;
    }
    
    .auth-container {
        padding: 10px;
    }
}
</style>

<script>
let usernameTimeout, emailTimeout, passwordTimeout, confirmPasswordTimeout;

// Password visibility toggle
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

// Username availability check
document.getElementById('username').addEventListener('input', function() {
    clearTimeout(usernameTimeout);
    const username = this.value.trim();
    const validation = document.getElementById('username-validation');
    
    if (username.length < 3) {
        validation.textContent = '';
        this.classList.remove('success', 'error');
        return;
    }
    
    usernameTimeout = setTimeout(() => {
        fetch(`/api/check-username?username=${encodeURIComponent(username)}`)
            .then(response => response.json())
            .then(data => {
                validation.textContent = data.message;
                validation.className = `validation-message ${data.available ? 'success' : 'error'}`;
                this.classList.remove('success', 'error');
                this.classList.add(data.available ? 'success' : 'error');
            })
            .catch(() => {
                validation.textContent = 'Unable to check username availability';
                validation.className = 'validation-message error';
            });
    }, 500);
});

// Email availability check
document.getElementById('email').addEventListener('input', function() {
    clearTimeout(emailTimeout);
    const email = this.value.trim();
    const validation = document.getElementById('email-validation');
    
    if (!email || !email.includes('@')) {
        validation.textContent = '';
        this.classList.remove('success', 'error');
        return;
    }
    
    emailTimeout = setTimeout(() => {
        fetch(`/api/check-email?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                validation.textContent = data.message;
                validation.className = `validation-message ${data.available ? 'success' : 'error'}`;
                this.classList.remove('success', 'error');
                this.classList.add(data.available ? 'success' : 'error');
            })
            .catch(() => {
                validation.textContent = 'Unable to check email availability';
                validation.className = 'validation-message error';
            });
    }, 500);
});

// Password strength check
document.getElementById('password').addEventListener('input', function() {
    clearTimeout(passwordTimeout);
    const password = this.value;
    
    if (!password) {
        updatePasswordStrength(0, 'Enter password to check strength', '');
        return;
    }
    
    passwordTimeout = setTimeout(() => {
        fetch('/api/password-strength', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            updatePasswordStrength(data.score, data.strength, data.feedback.join(', '));
        })
        .catch(() => {
            updatePasswordStrength(0, 'Unable to check password strength', '');
        });
    }, 300);
});

// Password confirmation check
document.getElementById('password_confirmation').addEventListener('input', function() {
    clearTimeout(confirmPasswordTimeout);
    const password = document.getElementById('password').value;
    const confirmation = this.value;
    const validation = document.getElementById('password-confirmation-validation');
    
    if (!confirmation) {
        validation.textContent = '';
        this.classList.remove('success', 'error');
        return;
    }
    
    confirmPasswordTimeout = setTimeout(() => {
        if (password === confirmation) {
            validation.textContent = 'Passwords match';
            validation.className = 'validation-message success';
            this.classList.remove('error');
            this.classList.add('success');
        } else {
            validation.textContent = 'Passwords do not match';
            validation.className = 'validation-message error';
            this.classList.remove('success');
            this.classList.add('error');
        }
    }, 300);
});

function updatePasswordStrength(score, strength, feedback) {
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    
    // Update strength bar
    bar.style.width = score + '%';
    
    // Update colors based on strength
    if (score < 30) {
        bar.style.backgroundColor = '#e74c3c';
        text.style.color = '#e74c3c';
    } else if (score < 50) {
        bar.style.backgroundColor = '#f39c12';
        text.style.color = '#f39c12';
    } else if (score < 70) {
        bar.style.backgroundColor = '#f1c40f';
        text.style.color = '#f1c40f';   
    } else if (score < 90) {
        bar.style.backgroundColor = '#2ecc71';
        text.style.color = '#2ecc71';
    } else {
        bar.style.backgroundColor = '#27ae60';
        text.style.color = '#27ae60';
    }
    
    // Update text
    text.textContent = `${strength}${feedback ? ' - ' + feedback : ''}`;
}

// Form submission handling
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    
    // Re-enable button after 10 seconds as failsafe
    setTimeout(() => {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }, 10000);
});
</script>