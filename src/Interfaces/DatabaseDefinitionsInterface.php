<?php
declare(strict_types=1);

namespace App\Interfaces;

interface DatabaseDefinitionsInterface
{
    public static function getDefinitions(): array;
}