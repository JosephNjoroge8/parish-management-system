<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing Local Church Export...\n";
    
    // Test if we can find members with that church name
    $churchName = 'Our Lady of Consolata Cathedral';
    echo "Looking for members from: {$churchName}\n";
    
    $members = App\Models\Member::where('local_church', $churchName)->get();
    echo "Found " . $members->count() . " members\n";
    
    if ($members->count() > 0) {
        echo "First member: " . $members->first()->first_name . " " . $members->first()->last_name . "\n";
    }
    
    // Test the export class directly
    echo "\nTesting MembersExport class...\n";
    $export = new App\Exports\MembersExport([], [], [], $members);
    $collection = $export->collection();
    echo "Export collection count: " . $collection->count() . "\n";
    
    // Test Excel download
    echo "\nTesting Excel download...\n";
    $result = Maatwebsite\Excel\Facades\Excel::download($export, 'test-export.xlsx');
    
    if ($result instanceof Symfony\Component\HttpFoundation\BinaryFileResponse) {
        echo "✅ Excel download successful!\n";
    } else {
        echo "❌ Excel download failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
