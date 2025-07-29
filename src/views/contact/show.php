<div class="container">
    <div class="contact-page">
        <!-- Contact Header -->
        <div class="contact-header text-center mb-8">
            <h1><?= trans('pages.contact.header.title') ?></h1>
            <p class="section-subtitle"><?= trans('pages.contact.header.subtitle') ?></p>
        </div>

        <div class="contact-content">
            <!-- Contact Info -->
            <div class="contact-info">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-6"><?= trans('pages.contact.info.title') ?></h3>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div>
                                <h4><?= trans('pages.contact.info.address.title') ?></h4>
                                <p><?= trans('pages.contact.info.address.value') ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                </svg>
                            </div>
                            <div>
                                <h4><?= trans('pages.contact.info.phone.title') ?></h4>
                                <p><?= trans('pages.contact.info.phone.value') ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div>
                                <h4><?= trans('pages.contact.info.email.title') ?></h4>
                                <p><?= trans('pages.contact.info.email.value') ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12,6 12,12 16,14"/>
                                </svg>
                            </div>
                            <div>
                                <h4><?= trans('pages.contact.info.hours.title') ?></h4>
                                <p><?= trans('pages.contact.info.hours.value') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-section">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-6"><?= trans('pages.contact.form.title') ?></h3>
                        
                        <form method="post" action="/contact/submit" class="contact-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="form-group">
                                <label for="name" class="form-label"><?= trans('pages.contact.form.name_label') ?></label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    class="form-input <?= isset($errors['name']) ? 'error' : '' ?>" 
                                    value="<?= htmlspecialchars($old['name'] ?? '') ?>" 
                                    required
                                    placeholder="<?= trans('pages.contact.form.name_placeholder') ?>"
                                >
                                <?php if (isset($errors['name'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['name']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label"><?= trans('pages.contact.form.email_label') ?></label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-input <?= isset($errors['email']) ? 'error' : '' ?>" 
                                    value="<?= htmlspecialchars($old['email'] ?? '') ?>" 
                                    required
                                    placeholder="<?= trans('pages.contact.form.email_placeholder') ?>"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['email']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label"><?= trans('pages.contact.form.message_label') ?></label>
                                <textarea 
                                    id="message" 
                                    name="message" 
                                    class="form-input form-textarea <?= isset($errors['message']) ? 'error' : '' ?>" 
                                    required
                                    placeholder="<?= trans('pages.contact.form.message_placeholder') ?>"
                                ><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                                <?php if (isset($errors['message'])): ?>
                                    <span class="form-error"><?= htmlspecialchars($errors['message']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg w-full">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"/>
                                        <polygon points="22,2 15,22 11,13 2,9 22,2"/>
                                    </svg>
                                    <?= trans('pages.contact.form.submit_button') ?>
                                </button>
                            </div>
                            
                            <p class="form-disclaimer">
                                <?= trans('pages.contact.form.disclaimer') ?>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>