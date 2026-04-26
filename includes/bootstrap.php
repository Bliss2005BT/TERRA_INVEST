<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

const APP_NAME = 'Terra Invest Co.';
const IMAGE_UPLOAD_DIR = __DIR__ . '/../assets/uploads/images/';
const VIDEO_UPLOAD_DIR = __DIR__ . '/../assets/uploads/videos/';
const DOCUMENT_UPLOAD_DIR = __DIR__ . '/../uploads/documents/';
const ROOT_IMAGE_UPLOAD_WEB = 'assets/uploads/images/';
const ROOT_VIDEO_UPLOAD_WEB = 'assets/uploads/videos/';
const ROOT_DOCUMENT_UPLOAD_WEB = 'uploads/documents/';
const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
const MAX_VIDEO_SIZE = 20 * 1024 * 1024;
const MAX_DOCUMENT_SIZE = 5 * 1024 * 1024;

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

function isJsonRequest(): bool
{
    $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    return str_contains($acceptHeader, 'application/json')
        || $requestedWith === 'xmlhttprequest';
}

function respondJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit();
}

function setAuthenticatedUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['user_name'] = normalizeText((string) ($user['name'] ?? ''));
    $_SESSION['user_email'] = strtolower(trim((string) ($user['email'] ?? '')));
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

function formatListingPrice(float $price): string
{
    return 'Rs. ' . number_format($price, 2);
}

function formatListingArea(float $area): string
{
    $squareFeet = number_format($area, 2) . ' sq ft';

    if ($area >= 43560) {
        return $squareFeet . ' / ' . number_format($area / 43560, 2) . ' acres';
    }

    return $squareFeet;
}

function formatListingLocation(string $location): string
{
    $parts = array_values(array_filter(array_map('trim', explode(',', $location)), static fn ($part): bool => $part !== ''));

    if (count($parts) >= 2) {
        return $parts[0] . ', ' . $parts[count($parts) - 1];
    }

    return $location;
}

function parseListingAmenities(string $amenities): array
{
    if (trim($amenities) === '') {
        return [];
    }

    $items = preg_split('/[\r\n,]+/', $amenities) ?: [];

    return array_values(array_filter(array_map('normalizeText', $items), static fn ($item): bool => $item !== ''));
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
        redirectTo($redirectPath);
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

function validateExternalUrl(string $url): bool
{
    if ($url === '') {
        return true;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function isExternalUrl(string $value): bool
{
    return (bool) preg_match('/^https?:\/\//i', $value);
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

function sanitizeUploadedBaseName(string $originalName): string
{
    $baseName = basename($originalName);
    $baseName = preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName) ?? '';
    $baseName = trim($baseName, '._-');

    return $baseName !== '' ? $baseName : 'document';
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

function storeDocumentFile(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed. Try again'];
    }

    if (($file['size'] ?? 0) > MAX_DOCUMENT_SIZE) {
        return ['success' => false, 'message' => 'File size must be less than 5MB'];
    }

    $originalName = sanitizeUploadedBaseName((string) ($file['name'] ?? ''));
    $nameParts = explode('.', $originalName);
    if (count($nameParts) > 2) {
        return ['success' => false, 'message' => 'Only PDF, JPG, PNG files are allowed'];
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Only PDF, JPG, PNG files are allowed'];
    }

    $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com', 'js', 'jsp', 'asp', 'aspx'];
    foreach ($nameParts as $index => $part) {
        if ($index === count($nameParts) - 1) {
            continue;
        }

        if (in_array(strtolower($part), $dangerousExtensions, true)) {
            return ['success' => false, 'message' => 'Only PDF, JPG, PNG files are allowed'];
        }
    }

    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    $detectedMime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMime = (string) finfo_file($finfo, (string) ($file['tmp_name'] ?? ''));
            finfo_close($finfo);
        }
    }

    if ($detectedMime === '' || !in_array($detectedMime, $allowedMimes, true)) {
        return ['success' => false, 'message' => 'Only PDF, JPG, PNG files are allowed'];
    }

    ensureUploadDirectory(DOCUMENT_UPLOAD_DIR);
    $storedName = uniqid('', true) . '_' . $originalName;
    $destination = DOCUMENT_UPLOAD_DIR . $storedName;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $destination)) {
        return ['success' => false, 'message' => 'Upload failed. Try again'];
    }

    return [
        'success' => true,
        'path' => ROOT_DOCUMENT_UPLOAD_WEB . $storedName,
    ];
}

