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

$name = normalizeText((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$errors = [];

if ($name === '') {
    $errors['name'] = 'Enter your full name.';
} elseif (mb_strlen($name) < 2) {
    $errors['name'] = 'Enter your full name.';
}

if ($email === '') {
    $errors['email'] = 'Enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}

if ($password === '') {
    $errors['password'] = 'Create a password.';
} elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors['password'] = 'Use at least 8 characters with letters and numbers.';
}

if ($confirmPassword === '') {
    $errors['confirm_password'] = 'Confirm your password.';
} elseif ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if ($errors !== []) {
    $respondError('Please check your details and try again.', $errors);
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $emailExists = $result->fetch_assoc() !== null;
    $stmt->close();

    if ($emailExists) {
        closeDBConnection($conn);
        $respondError('This email is already registered.', [
            'email' => 'This email is already registered.',
        ]);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $name, $email, $hashedPassword, $createdAt);
    $stmt->execute();
    $userId = (int) $stmt->insert_id;
    $stmt->close();
    closeDBConnection($conn);
} catch (mysqli_sql_exception $exception) {
    if ((int) $exception->getCode() === 1062) {
        $respondError('This email is already registered.', [
            'email' => 'This email is already registered.',
        ]);
    }

    error_log('Registration failed: ' . $exception->getMessage());
    $respondError('Unable to create your account right now. Please try again.', [], 500);
} catch (Throwable $exception) {
    error_log('Registration failed: ' . $exception->getMessage());
    $respondError('Unable to create your account right now. Please try again.', [], 500);
}

setAuthenticatedUser([
    'id' => $userId,
    'name' => $name,
    'email' => $email,
]);
setFlash('success', 'Account created successfully! Start exploring properties.');

if ($isJsonRequest) {
    respondJson([
        'success' => true,
        'message' => 'Account created successfully! Start exploring properties.',
        'redirect' => 'search.php',
    ]);
}

redirectTo('../search.php');
?>
