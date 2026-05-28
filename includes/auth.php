<?php
// SkillHub authentication helpers.
// Include this file on pages that need login or role checks.
// auth.php is for session/login checks 

// This contains authentication/session helper logic:
// - check if user is logged in
// - check if user is admin
// - redirect unauthorized users
// - logout helper 


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function is_admin(): bool
{
    return is_logged_in() && current_user_role() === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// Validates email using PHP email syntax rules plus stricter SkillHub rules.
// This prevents very weak emails such as "1@y.com" from being accepted.
function is_valid_email(string $email): bool
{
    $email = trim($email);

    // First, use PHP's built-in email validation for standard email syntax.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Split the email into local part and domain part.
    [$localPart, $domain] = explode('@', $email, 2);

    // SkillHub rule: the part before @ should be at least 2 characters.
    if (strlen($localPart) < 2) {
        return false;
    }

    // SkillHub rule: the full domain should be reasonable, not like "y.com".
    if (strlen($domain) < 6) {
        return false;
    }

    // Split domain into labels, for example "gmail.com" becomes ["gmail", "com"].
    $domainParts = explode('.', $domain);

    // Email domain must include at least a name and a top-level domain.
    if (count($domainParts) < 2) {
        return false;
    }

    $domainName = $domainParts[count($domainParts) - 2];
    $topLevelDomain = $domainParts[count($domainParts) - 1];

    // SkillHub rule: reject domains like "y.com".
    if (strlen($domainName) < 3) {
        return false;
    }

    // SkillHub rule: top-level domain must be at least 2 characters, like .com or .edu.
    if (strlen($topLevelDomain) < 2) {
        return false;
    }

    return true;
}

// Validates password strength using SkillHub security rules.
// This is used when creating a new account or changing a password.
// Validation rule:
// - at least 8 characters
// - maximum 64 characters
// - at least one uppercase letter
// - at least one lowercase letter
// - at least one number
// - at least one special character
// - must not contain spaces
// - must not contain the user’s name or email
function validate_skillhub_password(string $password, string $fullName = '', string $email = ''): array
{
    $errors = [];

    // Password must not be empty.
    if ($password === '') {
        $errors[] = 'Password is required.';
        return $errors;
    }

    // Minimum length rule.
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // Maximum length rule. OWASP recommends allowing long passwords, but keeping a limit prevents abuse.
    if (strlen($password) > 64) {
        $errors[] = 'Password must be 64 characters or less.';
    }

    // Spaces are blocked to avoid accidental leading/trailing password mistakes.
    if (preg_match('/\s/', $password)) {
        $errors[] = 'Password must not contain spaces.';
    }

    // Password should include at least one uppercase letter.
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }

    // Password should include at least one lowercase letter.
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }

    // Password should include at least one number.
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    // Password should include at least one special character.
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must include at least one special character.';
    }

    // Prevent using part of the email address as the password.
    $emailLocalPart = strtolower(explode('@', $email)[0] ?? '');
    if ($emailLocalPart !== '' && strlen($emailLocalPart) >= 3 && str_contains(strtolower($password), $emailLocalPart)) {
        $errors[] = 'Password must not contain your email name.';
    }

    // Prevent using the full name inside the password.
    $cleanName = strtolower(str_replace(' ', '', $fullName));
    if ($cleanName !== '' && strlen($cleanName) >= 3 && str_contains(strtolower($password), $cleanName)) {
        $errors[] = 'Password must not contain your full name.';
    }

    return $errors;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


