<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

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

function getSubscriptionPlans(): array
{
    return [
        'basic' => [
            'name' => 'Basic',
            'price' => 199,
            'listing_limit' => 1,
            'image_limit' => 5,
            'video_allowed' => false,
            'youtube_allowed' => false,
            'duration_days' => 7,
            'visibility' => 'Standard visibility in search',
            'restrictions' => [
                'Cannot upload video',
                'Cannot exceed 5 images',
                'Only 1 active listing allowed',
            ],
        ],
        'premium' => [
            'name' => 'Premium',
            'price' => 499,
            'listing_limit' => 3,
            'image_limit' => 15,
            'video_allowed' => true,
            'youtube_allowed' => false,
            'duration_days' => 30,
            'visibility' => 'Priority placement above Basic listings',
            'restrictions' => [
                'Maximum 3 active listings',
                'Maximum 15 images per listing',
            ],
            'popular' => true,
        ],
        'featured' => [
            'name' => 'Featured',
            'price' => 999,
            'listing_limit' => null,
            'image_limit' => null,
            'video_allowed' => true,
            'youtube_allowed' => true,
            'duration_days' => 60,
            'visibility' => 'Top position in search results',
            'restrictions' => [
                'Unlimited listings',
                'Unlimited images',
            ],
            'recommended' => true,
        ],
    ];
}

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

function normalizePlanType(string $planType): string
{
    return strtolower(normalizeText($planType));
}

function getPlanConfig(string $planType): ?array
{
    $planType = normalizePlanType($planType);
    $plans = getSubscriptionPlans();

    return $plans[$planType] ?? null;
}

function formatPlanName(string $planType): string
{
    $plan = getPlanConfig($planType);

    return $plan['name'] ?? ucfirst(normalizePlanType($planType));
}

function getPlanBadgeLabel(string $planType): string
{
    $planType = normalizePlanType($planType);

    if ($planType === 'featured') {
        return 'Featured';
    }

    if ($planType === 'premium') {
        return 'Premium';
    }

    return 'Basic';
}

function getPlanCardClass(string $planType): string
{
    return 'plan-' . normalizePlanType($planType);
}

function getListingPlanCaseSql(string $columnName): string
{
    return "CASE
        WHEN LOWER({$columnName}) = 'featured' THEN 60
        WHEN LOWER({$columnName}) = 'premium' THEN 30
        ELSE 7
    END";
}

function getListingActiveSql(string $createdAtColumn = 'created_at', string $planTypeColumn = 'subscription_type'): string
{
    $durationSql = getListingPlanCaseSql($planTypeColumn);

    return "DATE_ADD({$createdAtColumn}, INTERVAL {$durationSql} DAY) >= NOW()";
}

function getListingExpiryDate(string $createdAt, string $planType): string
{
    $plan = getPlanConfig($planType);
    $durationDays = $plan['duration_days'] ?? 7;

    return date('Y-m-d H:i:s', strtotime($createdAt . ' +' . $durationDays . ' days'));
}

function formatDisplayDate(string $value, string $format = 'd M Y'): string
{
    $timestamp = strtotime($value);
    if (!$timestamp) {
        return '';
    }

    return date($format, $timestamp);
}

function countActiveListingsForUser(int $userId): int
{
    $conn = getDBConnection();
    $activeListingSql = getListingActiveSql('created_at', 'subscription_type');
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM listings
         WHERE user_id = ?
           AND {$activeListingSql}"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = (int) (($result->fetch_assoc()['total'] ?? 0));
    $stmt->close();
    closeDBConnection($conn);

    return $total;
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit();
}

function requireLogin(string $redirectPath = '../index.php'): void
{
    if (!isUserLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirectTo($redirectPath . '?error=' . urlencode('Please log in to continue.'));
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
    $subscriptionExpirySql = "DATE_ADD(created_at, INTERVAL " . getListingPlanCaseSql('plan_type') . " DAY)";
    $stmt = $conn->prepare(
        "SELECT id, user_id, plan_type, created_at, {$subscriptionExpirySql} AS expiry_date
         FROM subscriptions
         WHERE user_id = ?
           AND {$subscriptionExpirySql} >= NOW()
         ORDER BY created_at DESC, id DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc() ?: null;
    $stmt->close();
    closeDBConnection($conn);

    if ($subscription) {
        $subscription['plan_type'] = normalizePlanType($subscription['plan_type']);
        $subscription['plan_name'] = formatPlanName($subscription['plan_type']);
        $subscription['badge_label'] = getPlanBadgeLabel($subscription['plan_type']);
        $plan = getPlanConfig($subscription['plan_type']) ?? [];
        $subscription['listing_limit'] = isset($plan['listing_limit']) && $plan['listing_limit'] !== null
            ? (int) $plan['listing_limit']
            : null;
        $subscription['image_limit'] = isset($plan['image_limit']) && $plan['image_limit'] !== null
            ? (int) $plan['image_limit']
            : null;
        $subscription['video_allowed'] = (bool) ($plan['video_allowed'] ?? false);
        $subscription['youtube_allowed'] = (bool) ($plan['youtube_allowed'] ?? false);
    }

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
    $activeListingSql = getListingActiveSql('l.created_at', 'l.subscription_type');
    $stmt = $conn->prepare(
        'SELECT l.*, u.name AS seller_name, u.email AS seller_email
         FROM listings l
         INNER JOIN users u ON u.id = l.user_id
         WHERE ' . $activeListingSql . '
           AND l.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $listingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $listing = $result->fetch_assoc() ?: null;
    $stmt->close();
    closeDBConnection($conn);

    if ($listing) {
        $listing['subscription_type'] = normalizePlanType($listing['subscription_type'] ?? 'basic');
        $listing['subscription_name'] = formatPlanName($listing['subscription_type']);
        $listing['badge_label'] = getPlanBadgeLabel($listing['subscription_type']);
        $listing['listing_expiry_date'] = getListingExpiryDate($listing['created_at'], $listing['subscription_type']);
        $listing['image_list'] = json_decode($listing['images'] ?? '[]', true) ?: [];
    }

    return $listing;
}
?>
