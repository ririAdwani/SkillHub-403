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
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

/*
 * Builds a safe redirect path from any folder level.
 * This avoids broken redirects from nested pages like /pages/Admin/admin.php.
 */
function app_url(string $path = ''): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = '';

    $pagesPos = strpos($scriptName, '/pages/');
    if ($pagesPos !== false) {
        $basePath = substr($scriptName, 0, $pagesPos);
    } else {
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '/') {
            $basePath = '';
        }
    }

    return $basePath . '/' . ltrim($path, '/');
}

/*
 * UI-level admin check.
 * Used for navbar display only. Real admin-page protection happens in require_admin().
 */
function is_admin(): bool
{
    return is_logged_in() && current_user_role() === 'admin';
}

/*
 * Blocks pages that require any logged-in user.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . app_url('pages/login.php'));
        exit;
    }
}


/*
 * Blocks admin pages from guests and regular users.
 * The database role is checked again instead of trusting only the session.
 */
function require_admin(): void
{
    // Build redirect to project homepage from any folder level.
    // Example: /SkillHub-403/pages/Admin/admin.php -> /SkillHub-403/index.php
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $pathParts = array_values(array_filter(explode('/', $scriptName)));
    $projectRoot = isset($pathParts[0]) ? '/' . $pathParts[0] : '';

    $homeUrl = $projectRoot . '/index.php';

    // Guests and regular users are both sent home.
    // This avoids exposing the login page as the admin-access response.
    if (!is_logged_in() || !is_admin()) {
        header('Location: ' . $homeUrl);
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