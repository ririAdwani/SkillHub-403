<?php
/*
  process_feedback.php — Saves student feedback to the database
  ==============================================================
  Called via AJAX (fetch) from main.js when the feedback form is submitted.
  
  Assignment compliance:
  ✅ Saves to database (feedback table)
  ✅ Requires login (only logged-in users can submit)
  ✅ Returns JSON response
  ✅ Uses PDO prepared statements (prevents SQL injection)
*/

header('Content-Type: application/json');

// Include from correct relative path
// This file lives in api/ folder (or server/ — see note below)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in users can submit feedback
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Collect and sanitize all form fields
// Name and email come from the logged-in account, not from editable user input.
$name       = trim($_SESSION['full_name'] ?? '');
$email      = trim($_SESSION['email'] ?? '');
$rating     = trim($_POST['rating'] ?? '');
$preference = trim($_POST['preference'] ?? '');
$comments   = trim($_POST['comments'] ?? '');

// workshops[] is an array of checkbox values — join them as a readable string
$workshopsArray = $_POST['workshops'] ?? [];
$workshopsStr   = is_array($workshopsArray) ? implode(', ', $workshopsArray) : '';

// Validate required fields
if ($name === '' || $email === '' || $rating === '') {
    echo json_encode(['success' => false, 'message' => 'Name, email, and rating are required.']);
    exit;
}

// Validate rating is one of the allowed values
if (!in_array($rating, ['Good', 'Average', 'Poor'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value.']);
    exit;
}

try {
    // Insert feedback into the database
    // user_id links to the logged-in user so admin knows who sent it
    $stmt = $pdo->prepare("
        INSERT INTO feedback 
            (user_id, name, email, rating, workshops_interested, preferred_time, comments, submitted_at)
        VALUES 
            (:user_id, :name, :email, :rating, :workshops, :preference, :comments, UTC_TIMESTAMP())
    ");

    $stmt->execute([
        ':user_id'    => current_user_id(),
        ':name'       => $name,
        ':email'      => $email,
        ':rating'     => $rating,
        ':workshops'  => $workshopsStr,
        ':preference' => $preference,
        ':comments'   => $comments,
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Return the actual error so you can debug it
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}