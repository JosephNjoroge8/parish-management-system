<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperUser extends Command
{
    protected $signature = 'user:create-super {email} {password} {--name=Super Admin}';
    protected $description = 'Create a super user with full admin privileges';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->option('name');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Create the super user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Super user created successfully!");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info("Name: {$name}");
        $this->info("Admin privileges: Enabled");

        return 0;
    }
}