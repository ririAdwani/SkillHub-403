<?php
// logout.php ends the current user session.
// It clears session data, destroys the session, and redirects the user to login.php.

// Logout backend flow:
// 1. User clicks Logout.
// 2. System clears all session variables.
// 3. System removes the session cookie if sessions use cookies.
// 4. System destroys the session.
// 5. User is redirected to login.php with a logout confirmation message.

require_once __DIR__ . '/../includes/auth.php';

// Use the shared helper from auth.php so logout behavior stays consistent.
logout_user();

// Redirect after logout so the user is not left on a blank PHP page.
header('Location: login.php?logged_out=1');
exit;