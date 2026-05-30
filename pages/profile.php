<?php
// profile.php manages the logged-in user's profile details.
// It supports profile picture upload, full name editing, password changes,
// and shows booked workshops for regular users only.

// <!-- Profile page. For admin and regular user. 
// User information
// Booked workshops
// Edit profile information
// Change password
// maybe Upload/change profile picture

// For Admin:
// Admin information
// Role information
// Edit profile information
// Change password
// maybe Upload/change profile picture

// -->

// <!-- Backend logic: 
//  (Pic upload flow)
// - save in uploads/profile_pictures/
// - rename file uniquely
// - validate extension
// - validate MIME type
// - validate size <= 2MB
// - reject executable/unsafe files
// - store image path in users.profile_image
// - delete old profile picture after successful new upload 
// (Password change flow)
// - current password must match existing hash
// - new password must follow SkillHub password rules
// - confirm password checked only after new password is valid
// - save new password with password_hash()
// -->
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$basePath = '../';
$currentPage = 'profile';

$successMessages = [];
$errors = [];
$userId = current_user_id();

// Loads the current user from the database so the profile page uses fresh data.
function load_profile_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, profile_image
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);

    $user = $stmt->fetch();
    return $user ?: null;
}

// Builds a clear time status for booked workshops shown on regular user profiles.
function get_workshop_status(?string $date, ?string $startTime): array
{
    if (empty($date)) {
        return ['label' => 'Scheduled', 'class' => 'status-upcoming'];
    }

    try {
        $timeValue = $startTime ?: '00:00:00';
        $startDate = new DateTime($date . ' ' . $timeValue);
        $today = new DateTime('today');
        $workshopDay = new DateTime($startDate->format('Y-m-d'));
        $daysDifference = (int) $today->diff($workshopDay)->format('%r%a');

        if ($startDate < new DateTime()) {
            return ['label' => 'Past workshop', 'class' => 'status-past'];
        }

        if ($daysDifference === 0) {
            return ['label' => 'Starts today', 'class' => 'status-today'];
        }

        if ($daysDifference === 1) {
            return ['label' => 'Starts tomorrow', 'class' => 'status-upcoming'];
        }

        if ($daysDifference <= 6) {
            return ['label' => 'Starts in ' . $daysDifference . ' days', 'class' => 'status-upcoming'];
        }

        if ($daysDifference <= 13) {
            return ['label' => 'Starts next week', 'class' => 'status-upcoming'];
        }

        return ['label' => 'Upcoming', 'class' => 'status-upcoming'];
    } catch (Exception $e) {
        return ['label' => 'Scheduled', 'class' => 'status-upcoming'];
    }
}

$user = load_profile_user($pdo, (int) $userId);

if (!$user) {
    logout_user();
    header('Location: login.php');
    exit;
}

