<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Parish Administrator',
            'email' => 'admin@parish.com',
            'phone' => '+254700000001',
            'password' => Hash::make('admin123'),
            'is_active' => true,
            'date_of_birth' => '1980-01-01',
            'gender' => 'Male',
            'address' => 'Parish Office',
            'occupation' => 'Parish Administrator',
            'emergency_contact' => 'Emergency Contact Admin',
            'emergency_phone' => '+254700000011',
            'email_verified_at' => now(),
        ]);

        // Create parish priest
        User::create([
            'name' => 'Father John Smith',
            'email' => 'priest@parish.com',
            'phone' => '+254700000002',
            'password' => Hash::make('priest123'),
            'is_active' => true,
            'date_of_birth' => '1975-05-15',
            'gender' => 'Male',
            'address' => 'Parish Rectory',
            'occupation' => 'Parish Priest',
            'emergency_contact' => 'Diocese Office',
            'emergency_phone' => '+254700000022',
            'email_verified_at' => now(),
        ]);

        // Create secretary
        User::create([
            'name' => 'Mary Johnson',
            'email' => 'secretary@parish.com',
            'phone' => '+254700000003',
            'password' => Hash::make('secretary123'),
            'is_active' => true,
            'date_of_birth' => '1985-08-20',
            'gender' => 'Female',
            'address' => 'Nairobi, Kenya',
            'occupation' => 'Parish Secretary',
            'emergency_contact' => 'John Johnson',
            'emergency_phone' => '+254700000033',
            'email_verified_at' => now(),
        ]);

        // Create treasurer
        User::create([
            'name' => 'Peter Kamau',
            'email' => 'treasurer@parish.com',
            'phone' => '+254700000004',
            'password' => Hash::make('treasurer123'),
            'is_active' => true,
            'date_of_birth' => '1978-12-10',
            'gender' => 'Male',
            'address' => 'Nairobi, Kenya',
            'occupation' => 'Accountant',
            'emergency_contact' => 'Grace Kamau',
            'emergency_phone' => '+254700000044',
            'email_verified_at' => now(),
        ]);
    }
}
