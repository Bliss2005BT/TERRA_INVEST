<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$pageTitle = 'View Listing';

$listingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$listing = $listingId > 0 ? getListingById($listingId) : null;
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-shell">
    <?php if (!$listing): ?>
        <section class="page-panel">
            <div class="empty-state">The requested listing was not found.</div>
        </section>
    <?php else: ?>
        <section class="page-panel">
            <div class="page-heading">
                <div>
                    <span class="badge badge-<?php echo esc($listing['subscription_type']); ?>">
                        <?php echo $listing['subscription_type'] === 'featured' ? '&#11088; ' : ''; ?><?php echo esc($listing['badge_label']); ?>
                    </span>
                    <h1><?php echo esc($listing['title']); ?></h1>
                    <p class="page-subtext"><?php echo esc($listing['location']); ?> | <?php echo esc($listing['property_type']); ?></p>
                </div>
                <div>
                    <div class="property-price">Rs. <?php echo esc(number_format((float) $listing['price'], 2)); ?></div>
                    <p class="page-subtext"><?php echo esc(number_format((float) $listing['area'], 2)); ?> sq ft</p>
                </div>
            </div>

            <div class="media-grid">
                <?php foreach ($listing['image_list'] as $image): ?>
                    <article class="media-card">
                        <img src="../<?php echo esc($image); ?>" alt="<?php echo esc($listing['title']); ?>">
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($listing['video'])): ?>
                <section style="margin-top: 22px;">
                    <h3 style="margin-bottom: 12px;">Property Video</h3>
                    <?php if (preg_match('/youtube\.com|youtu\.be/i', $listing['video'])): ?>
                        <?php
                        $embedUrl = $listing['video'];
                        if (preg_match('/v=([A-Za-z0-9_-]+)/', $listing['video'], $matches)) {
                            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        } elseif (preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/', $listing['video'], $matches)) {
                            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                        }
                        ?>
                        <div class="video-frame">
                            <iframe src="<?php echo esc($embedUrl); ?>" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <div class="media-card">
                            <video controls>
                                <source src="../<?php echo esc($listing['video']); ?>" type="video/mp4">
                            </video>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="detail-grid" style="margin-top: 26px;">
                <article class="detail-card">
                    <h3>Property Details</h3>
                    <div class="detail-meta">
                        <span><?php echo esc($listing['property_type']); ?></span>
                        <span><?php echo esc($listing['location']); ?></span>
                        <span>Listed on <?php echo esc(date('d M Y', strtotime($listing['created_at']))); ?></span>
                        <span>Expires on <?php echo esc(formatDisplayDate($listing['listing_expiry_date'])); ?></span>
                    </div>
                    <p><?php echo nl2br(esc($listing['description'])); ?></p>
                </article>
                <article class="detail-card">
                    <h3>Amenities & Seller</h3>
                    <ul class="detail-list">
                        <li><strong>Amenities:</strong> <?php echo esc($listing['amenities'] ?: 'Not specified'); ?></li>
                        <li><strong>Seller:</strong> <?php echo esc($listing['seller_name']); ?></li>
                        <li><strong>Email:</strong> <?php echo esc($listing['seller_email']); ?></li>
                        <li><strong>Subscription:</strong> <?php echo esc($listing['subscription_name']); ?></li>
                        <li><strong>Listing valid until:</strong> <?php echo esc(formatDisplayDate($listing['listing_expiry_date'])); ?></li>
                    </ul>
                    <a href="../search.php" class="btn-outline">Back to Search</a>
                </article>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
