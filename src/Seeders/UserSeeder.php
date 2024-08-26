<?php

namespace App\Seeders;

use App\Models\User;
use App\Traits\GeneratesRandomUsernames;

class UserSeeder
{
    use GeneratesRandomUsernames;

    private User $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function seed(): void
    {
        $users = [
            [
                'username' => $this->generateRandomUsername(),
                'email' => 'fgreen01@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Frank',
                'last_name' => 'Green',
                'level' => 1,
            ],
            [
                'username' => $this->generateRandomUsername(),
                'email' => 'efoster22@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Emma',
                'last_name' => 'Foster',
                'level' => 1,
            ],
            [
                'username' => $this->generateRandomUsername(),
                'email' => 'jdoe33@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'level' => 2,
            ],
            [
                'username' => $this->generateRandomUsername(),
                'email' => 'asmith44@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'level' => 1,
            ],
            [
                'username' => $this->generateRandomUsername(),
                'email' => 'bwhite55@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Bob',
                'last_name' => 'White',
                'level' => 3,
            ],
        ];

        foreach ($users as $user) {
            $this->userModel->create($user);
        }
    }
}