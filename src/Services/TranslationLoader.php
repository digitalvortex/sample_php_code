<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\TranslationLoaderInterface;

/**
 * Class TranslationLoader
 * 
 * Loads translation files from JSON format with caching and compilation support.
 * Supports multiple file formats and performance optimization strategies.
 * PHP 8.4 compatible with strict typing and comprehensive error handling.
 */
class TranslationLoader implements TranslationLoaderInterface
{
    private string $basePath;
    private string $compiledPath;
    private array $cache = [];
    private bool $useCompiled;
    private bool $cacheEnabled;

    public function __construct(array $config = [])
    {
        $this->basePath = $config['base_path'] ?? __DIR__ . '/../../resources/lang';
        $this->compiledPath = $config['compiled_path'] ?? __DIR__ . '/../../resources/compiled/translations';
        $this->useCompiled = $config['use_compiled'] ?? true;
        $this->cacheEnabled = $config['cache_enabled'] ?? true;
        
        // Ensure directories exist
        $this->ensureDirectoryExists($this->basePath);
        $this->ensureDirectoryExists($this->compiledPath);
    }

    public function load(string $locale, string $domain): array
    {
        $cacheKey = "{$locale}.{$domain}";
        
        // Return from memory cache if available
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        // Try to load from compiled cache first
        if ($this->useCompiled && $this->isCompiled($locale, $domain)) {
            $translations = $this->loadCompiled($locale, $domain);
        } else {
            $translations = $this->loadFromSource($locale, $domain);
        }
        
        // Cache in memory
        if ($this->cacheEnabled) {
            $this->cache[$cacheKey] = $translations;
        }
        
        return $translations;
    }

    public function exists(string $locale, string $domain): bool
    {
        // Check compiled version first
        if ($this->useCompiled && $this->isCompiled($locale, $domain)) {
            return file_exists($this->getCompiledPath($locale, $domain));
        }
        
        // Check source file
        return file_exists($this->getSourcePath($locale, $domain));
    }

    public function getAvailableDomains(string $locale): array
    {
        $localePath = $this->getLocalePath($locale);
        
        if (!is_dir($localePath)) {
            return [];
        }
        
        $domains = [];
        $files = scandir($localePath);
        
        if ($files === false) {
            return [];
        }
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension === 'json') {
                $domains[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        
        return $domains;
    }

    public function getAvailableLocales(): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }
        
        $locales = [];
        $directories = scandir($this->basePath);
        
        if ($directories === false) {
            return [];
        }
        
        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($this->basePath . '/' . $dir)) {
                continue;
            }
            
            // Validate locale format (basic validation)
            if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $dir)) {
                $locales[] = $dir;
            }
        }
        
        return $locales;
    }

    public function getLastModified(string $locale, string $domain): int
    {
        $sourcePath = $this->getSourcePath($locale, $domain);
        
        if (!file_exists($sourcePath)) {
            return 0;
        }
        
        $mtime = filemtime($sourcePath);
        return $mtime !== false ? $mtime : 0;
    }

    public function preloadLocale(string $locale): array
    {
        $domains = $this->getAvailableDomains($locale);
        $translations = [];
        
        foreach ($domains as $domain) {
            try {
                $translations[$domain] = $this->load($locale, $domain);
            } catch (\RuntimeException) {
                // Skip domains that fail to load
                continue;
            }
        }
        
        return $translations;
    }

    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
        $this->ensureDirectoryExists($this->basePath);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function compile(string $locale, ?string $domain = null): bool
    {
        if ($domain !== null) {
            return $this->compileDomain($locale, $domain);
        }
        
        // Compile all domains for locale
        $domains = $this->getAvailableDomains($locale);
        $success = true;
        
        foreach ($domains as $domainName) {
            if (!$this->compileDomain($locale, $domainName)) {
                $success = false;
            }
        }
        
        return $success;
    }

    public function isCompiled(string $locale, string $domain): bool
    {
        $compiledPath = $this->getCompiledPath($locale, $domain);
        $sourcePath = $this->getSourcePath($locale, $domain);
        
        // Check if compiled version exists
        if (!file_exists($compiledPath)) {
            return false;
        }
        
        // Check if source is newer than compiled version
        if (file_exists($sourcePath)) {
            $sourceTime = filemtime($sourcePath);
            $compiledTime = filemtime($compiledPath);
            
            return $compiledTime !== false && $sourceTime !== false && $compiledTime >= $sourceTime;
        }
        
        return true;
    }

    /**
     * Load translations from source JSON file.
     */
    private function loadFromSource(string $locale, string $domain): array
    {
        $filePath = $this->getSourcePath($locale, $domain);
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Translation file not found: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read translation file: {$filePath}");
        }
        
        $translations = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in translation file: {$filePath}. Error: " . json_last_error_msg());
        }
        
        return $translations ?? [];
    }

    /**
     * Load translations from compiled PHP file.
     */
    private function loadCompiled(string $locale, string $domain): array
    {
        $filePath = $this->getCompiledPath($locale, $domain);
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Compiled translation file not found: {$filePath}");
        }
        
        $translations = include $filePath;
        
        if (!is_array($translations)) {
            throw new \RuntimeException("Invalid compiled translation file: {$filePath}");
        }
        
        return $translations;
    }

    /**
     * Compile a single domain for a locale.
     */
    private function compileDomain(string $locale, string $domain): bool
    {
        try {
            $translations = $this->loadFromSource($locale, $domain);
            $compiledPath = $this->getCompiledPath($locale, $domain);
            
            // Ensure compiled directory exists
            $compiledDir = dirname($compiledPath);
            $this->ensureDirectoryExists($compiledDir);
            
            // Generate PHP array code
            $phpCode = "<?php\n\n";
            $phpCode .= "// Compiled translation file for locale: {$locale}, domain: {$domain}\n";
            $phpCode .= "// Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            $phpCode .= "return " . var_export($translations, true) . ";\n";
            
            $result = file_put_contents($compiledPath, $phpCode, LOCK_EX);
            return $result !== false;
            
        } catch (\Throwable $e) {
            error_log("Failed to compile translations for {$locale}.{$domain}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get path to source translation file.
     */
    private function getSourcePath(string $locale, string $domain): string
    {
        return $this->getLocalePath($locale) . "/{$domain}.json";
    }

    /**
     * Get path to compiled translation file.
     */
    private function getCompiledPath(string $locale, string $domain): string
    {
        return $this->compiledPath . "/{$locale}/{$domain}.php";
    }

    /**
     * Get path to locale directory.
     */
    private function getLocalePath(string $locale): string
    {
        return $this->basePath . '/' . $locale;
    }

    /**
     * Ensure directory exists, create if needed.
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \RuntimeException("Cannot create directory: {$path}");
            }
        }
    }
}