#!/bin/bash

# Emergency Production Fix for Marital Status Column
# Run this on your production server to fix the immediate issue

echo "🚨 Emergency Fix: Adding missing marital_status column"

# Create the migration directly in the database
php artisan tinker --execute="
try {
    // Check if column exists
    if (!\Schema::hasColumn('members', 'marital_status')) {
        // Add the column
        \Schema::table('members', function (\$table) {
            \$table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single')->after('matrimony_status');
        });
        echo '✅ Added marital_status column';
        
        // Migrate data from matrimony_status to marital_status
        \DB::table('members')->whereNotNull('matrimony_status')->update([
            'marital_status' => \DB::raw('matrimony_status')
        ]);
        echo '✅ Migrated data from matrimony_status to marital_status';
    } else {
        echo '✅ marital_status column already exists';
    }
    
    // Check marriage residence column
    if (!\Schema::hasColumn('members', 'member_marriage_residence')) {
        \Schema::table('members', function (\$table) {
            \$table->string('member_marriage_residence')->nullable()->after('marital_status');
        });
        echo '✅ Added member_marriage_residence column';
    } else {
        echo '✅ member_marriage_residence column already exists';
    }
    
    // Test the fix
    \$marriedCount = \App\Models\Member::where('marital_status', 'married')->count();
    echo '✅ Emergency fix successful - Found ' . \$marriedCount . ' married members';
    
} catch (\Exception \$e) {
    echo '❌ Emergency fix failed: ' . \$e->getMessage();
    exit(1);
}
"

echo "✅ Emergency fix completed. You can now continue with deployment."