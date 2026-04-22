<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$isJsonRequest = isJsonRequest();
$redirectToIndex = static function (string $message): void {
    setFlash('error', $message);
    redirectTo('../index.php');
};
$respondError = static function (string $message, array $errors = [], int $statusCode = 422) use ($isJsonRequest, $redirectToIndex): void {
    if ($isJsonRequest) {
        respondJson([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    $redirectToIndex($message);
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJsonRequest) {
        respondJson([
            'success' => false,
            'message' => 'This request is not allowed.',
        ], 405);
    }

    redirectTo('../index.php');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$errors = [];

if ($email === '') {
    $errors['email'] = 'Enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}

if ($password === '') {
    $errors['password'] = 'Enter your password.';
}

if ($errors !== []) {
    $respondError('Please check your details and try again.', $errors);
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $stmt->close();
    closeDBConnection($conn);
} catch (Throwable $exception) {
    error_log('Login failed: ' . $exception->getMessage());
    $respondError('Unable to sign you in right now. Please try again.', [], 500);
}

if (!$user) {
    $respondError('No account found with this email.', [
        'email' => 'No account found with this email.',
    ]);
}

if (!password_verify($password, (string) $user['password'])) {
    $respondError('Invalid email or password. Please try again.', [
        'password' => 'Invalid email or password. Please try again.',
    ]);
}

setAuthenticatedUser($user);
setFlash('success', "Welcome back! You're signed in.");

if ($isJsonRequest) {
    respondJson([
        'success' => true,
        'message' => "Welcome back! You're signed in.",
        'redirect' => 'search.php',
    ]);
}

redirectTo('../search.php');
?>
