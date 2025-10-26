<?php

// Direct database check for admin user
try {
    $pdo = new PDO('sqlite:'.__DIR__.'/database/database.sqlite');

    echo "=== SUPER USER LOGIN DETAILS ===\n";
    echo "📧 Email: admin@parish.com\n";
    echo "🔑 Password: admin123\n";
    echo "🌐 Login URL: http://localhost:8000/login\n\n";

    echo "=== VERIFYING ADMIN USER ===\n";
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute(['admin@parish.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✅ Admin user found\n";
        echo '📧 Email: '.$user['email']."\n";
        echo '👤 Name: '.$user['name']."\n";
        echo '🟢 Status: '.($user['is_active'] ? 'Active' : 'Inactive')."\n";
        echo '✅ Email Verified: '.($user['email_verified_at'] ? 'Yes' : 'No')."\n";
    } else {
        echo "❌ Admin user not found in database!\n";
    }

    echo "\n=== MEMBER STATISTICS ===\n";
    $memberCount = $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $activeMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE membership_status = 'active'")->fetchColumn();
    $marriedMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE matrimony_status = 'married'")->fetchColumn();

    echo "👥 Total Members: $memberCount\n";
    echo "🟢 Active Members: $activeMembers\n";
    echo "💍 Married Members: $marriedMembers\n";

    // Church group distribution
    $groups = $pdo->query('SELECT church_group, COUNT(*) as count FROM members GROUP BY church_group')->fetchAll(PDO::FETCH_ASSOC);

    echo "\n🏛️ Church Group Distribution:\n";
    foreach ($groups as $group) {
        echo "  • {$group['church_group']}: {$group['count']} members\n";
    }

    echo "\n=== SYSTEM STATUS ===\n";
    echo "✅ Database: Connected\n";
    echo '✅ Total Users: '.$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()."\n";
    echo "✅ Total Members: $memberCount\n";
    echo "✅ Database Tables: Working\n";

    echo "\n🚀 SYSTEM READY FOR TESTING!\n";
    echo "🔗 Open browser and navigate to: http://localhost:8000/login\n";
    echo "🔑 Use credentials: admin@parish.com / admin123\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
