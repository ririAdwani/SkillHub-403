<?php
/*
Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3
Name=Shahenaz Abushanab, ID=2215050, Section=DAR, Date=20/3
Name=Raghad Abdullah Alzahrani, ID=2206740, Section=DAR, Date=20/3
*/

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$workshop_id = $_POST['workshop_id'] ?? null;
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

if (!$workshop_id || empty($first_name) || empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}
// Starts a database transaction to make sure the booking,
// file upload metadata, and seat update are handled together.
// If any step fails, all changes will be rolled back.

try {
    $pdo->beginTransaction();

    $bookingStmt = $pdo->prepare("
        INSERT INTO bookings (workshop_id, first_name, last_name, email)
        VALUES (:workshop_id, :first_name, :last_name, :email)
    ");

    $bookingStmt->execute([
        ':workshop_id' => $workshop_id,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email
    ]);

    $booking_id = $pdo->lastInsertId();

    // Handles the optional supporting file upload.
// The system validates the file extension and size,
// renames the file with a unique name, stores it securely,
// and saves its metadata in the database.

    if (isset($_FILES['supporting_file']) && $_FILES['supporting_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 2 * 1024 * 1024;

        $originalName = $_FILES['supporting_file']['name'];
        $fileSize = $_FILES['supporting_file']['size'];
        $tmpPath = $_FILES['supporting_file']['tmp_name'];

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only PDF, JPG, JPEG, and PNG are allowed.');
        }

        if ($fileSize > $maxSize) {
            throw new Exception('File size is too large. Maximum allowed size is 2MB.');
        }

        $storedName = uniqid('certificate_', true) . '.' . $extension;
        $uploadDir = __DIR__ . '/../uploads/certificates/';
        $filePath = $uploadDir . $storedName;
        $dbPath = 'uploads/certificates/' . $storedName;

        if (!move_uploaded_file($tmpPath, $filePath)) {
            throw new Exception('Failed to upload the file.');
        }

        $uploadStmt = $pdo->prepare("
            INSERT INTO uploads 
            (booking_id, original_name, stored_name, file_path, file_type, file_size)
            VALUES 
            (:booking_id, :original_name, :stored_name, :file_path, :file_type, :file_size)
        ");

        $uploadStmt->execute([
            ':booking_id' => $booking_id,
            ':original_name' => $originalName,
            ':stored_name' => $storedName,
            ':file_path' => $dbPath,
            ':file_type' => $extension,
            ':file_size' => $fileSize
        ]);
    }

    // Updates the number of available seats after a successful booking.
// The condition prevents the number of seats from going below zero.

    $seatStmt = $pdo->prepare("
        UPDATE workshops
        SET available_seats = available_seats - 1
        WHERE workshop_id = :workshop_id
        AND available_seats > 0
    ");

    $seatStmt->execute([
        ':workshop_id' => $workshop_id
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking confirmed successfully.'
    ]);

    // If an error occurs, the transaction is cancelled
// and a JSON error response is returned to the frontend.

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}