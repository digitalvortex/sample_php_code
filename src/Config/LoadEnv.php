<?php
declare(strict_types=1);
namespace App\Config;

/**
 * Class LoadEnv
 * 
 * This class is responsible for loading environment variables from a file.
 */
readonly class LoadEnv
{
    /**
     * Loads environment variables from the specified file.
     *
     * @param string $env The path to the environment file.
     * @throws \InvalidArgumentException if the environment file does not exist.
     * @return void
     */
    public static function load(string $env): void
    {
        if (!file_exists($env)) {
            throw new \InvalidArgumentException("Env file $env does not exist");
        }

        $lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);

            // Trim whitespace
            $value = trim($value, "'\" \t\n\r\0\x0B");

            $_ENV[$key] = $value;
        }

    }

}