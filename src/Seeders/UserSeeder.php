<?php
declare(strict_types=1);

namespace App\Seeders;

use App\Models\User;
use App\Traits\SecurePasswordGenerator;

class UserSeeder
{
    use SecurePasswordGenerator;
    private User $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function seed(): void
    {
        $users = [
            [
                'username' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Alice Johnson',
                'email' => 'alice.johnson@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Alice',
                'last_name' => 'Johnson',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Bob Brown',
                'email' => 'bob.brown@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Bob',
                'last_name' => 'Brown',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Charlie Davis',
                'email' => 'charlie.davis@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Charlie',
                'last_name' => 'Davis',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Diana Evans',
                'email' => 'diana.evans@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Diana',
                'last_name' => 'Evans',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Ethan Foster',
                'email' => 'ethan.foster@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Ethan',
                'last_name' => 'Foster',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Fiona Green',
                'email' => 'fiona.green@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Fiona',
                'last_name' => 'Green',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'George Harris',
                'email' => 'george.harris@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'George',
                'last_name' => 'Harris',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'Hannah Irving',
                'email' => 'hannah.irving@example.com',
                'password' => $this->generateStrongPassword(),
                'first_name' => 'Hannah',
                'last_name' => 'Irving',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            // Add more test users as needed
        ];

        foreach ($users as $user) {
            $this->userModel->create($user);
        }
    }
}