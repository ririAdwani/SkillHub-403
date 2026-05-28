<?php
// logout.php handles secure user logout for SkillHub.
// It ends the current session, clears the session cookie, and redirects the user
// back to login.php with a logout confirmation message.

// Logout backend flow:
// 1. User clicks Logout in the navbar.
// 2. System loads auth.php so the active session can be accessed.
// 3. System clears all session data.
// 4. System removes the session cookie if PHP is using session cookies.
// 5. System destroys the session.
// 6. User is redirected to login.php with a confirmation message.

require_once __DIR__ . '/../includes/auth.php';

// Clears session variables, deletes the session cookie, and destroys the session.
logout_user();

// Redirect user after logout so they do not stay on a blank PHP page.
header('Location: ../index.php');
exit;