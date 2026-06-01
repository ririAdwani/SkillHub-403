<?php
// Temporary setup file to create one admin account.
// Delete this file immediately after running it once.

require_once __DIR__ . '/includes/db.php';

$fullName = 'SkillHub Admin';
$email = 'admin@skillhub.com';
$password = 'Admin@123';
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