// Handles profile updates submitted from the profile page forms.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_name') {
        $fullName = trim($_POST['full_name'] ?? '');

        // Validate the displayed account name before saving it.
        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 100) {
            $errors[] = 'Full name must be 100 characters or less.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET full_name = :full_name
                     WHERE user_id = :user_id'
                );
                $stmt->execute([
                    'full_name' => $fullName,
                    'user_id' => $userId,
                ]);

                // Keep the session value aligned with the database value.
                $_SESSION['full_name'] = $fullName;
                $successMessages[] = 'Your name has been updated.';
                $user = load_profile_user($pdo, (int) $userId);
            } catch (PDOException $e) {
                $errors[] = 'Name update failed. Please try again later.';
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Verify the current password before accepting a new password.
        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $passwordErrors = validate_password($newPassword, $user['full_name'], $user['email']);

            if (!empty($passwordErrors)) {
                $errors[] = 'New password does not meet the security requirements.';
            } elseif ($confirmPassword === '') {
                $errors[] = 'Please confirm your new password.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            } else {
                try {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET password_hash = :password_hash
                         WHERE user_id = :user_id'
                    );
                    $stmt->execute([
                        'password_hash' => $passwordHash,
                        'user_id' => $userId,
                    ]);

                    $successMessages[] = 'Your password has been changed.';
                    $user = load_profile_user($pdo, (int) $userId);
                } catch (PDOException $e) {
                    $errors[] = 'Password change failed. Please try again later.';
                }
            }
        }
    }

    if ($action === 'upload_profile_picture') {
        $maxFileSize = 2 * 1024 * 1024;
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        $uploadDir = __DIR__ . '/../uploads/profile_pictures/';

        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please choose a profile picture to upload.';
        } elseif ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The file could not be uploaded. Please try again.';
        } elseif ($_FILES['profile_image']['size'] > $maxFileSize) {
            $errors[] = 'Profile picture must not exceed 2MB.';
        } elseif (!is_uploaded_file($_FILES['profile_image']['tmp_name'])) {
            $errors[] = 'Invalid upload request.';
        } else {
            $originalName = $_FILES['profile_image']['name'];
            $tmpPath = $_FILES['profile_image']['tmp_name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // Check both the extension and real MIME type because file names can be faked.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);
            $imageInfo = @getimagesize($tmpPath);
            $imageMime = $imageInfo['mime'] ?? '';

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Only JPG, JPEG, and PNG profile pictures are allowed.';
            } elseif (!in_array($mimeType, $allowedMimeTypes, true) || !in_array($imageMime, $allowedMimeTypes, true)) {
                $errors[] = 'The uploaded file must be a valid JPG or PNG image.';
            } else {
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $errors[] = 'Upload folder could not be created.';
                } else {
                    $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
                    $newFileName = 'profile_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $safeExtension;
                    $destinationPath = $uploadDir . $newFileName;
                    $storedPath = 'uploads/profile_pictures/' . $newFileName;

                    if (!move_uploaded_file($tmpPath, $destinationPath)) {
                        $errors[] = 'Profile picture could not be saved.';
                    } else {
                        try {
                            $oldProfileImage = $user['profile_image'] ?? '';

                            $stmt = $pdo->prepare(
                                'UPDATE users
                                 SET profile_image = :profile_image
                                 WHERE user_id = :user_id'
                            );
                            $stmt->execute([
                                'profile_image' => $storedPath,
                                'user_id' => $userId,
                            ]);

                            // Delete the old uploaded profile picture only after the new one is saved in the database.
                            if ($oldProfileImage !== '' && str_starts_with($oldProfileImage, 'uploads/profile_pictures/')) {
                                $oldFilePath = __DIR__ . '/../' . $oldProfileImage;

                                if (is_file($oldFilePath)) {
                                    unlink($oldFilePath);
                                }
                            }

                            $successMessages[] = 'Profile picture has been updated.';
                            $user = load_profile_user($pdo, (int) $userId);
                        } catch (PDOException $e) {
                            // Remove the newly moved file if the database update fails.
                            if (is_file($destinationPath)) {
                                unlink($destinationPath);
                            }

                            $errors[] = 'Profile picture update failed. Please try again later.';
                        }
                    }
                }
            }
        }
    }
}

$profileImage = $user['profile_image'] ?? '';
$hasProfileImage = $profileImage !== '';
$initial = strtoupper(mb_substr(trim($user['full_name']), 0, 1));
$bookings = [];
$bookingsError = '';

