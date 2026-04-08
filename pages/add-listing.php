<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$subscription = requireSubscription();
$pageTitle = 'Add Listing';

$formData = [
    'title' => '',
    'price' => '',
    'area' => '',
    'city' => '',
    'state' => '',
    'property_type' => '',
    'description' => '',
    'amenities' => '',
    'youtube_link' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $value) {
        $formData[$key] = normalizeText($_POST[$key] ?? '');
    }

    if ($formData['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] <= 0) {
        $errors[] = 'Enter a valid price.';
    }

    if ($formData['area'] === '' || !is_numeric($formData['area']) || (float) $formData['area'] <= 0) {
        $errors[] = 'Enter a valid area.';
    }

    if ($formData['city'] === '' || $formData['state'] === '') {
        $errors[] = 'City and state are required.';
    }

    $allowedTypes = ['Residential', 'Commercial', 'Agricultural', 'Industrial', 'Plots'];
    if (!in_array($formData['property_type'], $allowedTypes, true)) {
        $errors[] = 'Please select a valid property type.';
    }

    if ($formData['description'] === '') {
        $errors[] = 'Description is required.';
    }

    if (!validateYouTubeUrl($formData['youtube_link'])) {
        $errors[] = 'Enter a valid YouTube link.';
    }

    if (empty($_FILES['images']['name'][0])) {
        $errors[] = 'At least one image is required.';
    }

    $imagePaths = [];
    if (!$errors) {
        $imageCount = count($_FILES['images']['name']);
        for ($index = 0; $index < $imageCount; $index++) {
            $file = [
                'name' => $_FILES['images']['name'][$index],
                'type' => $_FILES['images']['type'][$index],
                'tmp_name' => $_FILES['images']['tmp_name'][$index],
                'error' => $_FILES['images']['error'][$index],
                'size' => $_FILES['images']['size'][$index],
            ];

            $upload = storeUploadedFile(
                $file,
                IMAGE_UPLOAD_DIR,
                ['jpg', 'jpeg', 'png'],
                MAX_IMAGE_SIZE,
                ROOT_IMAGE_UPLOAD_WEB
            );

            if (!$upload['success']) {
                $errors[] = 'Image upload error: ' . $upload['message'];
                break;
            }

            $imagePaths[] = $upload['path'];
        }
    }

    $videoPath = '';
    if (!$errors && !empty($_FILES['video']['name'])) {
        $videoUpload = storeUploadedFile(
            $_FILES['video'],
            VIDEO_UPLOAD_DIR,
            ['mp4'],
            MAX_VIDEO_SIZE,
            ROOT_VIDEO_UPLOAD_WEB
        );

        if (!$videoUpload['success']) {
            $errors[] = 'Video upload error: ' . $videoUpload['message'];
        } else {
            $videoPath = $videoUpload['path'];
        }
    }

    if (!$errors && $videoPath !== '' && $formData['youtube_link'] !== '') {
        $errors[] = 'Please upload a video or provide a YouTube link, not both.';
    }

    if (!$errors) {
        $location = $formData['city'] . ', ' . $formData['state'];
        $userId = getCurrentUserId();
        $imagesJson = json_encode($imagePaths);
        $videoValue = $videoPath !== '' ? $videoPath : $formData['youtube_link'];

        $conn = getDBConnection();
        $stmt = $conn->prepare(
            'INSERT INTO listings
            (user_id, title, price, area, location, property_type, description, amenities, images, video, subscription_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $price = (float) $formData['price'];
        $area = (float) $formData['area'];
        $stmt->bind_param(
            'isddsssssss',
            $userId,
            $formData['title'],
            $price,
            $area,
            $location,
            $formData['property_type'],
            $formData['description'],
            $formData['amenities'],
            $imagesJson,
            $videoValue,
            $subscription['plan_type']
        );

        if ($stmt->execute()) {
            $listingId = $stmt->insert_id;
            $stmt->close();
            closeDBConnection($conn);
            setFlash('success', 'Listing created successfully.');
            redirectTo('../pages/view-listing.php?id=' . $listingId);
        }

        $stmt->close();
        closeDBConnection($conn);
        $errors[] = 'Unable to save your listing right now.';
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-shell">
    <section class="page-panel">
        <div class="page-heading">
            <div>
                <h1>Add a Land Listing</h1>
                <p>Plan selected: <strong><?php echo esc($subscription['plan_type']); ?></strong>. Seller details will be attached automatically from your logged-in account.</p>
            </div>
            <span class="badge"><?php echo esc($subscription['plan_type']); ?></span>
        </div>

        <?php if ($errors): ?>
            <div class="form-alert error"><?php echo esc(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="listing-form">
            <div class="form-grid">
                <div class="field-group">
                    <label for="title">Land Title</label>
                    <input type="text" id="title" name="title" value="<?php echo esc($formData['title']); ?>" required>
                </div>
                <div class="field-group">
                    <label for="property_type">Property Type</label>
                    <select id="property_type" name="property_type" required>
                        <option value="">Select property type</option>
                        <?php foreach (['Residential', 'Commercial', 'Agricultural', 'Industrial', 'Plots'] as $type): ?>
                            <option value="<?php echo esc($type); ?>" <?php echo $formData['property_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo esc($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label for="price">Price (INR)</label>
                    <input type="number" step="0.01" min="1" id="price" name="price" value="<?php echo esc($formData['price']); ?>" required>
                </div>
                <div class="field-group">
                    <label for="area">Area (sq ft)</label>
                    <input type="number" step="0.01" min="1" id="area" name="area" value="<?php echo esc($formData['area']); ?>" required>
                </div>
                <div class="field-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo esc($formData['city']); ?>" required>
                </div>
                <div class="field-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo esc($formData['state']); ?>" required>
                </div>
                <div class="field-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo esc($formData['description']); ?></textarea>
                </div>
                <div class="field-group full-width">
                    <label for="amenities">Amenities</label>
                    <textarea id="amenities" name="amenities" placeholder="Road access, water connection, fencing, nearby highway..."><?php echo esc($formData['amenities']); ?></textarea>
                </div>
                <div class="field-group full-width">
                    <label for="images">Property Images</label>
                    <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png" multiple required>
                    <span class="helper-text">Upload JPG or PNG images only. Maximum 5 MB per image.</span>
                    <div class="preview-grid" id="image-preview-grid">
                        <div class="preview-item">Image preview appears here</div>
                    </div>
                </div>
                <div class="field-group">
                    <label for="video">Video Upload (MP4 only)</label>
                    <input type="file" id="video" name="video" accept=".mp4">
                    <span class="helper-text">Optional. Maximum 20 MB.</span>
                </div>
                <div class="field-group">
                    <label for="youtube_link">Or YouTube Link</label>
                    <input type="url" id="youtube_link" name="youtube_link" value="<?php echo esc($formData['youtube_link']); ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>
            </div>

            <div>
                <button type="submit" class="btn-dark">Publish Listing</button>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
