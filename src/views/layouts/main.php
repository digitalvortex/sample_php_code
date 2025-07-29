<!DOCTYPE html>
<html lang="<?= function_exists('get_locale') ? get_locale() : 'en' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($params['title'] ?? 'SampleSite - Modern PHP MVC Framework') ?></title>
    <meta name="description" content="<?= htmlspecialchars($params['metaDescription'] ?? 'A modern PHP MVC framework demonstration with clean architecture and responsive design.') ?>">
    
    <!-- Preconnect to Google Fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Inter font for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="/css/main.css">
    
    <!-- Favicon and PWA meta tags -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="theme-color" content="#0ea5e9">
    
    <!-- Open Graph meta tags for social sharing -->
    <meta property="og:title" content="<?= htmlspecialchars($params['title'] ?? 'SampleSite') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($params['metaDescription'] ?? 'A modern PHP MVC framework demonstration') ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SampleSite">
    
    <!-- JavaScript -->
    <script src="/js/main.js" defer></script>
</head>

<body>
    <div class="page-wrapper">
        <!-- Modern Header with Glassmorphism -->
        <header>
            <div class="container">
                <a href="/" class="logo" aria-label="SampleSite Home">
                    <span class="logo-text">SampleSite</span>
                </a>
                
                <nav role="navigation" aria-label="Main navigation">
                    <button class="menu-toggle" aria-label="Toggle navigation menu" id="mobile-menu-button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    
                    <ul class="nav-menu" id="nav-menu">
                        <li><a href="/" <?= ($_SERVER['REQUEST_URI'] === '/') ? 'class="active"' : '' ?>><?= function_exists('trans') ? trans('common.home') : 'Home' ?></a></li>
                        <li><a href="/about" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/about')) ? 'class="active"' : '' ?>><?= function_exists('trans') ? trans('common.about') : 'About' ?></a></li>
                        <li><a href="/services" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/services')) ? 'class="active"' : '' ?>><?= function_exists('trans') ? trans('common.services') : 'Services' ?></a></li>
                        <li><a href="/blog" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/blog')) ? 'class="active"' : '' ?>><?= function_exists('trans') ? trans('common.blog') : 'Blog' ?></a></li>
                        <li><a href="/contact" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/contact')) ? 'class="active"' : '' ?>><?= function_exists('trans') ? trans('common.contact') : 'Contact' ?></a></li>
                        
                        <!-- Language Selector -->
                        <li class="language-selector">
                            <button class="language-toggle" aria-label="Select language" id="language-button">
                                <span class="language-icon">🌐</span>
                                <span class="current-language"><?= function_exists('get_locale') ? strtoupper(get_locale()) : 'EN' ?></span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <ul class="language-dropdown" id="language-dropdown">
                                <?php
                                $supportedLocales = [
                                    'en' => ['name' => 'English', 'native' => 'English'],
                                    'fr' => ['name' => 'French', 'native' => 'Français'],
                                    'es' => ['name' => 'Spanish', 'native' => 'Español'],
                                    'de' => ['name' => 'German', 'native' => 'Deutsch'],
                                    'it' => ['name' => 'Italian', 'native' => 'Italiano']
                                ];
                                $currentLocale = function_exists('get_locale') ? get_locale() : 'en';
                                
                                foreach ($supportedLocales as $locale => $info):
                                    $isActive = $locale === $currentLocale;
                                ?>
                                <li>
                                    <a href="/language/switch/<?= $locale ?>" 
                                       class="language-option <?= $isActive ? 'active' : '' ?>"
                                       data-locale="<?= $locale ?>"
                                       aria-label="Switch to <?= $info['name'] ?>">
                                        <span class="language-code"><?= strtoupper($locale) ?></span>
                                        <span class="language-name"><?= $info['native'] ?></span>
                                        <?php if ($isActive): ?>
                                        <span class="active-indicator">✓</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- Main Content Area -->
        <main role="main">
            <?= $content ?>
        </main>

        <!-- Modern Footer -->
        <footer role="contentinfo">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3 class="footer-title"><?= trans('common.footer.brand') ?></h3>
                        <p class="footer-description">
                            <?= trans('common.footer.description') ?>
                        </p>
                    </div>
                    
                    <div class="footer-section">
                        <h4 class="footer-subtitle"><?= trans('common.footer.quick_links') ?></h4>
                        <ul class="footer-links">
                            <li><a href="/"><?= trans('common.home') ?></a></li>
                            <li><a href="/about"><?= trans('common.about') ?></a></li>
                            <li><a href="/services"><?= trans('common.services') ?></a></li>
                            <li><a href="/blog"><?= trans('common.blog') ?></a></li>
                            <li><a href="/contact"><?= trans('common.contact') ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4 class="footer-subtitle"><?= trans('common.footer.technology') ?></h4>
                        <ul class="footer-links">
                            <li><?= trans('common.footer.technologies.php') ?></li>
                            <li><?= trans('common.footer.technologies.mvc') ?></li>
                            <li><?= trans('common.footer.technologies.di') ?></li>
                            <li><?= trans('common.footer.technologies.responsive') ?></li>
                            <li><?= trans('common.footer.technologies.css') ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p><?= trans('common.footer.copyright', ['year' => date('Y')]) ?></p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link"><?= trans('common.footer.skip_to_content') ?></a>
</body>

</html>