<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$pageTitle = 'View Listing';

$listingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$listing = $listingId > 0 ? getListingById($listingId) : null;

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$appBasePath = rtrim(dirname(dirname($scriptName)), '/');
if ($appBasePath === '' || $appBasePath === '.') {
    $appBasePath = '';
}

$buildAssetUrl = static function (string $path) use ($appBasePath): string {
    $normalized = trim(str_replace('\\', '/', $path));
    if ($normalized === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $normalized)) {
        return $normalized;
    }

    if (str_starts_with($normalized, '../')) {
        while (str_starts_with($normalized, '../')) {
            $normalized = substr($normalized, 3);
        }
    }

    if (str_starts_with($normalized, '/')) {
        return $normalized;
    }

    return ($appBasePath !== '' ? $appBasePath : '') . '/' . ltrim($normalized, '/');
};

$documentConfig = [
    'document_7_12' => ['label' => '7/12 Extract', 'status' => 'Available'],
    'document_ferfar' => ['label' => 'Ferfar', 'status' => 'Updated'],
    'document_title_report' => ['label' => 'Title Report', 'status' => 'Verified'],
];
$documentItems = [];
$availableDocumentCount = 0;

if ($listing) {
    $statusMap = [
        'document_7_12' => trim((string) ($listing['document_status_7_12'] ?? 'Available')),
        'document_ferfar' => trim((string) ($listing['document_status_ferfar'] ?? 'Updated')),
        'document_title_report' => trim((string) ($listing['document_status_title_report'] ?? 'Verified')),
    ];

    foreach ($documentConfig as $key => $meta) {
        $rawPath = isset($listing[$key]) ? trim((string) $listing[$key]) : '';
        $isAvailable = $rawPath !== '';
        $downloadUrl = '';
        if ($isAvailable) {
            $downloadUrl = '../pages/download-document.php?file=' . rawurlencode(basename($rawPath));
            $availableDocumentCount++;
        }

        $documentItems[] = [
            'label' => $meta['label'],
            'status' => $statusMap[$key] !== '' ? $statusMap[$key] : $meta['status'],
            'available' => $isAvailable,
            'download_url' => $downloadUrl,
            'file_name' => $isAvailable ? basename($rawPath) : '',
        ];
    }
}

$isVerifiedProperty = $availableDocumentCount === 3;
$amenitiesText = trim((string) ($listing['amenities'] ?? ''));
$listingImages = $listing['image_list'] ?? [];
$primaryImage = $listingImages[0] ?? '';
$primaryImageUrl = $primaryImage !== '' ? $buildAssetUrl($primaryImage) : '';
$fallbackImageUrl = ($appBasePath !== '' ? $appBasePath : '') . '/assets/logo_black.png';

$sellerPhone = trim((string) ($listing['seller_phone'] ?? ''));
$phoneDigits = preg_replace('/\D+/', '', $sellerPhone) ?? '';
$callUrl = $phoneDigits !== '' ? 'tel:' . $phoneDigits : '';
$whatsAppUrl = $phoneDigits !== '' ? 'https://wa.me/' . $phoneDigits : '';
$contactEmail = trim((string) ($listing['seller_email'] ?? ''));
$contactSubject = rawurlencode('Inquiry about ' . (string) ($listing['title'] ?? 'property'));
$contactUrl = $contactEmail !== '' ? 'mailto:' . $contactEmail . '?subject=' . $contactSubject : '';

