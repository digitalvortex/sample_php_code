<?php

namespace App\Seeders;

use App\Models\Level;
use Exception;

class LevelSeeder
{
    private Level $levelModel;

    public function __construct(Level $levelModel)
    {
        $this->levelModel = $levelModel;
    }

    public function seed(): void
    {
        $levels = [
            ['name' => 'admin'],
            ['name' => 'editor'],
            ['name' => 'reader'],
        ];

        foreach ($levels as $data) {
            try {
                $id = $this->levelModel->createLevel($data);
                echo "Inserted level '{$data['name']}' with ID: {$id}\n";
            } catch (Exception $e) {
                echo "Error inserting level '{$data['name']}': " . $e->getMessage() . "\n";
            }
        }
    }
}