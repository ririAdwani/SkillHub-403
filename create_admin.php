<?php
// Temporary setup file to create one admin account.
// Delete this file immediately after running it once.
// 1. Upload create_admin.php
// 2. Open it once in browser:
//    http://s1098733524.onlinehome.us/create_admin.php
// 3. Confirm it says admin created
// 4. Delete create_admin.php immediately
// 5. Log in using created account. 

require_once __DIR__ . '/includes/db.php';

$fullName = 'SkillHub Admin';
$email = 'admin73541@skillhub.edu';
$password = 'cbhw249s@mdkA';
$role = 'admin';

try {
    // Check if the admin email already exists.
    $checkStmt = $pdo->prepare(
        'SELECT user_id FROM users WHERE email = :email LIMIT 1'
    );

    $checkStmt->execute([
        'email' => $email,
    ]);

    if ($checkStmt->fetch()) {
        die('Admin account already exists.');
    }

    // Hash the admin password before storing it.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the admin account with a hashed password.
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role)
         VALUES (:full_name, :email, :password_hash, :role)'
    );

    $insertStmt->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);

    echo 'Admin account created successfully.';
} catch (PDOException $e) {
    die('Admin account could not be created.');
}