require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-shell">
    <?php if (!$listing): ?>
        <section class="page-panel">
            <div class="empty-state">The requested listing was not found.</div>
        </section>
    <?php else: ?>
        <section class="page-panel property-panel">
            <div class="property-heading">
                <div class="property-heading-main">
                    <div class="property-badge-row">
                        <span class="badge badge-<?php echo esc($listing['subscription_type']); ?>">
                            <?php echo $listing['subscription_type'] === 'featured' ? '&#11088; ' : ''; ?><?php echo esc($listing['badge_label']); ?>
                        </span>
                        <?php if ($isVerifiedProperty): ?>
                            <span class="badge property-verified-badge"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Verified Property</span>
                        <?php endif; ?>
                    </div>
                    <h1><?php echo esc($listing['title']); ?></h1>
                    <p class="page-subtext"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo esc($listing['location']); ?> | <?php echo esc($listing['property_type']); ?></p>
                </div>
                <div class="property-price-box">
                    <div class="property-price">Rs. <?php echo esc(number_format((float) $listing['price'], 2)); ?></div>
                    <p class="page-subtext"><?php echo esc(number_format((float) $listing['area'], 2)); ?> sq ft</p>
                </div>
            </div>

            <section class="property-content-grid">
                <article class="detail-card property-gallery">
                    <div class="property-main-media">
                        <?php if ($primaryImageUrl !== ''): ?>
                            <button type="button" class="gallery-arrow gallery-arrow-prev" id="gallery-prev" aria-label="Previous image">
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gallery-arrow gallery-arrow-next" id="gallery-next" aria-label="Next image">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                            <img
                                id="active-property-image"
                                class="property-main-image"
                                src="<?php echo esc($primaryImageUrl); ?>"
                                alt="<?php echo esc($listing['title']); ?>"
                                onerror="this.src='<?php echo esc($fallbackImageUrl); ?>';"
                            >
                        <?php else: ?>
                            <div class="property-main-placeholder">No image uploaded</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($listingImages): ?>
                        <div class="property-thumb-row">
                            <?php foreach ($listingImages as $index => $image): ?>
                                <?php $imageUrl = $buildAssetUrl((string) $image); ?>
                                <button
                                    type="button"
                                    class="property-thumb <?php echo $index === 0 ? 'is-active' : ''; ?>"
                                    data-gallery-thumb
                                    data-full-image="<?php echo esc($imageUrl); ?>"
                                    data-index="<?php echo (int) $index; ?>"
                                    aria-label="View image <?php echo (int) ($index + 1); ?>"
                                >
                                    <img src="<?php echo esc($imageUrl); ?>" alt="<?php echo esc($listing['title']); ?> thumbnail <?php echo (int) ($index + 1); ?>" onerror="this.src='<?php echo esc($fallbackImageUrl); ?>';">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="detail-card property-side-stack">
                    <section class="property-section">
                        <div class="section-heading">
                            <h2>Quick Info</h2>
                        </div>
                        <div class="key-details-grid quick-info-grid">
                            <div class="key-detail-card">
                                <span>Type</span>
                                <strong><?php echo esc($listing['property_type']); ?></strong>
                            </div>
                            <div class="key-detail-card">
                                <span>Area</span>
                                <strong><?php echo esc(number_format((float) $listing['area'], 2)); ?> sq ft</strong>
                            </div>
                            <div class="key-detail-card">
                                <span>Listed On</span>
                                <strong><?php echo esc(date('d M Y', strtotime($listing['created_at']))); ?></strong>
                            </div>
                            <div class="key-detail-card">
                                <span>Valid Until</span>
                                <strong><?php echo esc(formatDisplayDate($listing['listing_expiry_date'])); ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="property-section">
                        <div class="section-heading">
                            <h2>Contact Buttons</h2>
                        </div>
                        <div class="contact-button-row">
                            <a class="btn-dark" href="<?php echo esc($contactUrl !== '' ? $contactUrl : '../contact.php'); ?>">Contact Seller</a>
                            <a class="btn-outline <?php echo $callUrl === '' ? 'is-disabled' : ''; ?>" href="<?php echo esc($callUrl !== '' ? $callUrl : '#'); ?>" <?php echo $callUrl === '' ? 'aria-disabled="true"' : ''; ?>>Call Now</a>
                            <a class="btn-outline <?php echo $whatsAppUrl === '' ? 'is-disabled' : ''; ?>" href="<?php echo esc($whatsAppUrl !== '' ? $whatsAppUrl : '#'); ?>" target="_blank" rel="noopener noreferrer" <?php echo $whatsAppUrl === '' ? 'aria-disabled="true"' : ''; ?>>WhatsApp</a>
                        </div>
                    </section>
                </article>
            </section>

            <?php if (!empty($listing['video'])): ?>
                <section class="detail-card property-section">
                    <div class="section-heading">
                        <h2>Property Video</h2>
                    </div>
                    <?php if (preg_match('/youtube\.com|youtu\.be/i', (string) $listing['video'])): ?>
                        <?php
                        $embedUrl = (string) $listing['video'];
                        if (preg_match('/v=([A-Za-z0-9_-]+)/', (string) $listing['video'], $matches)) {
                            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        } elseif (preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/', (string) $listing['video'], $matches)) {
                            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        }
                        ?>
                        <div class="property-video-frame">
                            <iframe src="<?php echo esc($embedUrl); ?>" allowfullscreen loading="lazy"></iframe>
                        </div>
                    <?php elseif (isExternalUrl((string) $listing['video'])): ?>
                        <div class="external-video-link-wrap">
                            <a href="<?php echo esc((string) $listing['video']); ?>" target="_blank" rel="noopener noreferrer" class="btn-outline">Open External Video</a>
                        </div>
                    <?php else: ?>
                        <div class="property-video-frame" id="property-video-frame">
                            <video controls class="property-video-player" data-auto-aspect>
                                <source src="../<?php echo esc((string) $listing['video']); ?>" type="video/mp4">
                            </video>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="detail-card property-section">
                <div class="section-heading">
                    <h2>Property Details</h2>
                </div>
                <p class="property-rich-text"><?php echo nl2br(esc((string) $listing['description'])); ?></p>
            </section>

            <section class="detail-card property-section">
                <div class="section-heading">
                    <h2><i class="fa-solid fa-bolt" aria-hidden="true"></i> Amenities</h2>
                </div>
                <?php if ($amenitiesText !== ''): ?>
                    <p class="property-rich-text"><?php echo nl2br(esc($amenitiesText)); ?></p>
                <?php else: ?>
                    <p class="empty-copy">No amenities specified.</p>
                <?php endif; ?>
            </section>

            <section class="detail-card property-section">
                <div class="section-heading">
                    <h2><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Documents &amp; Verification</h2>
                </div>
                <div class="document-verification-list">
                    <?php foreach ($documentItems as $item): ?>
                        <article class="document-verification-item">
                            <div class="document-verification-meta">
                                <p class="document-title"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i> <?php echo esc($item['label']); ?></p>
                                <span class="document-status <?php echo $item['available'] ? 'is-available' : 'is-missing'; ?>">
                                    <?php echo esc($item['available'] ? $item['status'] : 'Not Available'); ?>
                                </span>
                            </div>
                            <div class="document-verification-actions">
                                <?php if ($item['available']): ?>
                                    <a class="btn-outline btn-sm" href="<?php echo esc($item['download_url'] . '&view=1'); ?>" target="_blank" rel="noopener noreferrer">View</a>
                                    <a class="btn-outline btn-sm" href="<?php echo esc($item['download_url']); ?>">Download</a>
                                <?php else: ?>
                                    <span class="document-not-available">Not Available</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="detail-card property-section">
                <div class="section-heading">
                    <h2>Seller Info</h2>
                </div>
                <div class="seller-info-list">
                    <div><span>Name</span><strong><?php echo esc((string) $listing['seller_name']); ?></strong></div>
                    <div><span>Email</span><strong><?php echo esc((string) $listing['seller_email']); ?></strong></div>
                    <div><span>Subscription</span><strong><?php echo esc((string) $listing['subscription_name']); ?></strong></div>
                </div>
            </section>

            <section class="contact-button-row contact-button-row-bottom">
                <a href="../search.php" class="btn-outline">Back to Search</a>
                <a class="btn-dark" href="<?php echo esc($contactUrl !== '' ? $contactUrl : '../contact.php'); ?>">Contact Seller</a>
                <a class="btn-outline <?php echo $callUrl === '' ? 'is-disabled' : ''; ?>" href="<?php echo esc($callUrl !== '' ? $callUrl : '#'); ?>" <?php echo $callUrl === '' ? 'aria-disabled="true"' : ''; ?>>Call Now</a>
            </section>
        </section>

        <script>
            window.listingFallbackImage = <?php echo json_encode($fallbackImageUrl, JSON_UNESCAPED_SLASHES); ?>;
        </script>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
