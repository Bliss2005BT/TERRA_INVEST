<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function sendJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

function normalizeSearchNumber(string $value, string $fieldLabel): ?float
{
    if ($value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        sendJson([
            'success' => false,
            'message' => $fieldLabel . ' must be a valid number.',
        ], 422);
    }

    return (float) $value;
}

try {
    if (!isUserLoggedIn()) {
        sendJson([
            'success' => false,
            'message' => 'Please log in to continue.',
        ], 401);
    }

    $location = normalizeText($_GET['location'] ?? '');
    $propertyType = normalizeText($_GET['property_type'] ?? '');
    $keyword = normalizeText($_GET['keyword'] ?? '');
    $minPrice = normalizeSearchNumber(normalizeText($_GET['min_price'] ?? ''), 'Minimum price');
    $maxPrice = normalizeSearchNumber(normalizeText($_GET['max_price'] ?? ''), 'Maximum price');
    $minArea = normalizeSearchNumber(normalizeText($_GET['min_area'] ?? ''), 'Minimum area');
    $maxArea = normalizeSearchNumber(normalizeText($_GET['max_area'] ?? ''), 'Maximum area');

    if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
        sendJson([
            'success' => false,
            'message' => 'Minimum price cannot be greater than maximum price.',
        ], 422);
    }

    if ($minArea !== null && $maxArea !== null && $minArea > $maxArea) {
        sendJson([
            'success' => false,
            'message' => 'Minimum area cannot be greater than maximum area.',
        ], 422);
    }

    $allowedPropertyTypes = ['All', 'Residential', 'Commercial', 'Agricultural', 'Industrial', 'Plots'];
    if ($propertyType !== '' && !in_array($propertyType, $allowedPropertyTypes, true)) {
        sendJson([
            'success' => false,
            'message' => 'Invalid property type selected.',
        ], 422);
    }

    $sql = 'SELECT id, title, price, area, location, property_type, description, amenities, images, video, subscription_type, created_at
            FROM listings
            WHERE ' . getListingActiveSql('created_at', 'subscription_type');
    $types = '';
    $params = [];

    if ($location !== '') {
        $sql .= ' AND location LIKE ?';
        $types .= 's';
        $params[] = '%' . $location . '%';
    }

    if ($propertyType !== '' && $propertyType !== 'All') {
        $sql .= ' AND property_type = ?';
        $types .= 's';
        $params[] = $propertyType;
    }

    if ($keyword !== '') {
        $sql .= ' AND (title LIKE ? OR location LIKE ? OR description LIKE ?)';
        $types .= 'sss';
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
    }

    if ($minPrice !== null) {
        $sql .= ' AND price >= ?';
        $types .= 'd';
        $params[] = $minPrice;
    }

    if ($maxPrice !== null) {
        $sql .= ' AND price <= ?';
        $types .= 'd';
        $params[] = $maxPrice;
    }

    if ($minArea !== null) {
        $sql .= ' AND area >= ?';
        $types .= 'd';
        $params[] = $minArea;
    }

    if ($maxArea !== null) {
        $sql .= ' AND area <= ?';
        $types .= 'd';
        $params[] = $maxArea;
    }

    $sql .= " ORDER BY FIELD(LOWER(subscription_type), 'featured', 'premium', 'basic') ASC, created_at DESC LIMIT 24";

    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $listings = [];

    while ($row = $result->fetch_assoc()) {
        $images = json_decode($row['images'] ?? '[]', true);
        if (!is_array($images)) {
            $images = [];
        }

        $row['subscription_type'] = normalizePlanType($row['subscription_type'] ?? 'basic');
        $row['subscription_name'] = formatPlanName($row['subscription_type']);
        $row['badge_label'] = getPlanBadgeLabel($row['subscription_type']);
        $row['listing_expiry_date'] = getListingExpiryDate($row['created_at'], $row['subscription_type']);
        $row['image'] = $images[0] ?? '';
        unset($row['images']);
        $listings[] = $row;
    }

    $stmt->close();
    closeDBConnection($conn);

    sendJson([
        'success' => true,
        'count' => count($listings),
        'listings' => $listings,
        'message' => count($listings) === 0 ? 'No results found.' : null,
    ]);
} catch (Throwable $exception) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if (isset($conn) && $conn instanceof mysqli) {
        closeDBConnection($conn);
    }

    sendJson([
        'success' => false,
        'message' => 'Unable to load listings right now.',
        'error' => $exception->getMessage(),
    ], 500);
}
