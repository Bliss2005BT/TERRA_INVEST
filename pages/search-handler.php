<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

if (!isUserLoggedIn()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
    ]);
    exit();
}

$location = normalizeText($_GET['location'] ?? '');
$propertyType = normalizeText($_GET['property_type'] ?? '');
$keyword = normalizeText($_GET['keyword'] ?? '');
$minPrice = normalizeText($_GET['min_price'] ?? '');
$maxPrice = normalizeText($_GET['max_price'] ?? '');
$minArea = normalizeText($_GET['min_area'] ?? '');
$maxArea = normalizeText($_GET['max_area'] ?? '');

$sql = 'SELECT id, title, price, area, location, property_type, description, amenities, images, video, subscription_type, created_at
        FROM listings
        WHERE 1=1';
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

if ($minPrice !== '' && is_numeric($minPrice)) {
    $sql .= ' AND price >= ?';
    $types .= 'd';
    $params[] = (float) $minPrice;
}

if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $sql .= ' AND price <= ?';
    $types .= 'd';
    $params[] = (float) $maxPrice;
}

if ($minArea !== '' && is_numeric($minArea)) {
    $sql .= ' AND area >= ?';
    $types .= 'd';
    $params[] = (float) $minArea;
}

if ($maxArea !== '' && is_numeric($maxArea)) {
    $sql .= ' AND area <= ?';
    $types .= 'd';
    $params[] = (float) $maxArea;
}

$sql .= " ORDER BY FIELD(subscription_type, 'Featured', 'Premium', 'Basic') ASC, created_at DESC";

$conn = getDBConnection();
$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$listings = [];

while ($row = $result->fetch_assoc()) {
    $images = json_decode($row['images'] ?? '[]', true) ?: [];
    $row['image'] = $images[0] ?? '';
    unset($row['images']);
    $listings[] = $row;
}

$stmt->close();
closeDBConnection($conn);

echo json_encode([
    'success' => true,
    'count' => count($listings),
    'listings' => $listings,
]);
?>
