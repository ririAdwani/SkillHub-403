<?php
// register.php handles new user registration for SkillHub.
// It validates the form, saves the user in the database, hashes the password,
// assigns the default role "user", and sends a welcome email.
// Register: reached from Login page only.

// Scenario:
// 1. User opens register page.
// 2. User enters full name, email, and password.
// 3. System saves user with role "user".
// 4. System shows "Account created successfully" and sends a welcome email.
// 5. User logs in from the login page.
// 6. User accesses the system.

// Registration backend flow:
// 1. User enters full name, email, password, and password confirmation.
// 2. System checks that the email is not already used.
// 3. Password is hashed before storage.
// 4. Role is saved as "user".
// 5. Welcome email is sent.
// 6. Success message appears and user goes to login.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect already logged-in users away from registration because they already have an active account session.
if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

// These variables store validation messages and keep safe form values after an error.
$errors = [];
$successMessage = '';
$emailWarning = '';
$fullName = '';
$email = '';

// This block runs only after the user submits the registration form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate the full name because it is required for the user profile.
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullName) > 100) {
        $errors[] = 'Full name is too long';
    }

    // Validate email because it is used as the unique login identifier.
    //This will reject:
    // 1@y.com
    // a@gmail.com
    // test@y.com
    // test@gmail.c
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (mb_strlen($email) > 150) {
        $errors[] = 'Email address must be 150 characters or less.';
    } elseif (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address, such as name@example.com.';
    }

    // Validate password strength first.
    // Password confirmation is checked only if the password itself is valid.
    $passwordErrors = validate_password($password, $fullName, $email);

    if (!empty($passwordErrors)) {
        $errors[] = 'Password does not meet the security requirements.';
    } else {
        // Confirm that the user typed the same password twice only after the password is valid.
        if ($confirmPassword === '') {
            $errors[] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
    }

    // Database work starts only after the form passes validation.
    if (empty($errors)) {
        try {
            // Check if the email is already registered before creating a new account.
            $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch()) {
                $errors[] = 'This email is already registered. Please log in instead.';
            } else {
                // Hash the password so the plain-text password is never stored in the database.
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Save the new user with the default regular user role.
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role)
                     VALUES (:full_name, :email, :password_hash, :role)'
                );

                $insertStmt->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'role' => 'user',
                ]);

                // Build a clear welcome email that matches the registration action.
                $subject = 'Welcome to SkillHub';
                $emailBody = "Hello {$fullName},\n\n"
                    . "Welcome to SkillHub. Your account has been created successfully.\n\n"
                    . "You can now log in to browse workshops, book sessions, and manage your profile.\n\n"
                    . "Next step:\n"
                    . "Log in to your account and choose a workshop from the Services page.\n\n"
                    . "Thank you,\n"
                    . "SkillHub Team";

                // These headers identify SkillHub as the sender and send the email as plain text.
                $headers = "From: SkillHub <no-reply@skillhub.local>\r\n"
                    . "Reply-To: no-reply@skillhub.local\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";

                // mail() returns false if the server does not accept the email for sending.
                $emailSent = @mail($email, $subject, $emailBody, $headers);

                $successMessage = 'Account created successfully. You can now log in.';

                // The account is still created even if the email fails, but the user is clearly warned.
                if (!$emailSent) {
                    $emailWarning = ' account was created, the welcome email could not be sent.';
                }

                // Clear the form after successful registration.
                $fullName = '';
                $email = '';
            }
        } catch (PDOException $e) {
            // Show a user-friendly error instead of exposing database details.
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register – SkillHub</title>

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../global/main.css" />
  </head>

  <body>
    <!-- ===== SIMPLE REGISTER HEADER ===== -->
    <!--
      This header is intentionally minimal.
      The full guest navbar is not shown here because the user reached this page
      from Login/Register flow, so the page should focus on account creation.
    -->
    <header>
      <div class="container header-container">
        <div id="header-inner" class="auth-header-inner">
          <a href="../index.php" id="site-logo">
            <div id="logo-icon"><i class="fa-solid fa-book-open"></i></div>
            <span id="site-name">Skill<span>Hub</span></span>
          </a>

        <a href="login.php" class="auth-header-link">
        <i class="fa-solid fa-right-to-bracket"></i>
        <span>Login</span>
        </a>
        </div>
      </div>
    </header>

    <!-- ===== REGISTER TOAST NOTIFICATION ===== -->
    <!--
    This toast appears after account creation.
    It gives the user a short success/warning message without placing
    a large message box inside the registration form.
    -->
    <?php if ($successMessage !== ''): ?>
    <div class="toast-container" role="status" aria-live="polite">
        <div class="toast-message <?= $emailWarning !== '' ? 'toast-warning' : 'toast-success' ?>">
        <div class="toast-icon">
            <?php if ($emailWarning !== ''): ?>
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php else: ?>
            <i class="fa-solid fa-circle-check"></i>
            <?php endif; ?>
        </div>

        <div class="toast-content">
            <strong><?= h($successMessage) ?></strong>

            <?php if ($emailWarning !== ''): ?>
            <span><?= h($emailWarning) ?></span>
            <?php else: ?>
            <span>A welcome email has been sent to your email address.</span>
            <?php endif; ?>
        </div>

        <a href="login.php" class="toast-action">Login</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== REGISTER HERO ===== -->
    <!--
      This section introduces the register page and explains why the user is creating an account.
    -->
<div class="page-hero auth-hero">
      <div class="container">
        <div class="badge">
          <i class="fa-solid fa-user-plus"></i> Join SkillHub
        </div>
        <h1>Create Your Account</h1>
        <p>Register to book workshops, manage your sessions, and access your profile.</p>
      </div>
    </div>

    <!-- ===== REGISTER FORM ===== -->
    <!--
      This form collects the required registration information:
      full name, email, password, and password confirmation.
    -->
    <section class="section auth-section">
          <div class="container auth-container">
          <form method="post" action="register.php" class="form-card auth-card" novalidate>          <!-- This block displays validation errors collected from the PHP backend. -->
          <?php if (!empty($errors)): ?>
            <div class="auth-message auth-message-error">
              <strong>Please fix the following:</strong>
              <ul>
                <?php foreach ($errors as $error): ?>
                  <li><?= h($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>


          <!-- This block collects the user identity information. -->
          <div class="form-group">
            <label for="full_name">Full Name <span class="required">*</span></label>
            <input
              type="text"
              id="full_name"
              name="full_name"
              placeholder="Enter your full name"
              value="<?= h($fullName) ?>"
              autocomplete="name"
              required
            />
          </div>

          <div class="form-group">
            <label for="email">Email Address <span class="required">*</span></label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="your.email@example.com"
              value="<?= h($email) ?>"
              autocomplete="email"
              required
            />
          </div>

          <!-- This block collects and confirms the account password. -->
          <div class="form-row">
            <div class="form-group">
              <label for="password">Password <span class="required">*</span></label>
                <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="new-password"
                required
                />
                <div class="password-help">
                <button type="button" class="password-help-btn" aria-label="Show password requirements">
                    <i class="fa-solid fa-circle-info"></i>
                </button>

                <div class="password-tooltip">
                    <strong>Password must include:</strong>
                    <ul>
                    <li>At least 8 characters</li>
                    <li>Uppercase and lowercase letters</li>
                    <li>At least one number</li>
                    <li>At least one special character</li>
                    </ul>
                </div>
                </div>
            </div>

            <div class="form-group">
              <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter password"
                    autocomplete="new-password"
                    required
                />
            </div>
          </div>

          <!-- This block submits the form and provides a login link for existing users. -->
          <button type="submit" class="btn btn-primary auth-submit-btn">
            <i class="fa-solid fa-user-plus"></i> Create Account
          </button>

          <p class="auth-helper-text">
            Already have an account?
            <a href="login.php" class="bold">Log in</a>
          </p>
        </form>
      </div>
    </section>

    <script src="../scripts/main.js"></script>
  </body>
</html>