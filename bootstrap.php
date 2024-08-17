<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Config\LoadEnv;

LoadEnv::load('.env');
die($_ENV['DB_HOST']);


