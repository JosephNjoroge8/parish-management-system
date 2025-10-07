<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateSuperAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-super-user 
                            {--email= : Admin email address} 
                            {--password= : Admin password} 
                            {--name= : Admin full name}
                            {--force : Force creation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super administrator user with full system access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔐 Creating Super Administrator User for Parish Management System');
        $this->newLine();

        // Get input values
        $email = $this->option('email') ?: $this->ask('Admin Email Address', 'admin@parishsystem.com');
        $name = $this->option('name') ?: $this->ask('Admin Full Name', 'System Administrator');
        $password = $this->option('password') ?: $this->secret('Admin Password (leave empty for auto-generated)');

        // Generate password if not provided
        if (empty($password)) {
            $password = Str::random(12);
            $this->warn("🔑 Auto-generated password: {$password}");
            $this->warn('⚠️  IMPORTANT: Save this password securely!');
        }

        // Validate input
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("   • {$error}");
            }

            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if (! $this->option('force') && ! $this->confirm("User with email {$email} already exists. Update to super admin?")) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }

            // Update existing user
            $existingUser->update([
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->info("✅ Updated existing user {$email} to super administrator");
        } else {
            // Create new user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_active' => true,
                'phone' => null,
                'email_verified_at' => now(),
            ]);

            $this->info("✅ Created new super administrator: {$email}");
        }

        // Display login information
        $this->newLine();
        $this->info('🎉 Super Administrator Setup Complete!');
        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Name', $name],
            ['Email', $email],
            ['Password', $password],
            ['Admin Access', 'Full System Access'],
            ['Member Management', 'Enabled'],
            ['User Management', 'Enabled'],
        ]);

        $this->newLine();
        $this->warn('🔒 SECURITY REMINDERS:');
        $this->warn('   • Change the password after first login');
        $this->warn('   • Store credentials securely');
        $this->warn('   • Enable two-factor authentication if available');
        $this->newLine();

        return Command::SUCCESS;
    }
}
