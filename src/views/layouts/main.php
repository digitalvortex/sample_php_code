<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $params['title'] ?? 'Default Title' ?></title>
    <meta name="description" content="<?= $params['metaDescription'] ?? '' ?>">
    <link rel="stylesheet" href="/css/main.css">
    <script src="/js/main.js" defer></script>
</head>

<body>
    <div class="page-wrapper">
        <header>
            <div class="container">
                <a href="/" class="logo">
                    <span class="logo-text">SampleSite</span>
                </a>
                <nav>
                    <button class="menu-toggle" aria-label="Toggle Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <ul class="nav-menu">
                        <li><a href="/">Home</a></li>
                        <li><a href="/about">About</a></li>
                        <li><a href="/services">Services</a></li>
                        <li><a href="/blog">Blog</a></li>
                        <li><a href="/contact">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main>
            <div class="container">
                <?= $content ?>
            </div>
        </main>

        <footer>
            <div class="container">
                <p>&copy; <?= date('Y') ?> SampleSite. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>

</html>