<?php
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
        // Use __DIR__ to build a reliable path to login.php
        // auth.php lives in includes/ so login.php is at includes/../pages/login.php
        $loginUrl = dirname(__DIR__) . '/pages/login.php';
        
        // For HTTP redirect we need the web URL, not filesystem path
        // Build it from SERVER variables safely
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Get the project root from SCRIPT_NAME
        // e.g. /SkillHub-403/pages/schedule.php → root = /SkillHub-403
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $pathParts  = array_values(array_filter(explode('/', $scriptName)));
        $projectRoot = isset($pathParts[0]) ? '/' . $pathParts[0] : '';
        
        header('Location: ' . $scheme . '://' . $host . $projectRoot . '/pages/login.php');
        exit;
    }
}

function require_admin(): void
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $scriptName  = $_SERVER['SCRIPT_NAME'] ?? '';
    $pathParts   = array_values(array_filter(explode('/', $scriptName)));
    $projectRoot = isset($pathParts[0]) ? '/' . $pathParts[0] : '';
    
    if (!is_logged_in()) {
        header('Location: ' . $scheme . '://' . $host . $projectRoot . '/pages/login.php');
        exit;
    }

    if (!is_admin()) {
        header('Location: ' . $scheme . '://' . $host . $projectRoot . '/index.php');
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

function is_valid_email(string $email): bool
{
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    [$localPart, $domain] = explode('@', $email, 2);

    if (strlen($localPart) < 2) return false;
    if (strlen($domain) < 6) return false;

    $domainParts = explode('.', $domain);
    if (count($domainParts) < 2) return false;

    $domainName   = $domainParts[count($domainParts) - 2];
    $topLevelDomain = $domainParts[count($domainParts) - 1];

    if (strlen($domainName) < 3) return false;
    if (strlen($topLevelDomain) < 2) return false;

    return true;
}

function validate_password(string $password, string $fullName = '', string $email = ''): array
{
    $errors = [];

    if ($password === '') {
        $errors[] = 'Password is required.';
        return $errors;
    }

    if (strlen($password) < 8)  $errors[] = 'Password must be at least 8 characters.';
    if (strlen($password) > 64) $errors[] = 'Password must be 64 characters or less.';
    if (preg_match('/\s/', $password)) $errors[] = 'Password must not contain spaces.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must include at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must include at least one number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must include at least one special character.';

    $emailLocalPart = strtolower(explode('@', $email)[0] ?? '');
    if ($emailLocalPart !== '' && strlen($emailLocalPart) >= 3 && str_contains(strtolower($password), $emailLocalPart)) {
        $errors[] = 'Password must not contain your email name.';
    }

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