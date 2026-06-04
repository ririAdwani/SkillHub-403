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
  These helpers keep the email readable without changing database values.
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
  Formats newline-separated admin text into simple HTML bullet points.
  Used for learning points and good-fit-for sections in the email.
*/
function format_email_points(?string $text): string
{
    $text = trim((string) $text);

    if ($text === '') {
        return '<p style="margin:0;color:#6B7280;font-size:14px;">No details added yet.</p>';
    }

    $lines = preg_split('/\r\n|\r|\n/', $text);
    $items = '';

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line !== '') {
            $items .= '<li style="margin-bottom:7px;color:#374151;">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</li>';
        }
    }

    return '<ul style="margin:0;padding-left:20px;font-size:14px;line-height:1.6;">' . $items . '</ul>';
}

/*
  Sends a styled HTML confirmation email after the booking is saved successfully.
  The email includes full workshop details, but the Zoom link is still not exposed.
*/
function send_booking_confirmation_email(
    string $email,
    string $firstName,
    string $lastName,
    array $workshop
): bool {
    $fullName = trim($firstName . ' ' . $lastName);
    $safeName = htmlspecialchars($fullName ?: 'Student', ENT_QUOTES, 'UTF-8');

    $dateText = htmlspecialchars(format_booking_date($workshop['workshop_date'] ?? ''), ENT_QUOTES, 'UTF-8');
    $timeText = htmlspecialchars(format_booking_time($workshop['start_time'] ?? '', $workshop['end_time'] ?? ''), ENT_QUOTES, 'UTF-8');

    $categoryName = htmlspecialchars($workshop['category_name'] ?? 'Workshop', ENT_QUOTES, 'UTF-8');
    $workshopTitle = htmlspecialchars($workshop['title'] ?? 'SkillHub Workshop', ENT_QUOTES, 'UTF-8');
    $description = nl2br(htmlspecialchars($workshop['description'] ?? 'No description added yet.', ENT_QUOTES, 'UTF-8'));
    $hookMessage = nl2br(htmlspecialchars($workshop['hook_message'] ?? '', ENT_QUOTES, 'UTF-8'));

    $instructorName = htmlspecialchars($workshop['instructor_name'] ?: 'Instructor TBA', ENT_QUOTES, 'UTF-8');
    $instructorSpecialty = htmlspecialchars($workshop['instructor_specialty'] ?: 'Not specified', ENT_QUOTES, 'UTF-8');
    $instructorExperience = htmlspecialchars($workshop['instructor_experience'] ?: 'Not specified', ENT_QUOTES, 'UTF-8');

    $learningPoints = format_email_points($workshop['learning_points'] ?? '');
    $goodFitFor = format_email_points($workshop['good_fit_for'] ?? '');

    $subject = 'SkillHub Workshop Booking Confirmation';

    $hookHtml = $hookMessage !== ''
        ? '<p style="margin:0 0 18px;color:#1F2937;font-size:15px;line-height:1.7;font-weight:600;">' . $hookMessage . '</p>'
        : '';

    $message = '
    <!doctype html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>SkillHub Booking Confirmation</title>
    </head>
    <body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif;color:#1F2937;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:28px 12px;">
        <tr>
          <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:18px;overflow:hidden;">
              
              <tr>
                <td style="background:#EBF4FF;padding:28px 30px;text-align:center;">
                  <div style="display:inline-block;background:#2C7BE5;color:#FFFFFF;padding:10px 14px;border-radius:14px;font-size:18px;font-weight:700;">
                    SkillHub
                  </div>
                  <h1 style="margin:18px 0 8px;color:#1F2937;font-size:24px;line-height:1.3;">
                    Your workshop booking is confirmed
                  </h1>
                  <p style="margin:0;color:#6B7280;font-size:14px;">
                    Your seat has been reserved successfully.
                  </p>
                </td>
              </tr>

              <tr>
                <td style="padding:30px;">
                  <p style="margin:0 0 18px;color:#374151;font-size:15px;line-height:1.7;">
                    Hello <strong>' . $safeName . '</strong>,
                  </p>

                  <p style="margin:0 0 22px;color:#374151;font-size:15px;line-height:1.7;">
                    Thank you for booking with SkillHub. Below are the full details for your selected workshop.
                  </p>

                  <div style="padding:20px;border:1px solid #DBEAFE;border-radius:16px;background:#F8FBFF;margin-bottom:22px;">
                    <p style="margin:0 0 8px;color:#2C7BE5;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
                      Workshop Details
                    </p>
                    <h2 style="margin:0 0 10px;color:#111827;font-size:22px;line-height:1.3;">
                      ' . $workshopTitle . '
                    </h2>
                    ' . $hookHtml . '
                    <p style="margin:0;color:#4B5563;font-size:14px;line-height:1.7;">
                      ' . $description . '
                    </p>
                  </div>

                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                    <tr>
                      <td style="padding:12px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;">
                        <strong style="display:block;color:#6B7280;font-size:12px;margin-bottom:4px;">Category</strong>
                        <span style="color:#1F2937;font-size:14px;">' . $categoryName . '</span>
                      </td>
                    </tr>
                    <tr><td height="10"></td></tr>
                    <tr>
                      <td style="padding:12px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;">
                        <strong style="display:block;color:#6B7280;font-size:12px;margin-bottom:4px;">Date & Time</strong>
                        <span style="color:#1F2937;font-size:14px;">' . $dateText . ' | ' . $timeText . '</span>
                      </td>
                    </tr>
                    <tr><td height="10"></td></tr>
                    <tr>
                      <td style="padding:12px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;">
                        <strong style="display:block;color:#6B7280;font-size:12px;margin-bottom:4px;">Instructor</strong>
                        <span style="color:#1F2937;font-size:14px;">' . $instructorName . '</span><br>
                        <span style="color:#6B7280;font-size:13px;">Specialty: ' . $instructorSpecialty . '</span><br>
                        <span style="color:#6B7280;font-size:13px;">Experience: ' . $instructorExperience . '</span>
                      </td>
                    </tr>
                  </table>

                  <div style="padding:18px;border:1px solid #BBF7D0;border-radius:16px;background:#F0FDF4;margin-bottom:16px;">
                    <p style="margin:0 0 10px;color:#059669;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
                      What You’ll Learn
                    </p>
                    ' . $learningPoints . '
                  </div>

                  <div style="padding:18px;border:1px solid #DDD6FE;border-radius:16px;background:#F5F3FF;margin-bottom:20px;">
                    <p style="margin:0 0 10px;color:#7C3AED;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
                      Good Fit For
                    </p>
                    ' . $goodFitFor . '
                  </div>

                  <div style="padding:15px 16px;border-radius:14px;background:#EBF4FF;border:1px solid #BFDBFE;">
                    <p style="margin:0;color:#475569;font-size:14px;line-height:1.6;">
                      <strong style="color:#2C7BE5;">Online session:</strong>
                      The Zoom meeting link and access details will be sent to your email before the workshop starts.
                    </p>
                  </div>

                  <p style="margin:24px 0 0;color:#6B7280;font-size:14px;line-height:1.7;">
                    Thank you,<br>
                    <strong>SkillHub Team</strong>
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>';

    // HTML email requires MIME headers because the content is no longer plain text.
    $headers = "From: SkillHub <no-reply@s1098733524.onlinehome.us>\r\n"
        . "Reply-To: no-reply@s1098733524.onlinehome.us\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n";

    return @mail($email, $subject, $message, $headers);
}

try {
    $pdo->beginTransaction();
        // Load workshop details for seat validation and the confirmation email.
$workshopStmt = $pdo->prepare("
    SELECT workshops.workshop_id,
           workshops.title,
           workshops.description,
           workshops.hook_message,
           workshops.good_fit_for,
           workshops.learning_points,
           workshops.workshop_date,
           workshops.start_time,
           workshops.end_time,
           workshops.available_seats,
           categories.category_name,
           TRIM(CONCAT(COALESCE(i.title,''), ' ', COALESCE(i.full_name,''))) AS instructor_name,
           i.specialty AS instructor_specialty,
           i.experience AS instructor_experience
    FROM workshops
    JOIN categories
    ON workshops.category_id = categories.category_id
    LEFT JOIN instructors i
    ON workshops.instructor_id = i.instructor_id
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