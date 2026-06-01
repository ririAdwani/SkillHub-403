<?php
// api/mark_replies_seen.php
// Called when user opens the My Replies tab
// Sets a session flag so the navbar dot disappears
require_once __DIR__ . '/../includes/auth.php';
$_SESSION['replies_seen_at'] = time();
echo json_encode(['success' => true]);