// Regular users see their booked workshops. Admin profiles stay focused on account settings only.
if (!is_admin()) {
    try {
        $stmt = $pdo->prepare(
            'SELECT bookings.booking_id,
                    bookings.email,
                    workshops.title,
                    workshops.workshop_date,
                    workshops.start_time,
                    workshops.end_time,
                    categories.category_name
             FROM bookings
             JOIN workshops ON bookings.workshop_id = workshops.workshop_id
             LEFT JOIN categories ON workshops.category_id = categories.category_id
             WHERE bookings.email = :email
             ORDER BY workshops.workshop_date ASC, workshops.start_time ASC'
        );
        $stmt->execute(['email' => $user['email']]);
        $bookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        $bookingsError = 'Booked workshops could not be loaded right now.';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile – SkillHub</title>

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../global/main.css" />
  </head>

  <body>


<!-- ===== PROFILE CONTENT ===== -->
<main class="profile-page">
  <div class="container profile-container">
    <a href="../index.php" class="profile-back-link">
      <i class="fa-solid fa-arrow-left"></i>
      <span>Back</span>
    </a>

    <?php if (!empty($successMessages)): ?>
      <div class="profile-alert profile-alert-success" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <div>
          <?php foreach ($successMessages as $message): ?>
            <p><?= h($message) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="profile-alert profile-alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
          <?php foreach ($errors as $error): ?>
            <p><?= h($error) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Compact profile summary and account actions. -->
    <section class="profile-top-card">
      <div class="profile-summary">
        <div class="profile-avatar-wrap">
          <?php if (!$hasProfileImage): ?>
            <div class="profile-avatar-note" data-profile-avatar-note>
              <i class="fa-solid fa-image"></i>
              <span>Add a picture here.</span>
            </div>
          <?php endif; ?>

          <button
            type="button"
            class="profile-avatar-btn"
            id="profile-avatar-open"
            aria-label="Upload profile picture"
          >
            <?php if ($hasProfileImage): ?>
              <img src="../<?= h($profileImage) ?>" alt="Profile picture" class="profile-avatar-img" />
            <?php else: ?>
              <span class="profile-avatar-initial"><?= h($initial) ?></span>
            <?php endif; ?>

            <span class="profile-avatar-plus" aria-hidden="true">
              <i class="fa-solid fa-plus"></i>
            </span>
          </button>
        </div>

        <div class="profile-identity">
          <h1><?= h($user['full_name']) ?></h1>
          <p><?= h($user['email']) ?></p>
        </div>
      </div>

      <div class="profile-actions">
        <!-- Full name edit form. -->
        <form method="post" action="profile.php" class="profile-name-form" id="profile-name-form" novalidate>
        <input type="hidden" name="action" value="update_name" />

        <label for="full_name">Full Name</label>

        <div class="profile-edit-field">
            <input
            type="text"
            id="full_name"
            name="full_name"
            value="<?= h($user['full_name']) ?>"
            maxlength="100"
            autocomplete="name"
            readonly
            required
            />

            <button
            type="button"
            class="profile-icon-submit"
            id="profile-name-edit"
            aria-label="Edit name"
            data-edit-label="Edit name"
            data-save-label="Save name"
            >
            <i class="fa-solid fa-pen"></i>
            </button>
        </div>
        </form>

        <button type="button" class="btn btn-outline profile-password-open" id="profile-password-open">
          <i class="fa-solid fa-lock"></i>
          Change Password
        </button>
      </div>
    </section>

    <?php if (!is_admin()): ?>
      <!-- Regular users see their booked workshops. -->
      <section class="profile-bookings-section">
        <div class="profile-section-header">
          <div>
            <span class="profile-section-kicker">My Schedule</span>
            <h2>Booked Workshops</h2>
            </div>

          <a href="services.php" class="btn btn-outline profile-section-action">
            <i class="fa-solid fa-box"></i>
            Explore More
          </a>
        </div>

        <?php if ($bookingsError !== ''): ?>
          <div class="profile-empty-state">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p><?= h($bookingsError) ?></p>
          </div>
        <?php elseif (empty($bookings)): ?>
          <div class="profile-empty-state">
            <i class="fa-solid fa-calendar-plus"></i>
            <h3>No booked workshops yet</h3>
            <p>Browse the workshop list and reserve your first session.</p>
          </div>
        <?php else: ?>
  <div class="profile-booking-note">
    <i class="fa-solid fa-video"></i>
    <span>For each upcoming reserved workshop, the Zoom meeting link will be sent to your email before the workshop starts.</span>
  </div>

  <div class="booking-table-wrapper">
    <table class="booking-table">
      <thead>
        <tr>
          <th>Workshop</th>
          <th>Category</th>
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <?php
            $status = get_workshop_status($booking['workshop_date'], $booking['start_time']);
            $dateText = date('M j, Y', strtotime($booking['workshop_date']));
            $startText = date('g:i A', strtotime($booking['start_time']));
            $endText = date('g:i A', strtotime($booking['end_time']));
          ?>

          <tr class="<?= h($status['class']) ?>">
            <td>
              <strong class="booking-title"><?= h($booking['title']) ?></strong>
            </td>

            <td><?= h($booking['category_name'] ?? 'Workshop') ?></td>

            <td>
              <strong><?= h($dateText) ?></strong>
            </td>

            <td>
              <strong><?= h($startText) ?> - <?= h($endText) ?></strong>
            </td>

            <td>
              <span class="booking-status-badge"><?= h($status['label']) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
      </section>
    <?php endif; ?>
  </div>
</main>

    <!-- ===== PROFILE PICTURE UPLOAD MODAL ===== -->
    <div class="profile-upload-overlay" id="profile-upload-overlay" hidden>
      <div class="profile-upload-modal" role="dialog" aria-modal="true" aria-labelledby="profile-upload-title">
        <div class="profile-upload-header">
          <div>
            <span class="profile-section-kicker">Profile Picture</span>
            <h2 id="profile-upload-title">Upload a Picture</h2>
          </div>

          <button type="button" class="profile-modal-close" id="profile-upload-close" aria-label="Close upload window">
            &times;
          </button>
        </div>

        <form method="post" action="profile.php" enctype="multipart/form-data" class="profile-upload-form">
          <input type="hidden" name="action" value="upload_profile_picture" />
          <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />

          <label for="profile_image" class="profile-upload-box">
            <i class="fa-solid fa-plus"></i>
            <span>Choose picture</span>
          </label>

          <input
            type="file"
            id="profile_image"
            name="profile_image"
            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
            class="profile-file-input"
            required
          />

          <p class="profile-file-name" id="profile-file-name">No file selected</p>

          <div class="profile-upload-rules">
            <strong>Upload rules</strong>
            <ul>
              <li>Allowed files: JPG, JPEG, PNG</li>
              <li>Maximum size: 2MB</li>
            </ul>
          </div>

          <button type="submit" class="btn btn-primary profile-upload-submit">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Picture
          </button>
        </form>
      </div>
    </div>

<!-- ===== CHANGE PASSWORD MODAL ===== -->
<div class="profile-upload-overlay" id="profile-password-overlay" hidden>
  <div class="profile-upload-modal profile-password-modal" role="dialog" aria-modal="true" aria-labelledby="profile-password-title">
    <div class="profile-upload-header">
      <div>
        <span class="profile-section-kicker">Security</span>
        <h2 id="profile-password-title">Change Password</h2>
      </div>

      <button type="button" class="profile-modal-close" id="profile-password-close" aria-label="Close password window">
        &times;
      </button>
    </div>

    <form method="post" action="profile.php" class="profile-password-form" novalidate>
      <input type="hidden" name="action" value="change_password" />

      <div class="form-group">
        <label for="current_password">Current Password <span class="required">*</span></label>
        <input
          type="password"
          id="current_password"
          name="current_password"
          autocomplete="current-password"
          required
        />
      </div>

      <div class="form-group">
        <label for="new_password">New Password <span class="required">*</span></label>
        <input
          type="password"
          id="new_password"
          name="new_password"
          autocomplete="new-password"
          required
        />
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
        <input
          type="password"
          id="confirm_password"
          name="confirm_password"
          autocomplete="new-password"
          required
        />
      </div>

      <button type="submit" class="btn btn-primary profile-upload-submit">
        <i class="fa-solid fa-lock"></i>
        Save Password
      </button>
    </form>
  </div>
</div>

    <!-- ===== FOOTER ===== -->
    <footer>
      <div class="container">
        <div id="footer-grid">
          <div id="footer-brand">
            <h3><i class="fa-solid fa-book-open"></i> SkillHub</h3>
            <p>
              Empowering students to discover and develop new skills through
              short, focused workshops.
            </p>
          </div>

          <div id="footer-links">
            <p class="footer-heading">Quick Links</p>
            <ul>
              <li><a href="../index.php">Home</a></li>
              <li><a href="services.php">Services</a></li>
              <li><a href="schedule.php">Schedule</a></li>
              <li><a href="video.php">Guide</a></li>
              <li><a href="feedback.php">Feedback</a></li>
              <li><a href="about.php">About</a></li>
            </ul>
          </div>

          <div id="footer-contact">
            <p class="footer-heading">Contact</p>
            <address>
              SkillHub Platform<br />
              University Campus, Building A<br />
              Email: info@skillhub.edu<br />
              Phone: +1 (555) 123-4567
            </address>
          </div>
        </div>
      </div>

      <div id="footer-copyright">
        &copy; 2026 SkillHub – Student Workshops Platform. All rights reserved.
      </div>
    </footer>

    <script src="../scripts/main.js"></script>
  </body>
</html>
