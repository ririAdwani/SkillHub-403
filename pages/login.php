<?php
// Login page: includes link to Register 
//  Login backend flow
// 1. User enters email + password
// 2. System verifies password
// 3. Starts session
// 4. If role = admin → admin.php
// 5. If role = user → home index 

// Access control
// Non-logged-in user trying to open profile.php → redirect to login.php
// Regular user trying to open admin.php → redirect to home/profile
// Admin pages must not only hide buttons, they must block access from backend 

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect already logged-in users away from the login page.
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: Admin/admin.php');
        exit;
    }

    header('Location: ../index.php');
    exit;
}

// These variables store form messages and keep the email value after an error.
$errors = [];
$infoMessage = '';
$email = '';

// This message appears when a guest tries to book a workshop before logging in.
if (isset($_GET['reason']) && $_GET['reason'] === 'booking') {
    $infoMessage = 'Please log in or register to book a workshop.';
}

// This message appears after logout.
if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    $infoMessage = 'You have been logged out successfully.';
}

// This block runs only when the user submits the login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required login fields before checking the database.
    //This will reject:
    // 1@y.com
    // a@gmail.com
    // test@y.com
    // test@gmail.c
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address, such as name@example.com.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    // Database check starts only after the form passes basic validation.
    if (empty($errors)) {
        try {
            // Find the user by email. Email is unique in the users table.
            $stmt = $pdo->prepare(
                'SELECT user_id, full_name, email, password_hash, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Use one generic error message so attackers cannot know which part is wrong.
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
            } else {
                // Regenerate session ID after successful login to reduce session fixation risk.
                session_regenerate_id(true);

                // Store only needed user data in the session.
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role.
                if ($user['role'] === 'admin') {
                    header('Location: Admin/admin.php');
                    exit;
                }

                header('Location: ../index.php');
                exit;
            }
        } catch (PDOException $e) {
            // Show a safe error message instead of exposing database details.
            $errors[] = 'Login failed. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login – SkillHub</title>

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="../global/main.css" />
  </head>

  <body>
    <!-- ===== SIMPLE LOGIN HEADER ===== -->
    <!--
      This header is intentionally minimal.
      The login page focuses on authentication, so it only shows the logo
      and a Register button for new users.
    -->
    <header>
      <div class="container">
        <div id="header-inner">
          <a href="../index.html" id="site-logo">
            <div id="logo-icon"><i class="fa-solid fa-book-open"></i></div>
            <span id="site-name">Skill<span>Hub</span></span>
          </a>

            <a href="register.php" class="auth-header-link">
            <i class="fa-solid fa-user-plus"></i>
            <span>Register</span>
            </a>
        </div>
      </div>
    </header>

    <!-- ===== LOGIN HERO ===== -->
    <!--
      This section introduces the login page and explains what the user
      can access after logging in.
    -->
    <div class="page-hero auth-hero">
      <div class="container">
        <div class="badge">
          <i class="fa-solid fa-right-to-bracket"></i> SkillHub Login
        </div>
        <h1>Welcome Back</h1>
        <p>Log in to book workshops, access your profile, and manage your sessions.</p>
      </div>
    </div>

    <!-- ===== LOGIN FORM ===== -->
    <!--
      This form collects the user email and password.
      The backend verifies the password using the stored password hash.
    -->
    <section class="section auth-section">
        <div class="container auth-container">
         <form method="post" action="login.php" class="form-card auth-card" novalidate>          <!-- This block displays information messages such as logout or booking redirect notices. -->
          <?php if ($infoMessage !== ''): ?>
            <div class="auth-message auth-message-info">
              <i class="fa-solid fa-circle-info"></i> <?= h($infoMessage) ?>
            </div>
          <?php endif; ?>

          <!-- This block displays validation or login errors collected from the backend. -->
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

          <!-- This block collects the login credentials. -->
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

          <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            />
          </div>

          <!-- This block submits the form and provides a register link for new users. -->
          <button type="submit" class="btn btn-primary auth-submit-btn">
            <i class="fa-solid fa-right-to-bracket"></i> Login
          </button>

          <p class="auth-helper-text">
            Don’t have an account?
            <a href="register.php" class="bold">Register here</a>
          </p>
        </form>
      </div>
    </section>

    <script src="../scripts/main.js"></script>
  </body>
</html>