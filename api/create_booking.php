<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$workshop_id = $_POST['workshop_id'] ?? null;
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');


// Starts a database transaction to make sure the booking,
//  seat update are handled together.
// If any step fails, all changes will be rolled back.

/*
  Formats workshop dates and times for the booking confirmation email.
  These helpers keep the email readable without changing the database values.
*/
function format_booking_date(?string $date): string
{
    if (empty($date)) {
        return 'To be announced';
    }

    return date('M j, Y', strtotime($date));
}

function format_booking_time(?string $startTime, ?string $endTime): string
{
    if (empty($startTime) || empty($endTime)) {
        return 'To be announced';
    }

    return date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime));
}

/*
  Sends a confirmation email after the booking is saved successfully.
  The Zoom link is not exposed here because SkillHub says meeting details
  are sent by email before the workshop starts.
*/
function send_booking_confirmation_email(
    string $email,
    string $firstName,
    string $lastName,
    array $workshop
): bool {
    $fullName = trim($firstName . ' ' . $lastName);
    $dateText = format_booking_date($workshop['workshop_date'] ?? '');
    $timeText = format_booking_time($workshop['start_time'] ?? '', $workshop['end_time'] ?? '');
    $categoryName = $workshop['category_name'] ?? 'Workshop';
    $workshopTitle = $workshop['title'] ?? 'SkillHub Workshop';

    $subject = 'SkillHub Workshop Booking Confirmation';

    $message = "Hello {$fullName},\n\n"
        . "Your workshop booking has been confirmed.\n\n"
        . "Workshop Details:\n"
        . "Workshop: {$workshopTitle}\n"
        . "Category: {$categoryName}\n"
        . "Date: {$dateText}\n"
        . "Time: {$timeText}\n\n"
        . "Your seat has been reserved successfully.\n"
        . "The Zoom meeting link and access details will be sent to your email before the workshop starts.\n\n"
        . "Thank you,\n"
        . "SkillHub Team";

    // Use a real IONOS-hosted email later if available.
    $headers = "From: SkillHub <no-reply@s1098733524.onlinehome.us>\r\n"
        . "Reply-To: no-reply@s1098733524.onlinehome.us\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($email, $subject, $message, $headers);
}

try {
    $pdo->beginTransaction();
        // Load workshop details for seat validation and the confirmation email.
    $workshopStmt = $pdo->prepare("
        SELECT workshops.workshop_id,
               workshops.title,
               workshops.workshop_date,
               workshops.start_time,
               workshops.end_time,
               workshops.available_seats,
               categories.category_name
        FROM workshops
        JOIN categories
        ON workshops.category_id = categories.category_id
        WHERE workshops.workshop_id = :workshop_id
        LIMIT 1
    ");

    $workshopStmt->execute([
        ':workshop_id' => $workshop_id
    ]);

    $workshop = $workshopStmt->fetch();

    if (!$workshop) {
        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'Workshop was not found.'
        ]);
        exit;
    }
    // Blocks booking if the workshop already ended.
        $now = new DateTime('now', new DateTimeZone('Asia/Riyadh'));
        $workshopEnd = new DateTime(
            $workshop['workshop_date'] . ' ' . $workshop['end_time'],
            new DateTimeZone('Asia/Riyadh')
        );

        if ($workshopEnd <= $now) {
            $pdo->rollBack();

            echo json_encode([
                'success' => false,
                'message' => 'This workshop has already ended and can no longer be booked.'
            ]);
            exit;
        }

    if ((int) $workshop['available_seats'] <= 0) {
        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'This workshop is fully booked.'
        ]);
        exit;
    }

    // Check if this user has already booked this workshop
    // Prevents the same person from booking the same workshop multiple times
    $checkDupe = $pdo->prepare(
        "SELECT COUNT(*) FROM bookings WHERE workshop_id = :workshop_id AND email = :email"
    );
    $checkDupe->execute([':workshop_id' => $workshop_id, ':email' => $email]);
    
    if ($checkDupe->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'You have already booked this workshop.',
            'already_booked' => true
        ]);
        exit;
    }

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

    // If no row was updated, the workshop had no available seats.
    if ($seatStmt->rowCount() !== 1) {
        throw new Exception('This workshop is fully booked.');
    }

    $pdo->commit();

    // Send email after commit so the booking is not lost if mail() fails.
    $emailSent = send_booking_confirmation_email(
        $email,
        $first_name,
        $last_name,
        $workshop
    );

    echo json_encode([
        'success' => true,
        'email_sent' => $emailSent,
        'message' => $emailSent
            ? 'Booking confirmed successfully. A confirmation email was sent.'
            : 'Booking confirmed successfully, but the confirmation email could not be sent.'
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