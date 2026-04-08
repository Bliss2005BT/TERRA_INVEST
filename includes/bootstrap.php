<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

const APP_NAME = 'Terra Invest Co.';
const IMAGE_UPLOAD_DIR = __DIR__ . '/../assets/uploads/images/';
const VIDEO_UPLOAD_DIR = __DIR__ . '/../assets/uploads/videos/';
const ROOT_IMAGE_UPLOAD_WEB = 'assets/uploads/images/';
const ROOT_VIDEO_UPLOAD_WEB = 'assets/uploads/videos/';
const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
const MAX_VIDEO_SIZE = 20 * 1024 * 1024;

function isUserLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function getCurrentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function getCurrentUserName(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

function getCurrentUserEmail(): string
{
    return $_SESSION['user_email'] ?? '';
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit();
}

function requireLogin(): void
{
    if (!isUserLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirectTo('../index.php?error=' . urlencode('Please log in to continue.'));
    }
}

function esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function getActiveSubscription(?int $userId = null): ?array
{
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) {
        return null;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'SELECT id, user_id, plan_type, created_at
         FROM subscriptions
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc() ?: null;
    $stmt->close();
    closeDBConnection($conn);

    return $subscription;
}

function requireSubscription(): array
{
    $subscription = getActiveSubscription();

    if (!$subscription) {
        setFlash('error', 'Please select a subscription plan before adding a listing.');
        redirectTo('../pages/subscription.php');
    }

    return $subscription;
}

function normalizeText(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function validateYouTubeUrl(string $url): bool
{
    if ($url === '') {
        return true;
    }

    return (bool) preg_match(
        '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{6,}$/',
        $url
    );
}

function ensureUploadDirectory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function buildUploadName(string $originalName): string
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('file_', true) . '.' . $extension;
}

function storeUploadedFile(array $file, string $targetDir, array $allowedExtensions, int $maxSize, string $webPrefix): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed.'];
    }

    if (($file['size'] ?? 0) > $maxSize) {
        return ['success' => false, 'message' => 'File exceeds the allowed size limit.'];
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Invalid file format uploaded.'];
    }

    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'mp4' => 'video/mp4',
    ];
    $detectedMime = $file['type'] ?? '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if (isset($mimeMap[$extension]) && $detectedMime !== $mimeMap[$extension]) {
        return ['success' => false, 'message' => 'Uploaded file content does not match the selected format.'];
    }

    ensureUploadDirectory($targetDir);
    $fileName = buildUploadName($file['name']);
    $destination = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Unable to save uploaded file.'];
    }

    return [
        'success' => true,
        'path' => $webPrefix . $fileName,
    ];
}

function getListingById(int $listingId): ?array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'SELECT l.*, u.name AS seller_name, u.email AS seller_email
         FROM listings l
         INNER JOIN users u ON u.id = l.user_id
         WHERE l.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $listingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $listing = $result->fetch_assoc() ?: null;
    $stmt->close();
    closeDBConnection($conn);

    if ($listing) {
        $listing['image_list'] = json_decode($listing['images'] ?? '[]', true) ?: [];
    }

    return $listing;
}
?>