function deleteStoredAsset(string $path): void
{
    $normalizedPath = trim(str_replace('\\', '/', $path));
    if ($normalizedPath === '') {
        return;
    }

    $relativePath = null;
    if (str_starts_with($normalizedPath, ROOT_IMAGE_UPLOAD_WEB)) {
        $relativePath = substr($normalizedPath, strlen(ROOT_IMAGE_UPLOAD_WEB));
        $baseDir = IMAGE_UPLOAD_DIR;
    } elseif (str_starts_with($normalizedPath, ROOT_VIDEO_UPLOAD_WEB)) {
        $relativePath = substr($normalizedPath, strlen(ROOT_VIDEO_UPLOAD_WEB));
        $baseDir = VIDEO_UPLOAD_DIR;
    } elseif (str_starts_with($normalizedPath, ROOT_DOCUMENT_UPLOAD_WEB)) {
        $relativePath = substr($normalizedPath, strlen(ROOT_DOCUMENT_UPLOAD_WEB));
        $baseDir = DOCUMENT_UPLOAD_DIR;
    } else {
        return;
    }

    if ($relativePath === false || $relativePath === '' || preg_match('#(^|/)\.\.(/|$)#', $relativePath)) {
        return;
    }

    $fullPath = realpath($baseDir . $relativePath);
    $basePath = realpath($baseDir);
    if ($fullPath && $basePath && str_starts_with(str_replace('\\', '/', $fullPath), str_replace('\\', '/', $basePath)) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

function hydrateListingRow(array $listing): array
{
    $listing['subscription_type'] = normalizePlanType($listing['subscription_type'] ?? 'basic');
    $listing['subscription_name'] = formatPlanName($listing['subscription_type']);
    $listing['badge_label'] = getPlanBadgeLabel($listing['subscription_type']);
    $listing['listing_expiry_date'] = getListingExpiryDate($listing['created_at'], $listing['subscription_type']);
    $listing['image_list'] = json_decode($listing['images'] ?? '[]', true) ?: [];
    $listing['image'] = $listing['image_list'][0] ?? '';

    return $listing;
}

function getListingsForUser(int $userId): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'SELECT *
         FROM listings
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $listings = [];

    while ($listing = $result->fetch_assoc()) {
        $listings[] = hydrateListingRow($listing);
    }

    $stmt->close();
    closeDBConnection($conn);

    return $listings;
}

function ensureListingDocumentsTable(mysqli $conn): void
{
    static $isEnsured = false;
    if ($isEnsured) {
        return;
    }

    $conn->query(
        'CREATE TABLE IF NOT EXISTS listing_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            listing_id INT NOT NULL,
            doc_type VARCHAR(64) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            verification_status VARCHAR(64) NOT NULL DEFAULT "Available",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_listing_documents_listing_id (listing_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $isEnsured = true;
}

function saveListingDocuments(int $listingId, array $documents): void
{
    if ($listingId <= 0 || $documents === []) {
        return;
    }

    $conn = getDBConnection();
    ensureListingDocumentsTable($conn);

    $deleteStmt = $conn->prepare('DELETE FROM listing_documents WHERE listing_id = ?');
    $deleteStmt->bind_param('i', $listingId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $insertStmt = $conn->prepare(
        'INSERT INTO listing_documents (listing_id, doc_type, file_path, verification_status)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($documents as $docType => $docData) {
        $filePath = trim((string) ($docData['path'] ?? ''));
        if ($filePath === '') {
            continue;
        }

        $status = trim((string) ($docData['status'] ?? 'Available'));
        if ($status === '') {
            $status = 'Available';
        }

        $insertStmt->bind_param('isss', $listingId, $docType, $filePath, $status);
        $insertStmt->execute();
    }

    $insertStmt->close();
    closeDBConnection($conn);
}

function getListingDocuments(int $listingId): array
{
    if ($listingId <= 0) {
        return [];
    }

    $conn = getDBConnection();
    ensureListingDocumentsTable($conn);
    $stmt = $conn->prepare(
        'SELECT doc_type, file_path, verification_status
         FROM listing_documents
         WHERE listing_id = ?'
    );
    $stmt->bind_param('i', $listingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $documents = [];

    while ($row = $result->fetch_assoc()) {
        $documents[(string) $row['doc_type']] = [
            'path' => (string) ($row['file_path'] ?? ''),
            'status' => (string) ($row['verification_status'] ?? ''),
        ];
    }

    $stmt->close();
    closeDBConnection($conn);

    return $documents;
}

function deleteListingForUser(int $listingId, int $userId): bool
{
    $conn = getDBConnection();
    ensureListingDocumentsTable($conn);
    $selectStmt = $conn->prepare(
        'SELECT images, video
         FROM listings
         WHERE id = ?
           AND user_id = ?
         LIMIT 1'
    );
    $selectStmt->bind_param('ii', $listingId, $userId);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $listing = $result->fetch_assoc() ?: null;
    $selectStmt->close();

    if (!$listing) {
        closeDBConnection($conn);
        return false;
    }

    $deleteStmt = $conn->prepare(
        'DELETE FROM listings
         WHERE id = ?
           AND user_id = ?
         LIMIT 1'
    );
    $deleteStmt->bind_param('ii', $listingId, $userId);
    $deleteStmt->execute();
    $deleted = $deleteStmt->affected_rows > 0;
    $deleteStmt->close();

    if ($deleted) {
        $images = json_decode($listing['images'] ?? '[]', true);
        if (is_array($images)) {
            foreach ($images as $imagePath) {
                deleteStoredAsset((string) $imagePath);
            }
        }

        $videoPath = (string) ($listing['video'] ?? '');
        if ($videoPath !== '' && !isExternalUrl($videoPath)) {
            deleteStoredAsset($videoPath);
        }

        $docStmt = $conn->prepare(
            'SELECT file_path
             FROM listing_documents
             WHERE listing_id = ?'
        );
        $docStmt->bind_param('i', $listingId);
        $docStmt->execute();
        $docResult = $docStmt->get_result();
        while ($doc = $docResult->fetch_assoc()) {
            deleteStoredAsset((string) ($doc['file_path'] ?? ''));
        }
        $docStmt->close();

        $deleteDocStmt = $conn->prepare('DELETE FROM listing_documents WHERE listing_id = ?');
        $deleteDocStmt->bind_param('i', $listingId);
        $deleteDocStmt->execute();
        $deleteDocStmt->close();
    }

    closeDBConnection($conn);

    return $deleted;
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
        $listing = hydrateListingRow($listing);
        $documents = getListingDocuments((int) $listing['id']);
        $listing['document_7_12'] = (string) ($documents['document_7_12']['path'] ?? '');
        $listing['document_ferfar'] = (string) ($documents['document_ferfar']['path'] ?? '');
        $listing['document_title_report'] = (string) ($documents['document_title_report']['path'] ?? '');
        $listing['document_status_7_12'] = (string) ($documents['document_7_12']['status'] ?? 'Available');
        $listing['document_status_ferfar'] = (string) ($documents['document_ferfar']['status'] ?? 'Updated');
        $listing['document_status_title_report'] = (string) ($documents['document_title_report']['status'] ?? 'Verified');
    }

    return $listing;
}
?>
