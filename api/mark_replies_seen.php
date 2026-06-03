<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    // Use MySQL NOW() instead of PHP date() to avoid timezone mismatch
    // PHP and MySQL are in different timezones on this server
    $stmt = $pdo->prepare(
        "UPDATE users SET replies_seen_at = NOW() WHERE user_id = :uid"
    );
    $stmt->execute([':uid' => current_user_id()]);

    // Read back what MySQL actually saved so we can confirm
    $check = $pdo->prepare("SELECT replies_seen_at FROM users WHERE user_id = :uid");
    $check->execute([':uid' => current_user_id()]);
    $saved = $check->fetchColumn();

    $_SESSION['replies_seen_at'] = $saved;

    echo json_encode([
        'success'  => true,
        'saved_at' => $saved,
        'user_id'  => current_user_id()
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}