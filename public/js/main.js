document.addEventListener('DOMContentLoaded', () => {
    // Mobile navigation toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', navMenu.classList.contains('active'));
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close menu when pressing Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.focus();
            }
        });
    }

    // Language selector functionality
    const languageButton = document.getElementById('language-button');
    const languageDropdown = document.getElementById('language-dropdown');
    
    if (languageButton && languageDropdown) {
        let isDropdownOpen = false;
        
        // Toggle dropdown on button click
        languageButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleLanguageDropdown();
        });
        
        // Handle language selection
        languageDropdown.addEventListener('click', (e) => {
            const languageOption = e.target.closest('.language-option');
            if (languageOption && !languageOption.classList.contains('active')) {
                e.preventDefault();
                const locale = languageOption.dataset.locale;
                switchLanguage(locale, languageOption.href);
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!languageButton.contains(e.target) && !languageDropdown.contains(e.target)) {
                closeLanguageDropdown();
            }
        });
        
        // Handle keyboard navigation
        languageButton.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    toggleLanguageDropdown();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    openLanguageDropdown();
                    focusFirstLanguageOption();
                    break;
                case 'Escape':
                    closeLanguageDropdown();
                    break;
            }
        });
        
        languageDropdown.addEventListener('keydown', (e) => {
            const options = languageDropdown.querySelectorAll('.language-option');
            const currentIndex = Array.from(options).indexOf(document.activeElement);
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex < options.length - 1 ? currentIndex + 1 : 0;
                    options[nextIndex].focus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : options.length - 1;
                    options[prevIndex].focus();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (document.activeElement.classList.contains('language-option')) {
                        const locale = document.activeElement.dataset.locale;
                        switchLanguage(locale, document.activeElement.href);
                    }
                    break;
                case 'Escape':
                    closeLanguageDropdown();
                    languageButton.focus();
                    break;
            }
        });
        
        function toggleLanguageDropdown() {
            if (isDropdownOpen) {
                closeLanguageDropdown();
            } else {
                openLanguageDropdown();
            }
        }
        
        function openLanguageDropdown() {
            languageDropdown.classList.add('show');
            languageButton.setAttribute('aria-expanded', 'true');
            isDropdownOpen = true;
        }
        
        function closeLanguageDropdown() {
            languageDropdown.classList.remove('show');
            languageButton.setAttribute('aria-expanded', 'false');
            isDropdownOpen = false;
        }
        
        function focusFirstLanguageOption() {
            const firstOption = languageDropdown.querySelector('.language-option');
            if (firstOption) {
                firstOption.focus();
            }
        }
        
        function switchLanguage(locale, fallbackUrl) {
            // Show loading state
            const currentLanguageSpan = languageButton.querySelector('.current-language');
            const originalText = currentLanguageSpan.textContent;
            currentLanguageSpan.textContent = '...';
            
            // Make AJAX request if fetch is available
            if (typeof fetch !== 'undefined') {
                fetch(`/language/switch/${locale}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        // Update current language display
                        currentLanguageSpan.textContent = locale.toUpperCase();
                        
                        // Close dropdown
                        closeLanguageDropdown();
                        
                        // Redirect to the new URL
                        window.location.href = data.redirect_url;
                    } else {
                        throw new Error('Language switch failed');
                    }
                })
                .catch(error => {
                    console.warn('AJAX language switch failed, falling back to page navigation:', error);
                    currentLanguageSpan.textContent = originalText;
                    window.location.href = fallbackUrl;
                });
            } else {
                // Fallback to direct navigation
                window.location.href = fallbackUrl;
            }
        }
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Form enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('.form-input');
        
        inputs.forEach(input => {
            // Add floating label effect
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
            });

            // Check if input has value on load
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    });

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);

    // Observe all elements with fade-in animations
    document.querySelectorAll('.animate-fadeInUp, .animate-fadeIn').forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });

    // Header scroll effect
    let lastScrollY = window.scrollY;
    const header = document.querySelector('header');
    
    if (header) {
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
            
            lastScrollY = currentScrollY;
        });
    }

    // Contact form enhancements
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            // Add loading state to submit button
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                    Sending...
                `;
                
                // Re-enable after 5 seconds to prevent permanent disability
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22,2 15,22 11,13 2,9 22,2"/>
                        </svg>
                        Send Message
                    `;
                }, 5000);
            }
        });
    }

    // Add CSS for spinner animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .form-group.focused .form-label {
            transform: translateY(-8px) scale(0.9);
            color: var(--primary-600);
        }
        
        .form-label {
            transition: all 0.2s ease;
            transform-origin: left top;
        }
    `;
    document.head.appendChild(style);
});