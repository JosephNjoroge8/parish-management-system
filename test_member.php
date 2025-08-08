<?php
// Test script to manually create members

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\Member;
use App\Models\Family;

try {
    // Create a test member
    $member = Member::create([
        'first_name' => 'John',
        'last_name' => 'Test',
        'local_church' => 'St James Kangemi',
        'church_group' => 'PMC',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
        'membership_date' => '2020-01-01',
        'membership_status' => 'active',
    ]);
    
    echo "Member created successfully with ID: " . $member->id . "\n";
    echo "Total members now: " . Member::count() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
