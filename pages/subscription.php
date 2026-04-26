<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$pageTitle = 'Subscription Plans';

$plans = getSubscriptionPlans();
$activeSubscription = getActiveSubscription();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planType = normalizePlanType($_POST['plan_type'] ?? '');
    $plan = getPlanConfig($planType);

    if (!$plan) {
        setFlash('error', 'Please select a valid subscription plan.');
        redirectTo('../pages/subscription.php');
    }

    $userId = getCurrentUserId();
    $planName = $plan['name'];
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'INSERT INTO subscriptions
        (user_id, plan_type, created_at)
        VALUES (?, ?, NOW())'
    );
    $stmt->bind_param(
        'is',
        $userId,
        $planName
    );

    if ($stmt->execute()) {
        setFlash('success', formatPlanName($planType) . ' plan activated. You can now add your listing.');
        $stmt->close();
        closeDBConnection($conn);
        redirectTo('../pages/add-listing.php');
    }

    $stmt->close();
    closeDBConnection($conn);
    setFlash('error', 'Unable to save your subscription right now.');
    redirectTo('../pages/subscription.php');
}
require_once __DIR__ . '/../includes/header.php';
?>
<main class="page-shell">
    <section class="page-panel">
        <div class="page-heading">
            <div>
                <h1>Select Your Listing Plan</h1>
                <p>Every listing starts here. Choose a plan, save it to your account, then continue to the property form.</p>
            </div>
            <?php if ($activeSubscription): ?>
                <div class="subscription-status-card">
                    <span class="badge badge-<?php echo esc($activeSubscription['plan_type']); ?>">
                        <?php echo esc($activeSubscription['plan_name']); ?> Active
                    </span>
                    <p>Expires on <?php echo esc(formatDisplayDate($activeSubscription['expiry_date'])); ?></p>
                    <a href="../pages/add-listing.php" class="btn-outline">Continue to Add Listing</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="plan-grid">
            <?php foreach ($plans as $key => $plan): ?>
                <article class="plan-card <?php echo esc(getPlanCardClass($key)); ?>">
                    <div class="plan-topline">
                        <span class="badge badge-<?php echo esc($key); ?>"><?php echo esc($plan['name']); ?></span>
                        <?php if (!empty($plan['popular'])): ?>
                            <span class="plan-kicker">Most Popular</span>
                        <?php endif; ?>
                        <?php if (!empty($plan['recommended'])): ?>
                            <span class="plan-kicker plan-kicker-strong">Best Value</span>
                        <?php endif; ?>
                    </div>

                    <h3><?php echo esc($plan['name']); ?> Plan</h3>
                    <p class="property-price">Rs. <?php echo esc(number_format((float) $plan['price'], 0)); ?></p>
                    <p class="page-subtext"><?php echo esc($plan['visibility']); ?></p>

                    <div class="plan-metric-row">
                        <div class="plan-metric">
                            <strong><?php echo $plan['listing_limit'] === null ? 'Unlimited' : esc((string) $plan['listing_limit']); ?></strong>
                            <span>Listings</span>
                        </div>
                        <div class="plan-metric">
                            <strong><?php echo $plan['image_limit'] === null ? 'Unlimited' : esc((string) $plan['image_limit']); ?></strong>
                            <span>Images</span>
                        </div>
                        <div class="plan-metric">
                            <strong><?php echo esc((string) $plan['duration_days']); ?> days</strong>
                            <span>Duration</span>
                        </div>
                    </div>

                    <div class="plan-section">
                        <h4>Features</h4>
                        <ul>
                            <li><?php echo esc($plan['listing_limit'] === null ? 'Unlimited listings' : 'Up to ' . $plan['listing_limit'] . ' active listing' . ($plan['listing_limit'] > 1 ? 's' : '')); ?></li>
                            <li><?php echo esc($plan['image_limit'] === null ? 'Unlimited images per listing' : 'Up to ' . $plan['image_limit'] . ' images per listing'); ?></li>
                            <li><?php echo esc($plan['video_allowed'] ? 'Video upload allowed' : 'No video upload'); ?></li>
                            <?php if (!empty($plan['youtube_allowed'])): ?>
                                <li>External link allowed</li>
                            <?php endif; ?>
                            <li><?php echo esc((string) $plan['duration_days']); ?> day listing duration</li>
                        </ul>
                    </div>

                    <div class="plan-section plan-section-muted">
                        <h4>Restrictions</h4>
                        <ul>
                            <?php foreach ($plan['restrictions'] as $restriction): ?>
                                <li><?php echo esc($restriction); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <form method="post">
                        <input type="hidden" name="plan_type" value="<?php echo esc($key); ?>">
                        <button type="submit" class="btn-dark plan-cta">Choose Plan</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
