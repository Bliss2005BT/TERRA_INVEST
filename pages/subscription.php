<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$pageTitle = 'Subscription Plans';

$plans = [
    'Basic' => [
        'price' => 'Rs. 999',
        'description' => 'Ideal for first-time sellers who want a clean listing flow.',
        'features' => [
            '1 active listing',
            'Standard search visibility',
            'Image gallery support',
        ],
    ],
    'Premium' => [
        'price' => 'Rs. 2,499',
        'description' => 'Best for sellers who want stronger exposure and rich media.',
        'features' => [
            '5 active listings',
            'Priority placement in results',
            'Video uploads and full gallery',
        ],
    ],
    'Featured' => [
        'price' => 'Rs. 4,999',
        'description' => 'Maximum visibility for high-priority land opportunities.',
        'features' => [
            'Unlimited active listings',
            'Featured badge on listing',
            'Top search placement support',
        ],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planType = normalizeText($_POST['plan_type'] ?? '');

    if (!array_key_exists($planType, $plans)) {
        setFlash('error', 'Please select a valid subscription plan.');
        redirectTo('../pages/subscription.php');
    }

    $userId = getCurrentUserId();
    $conn = getDBConnection();
    $stmt = $conn->prepare('INSERT INTO subscriptions (user_id, plan_type) VALUES (?, ?)');
    $stmt->bind_param('is', $userId, $planType);

    if ($stmt->execute()) {
        setFlash('success', 'Subscription saved. You can now add your land listing.');
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
                <p>Choose a plan before adding a property. This keeps the flow aligned with your existing Terra Invest system.</p>
            </div>
            <?php if ($activeSubscription): ?>
                <span class="badge"><?php echo esc($activeSubscription['plan_type']); ?> Active</span>
            <?php endif; ?>
        </div>

        <div class="plan-grid">
            <?php foreach ($plans as $name => $plan): ?>
                <article class="plan-card">
                    <span class="badge"><?php echo esc($name); ?></span>
                    <h3><?php echo esc($name); ?></h3>
                    <p class="property-price"><?php echo esc($plan['price']); ?></p>
                    <p class="page-subtext"><?php echo esc($plan['description']); ?></p>
                    <ul>
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li><?php echo esc($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="post">
                        <input type="hidden" name="plan_type" value="<?php echo esc($name); ?>">
                        <button type="submit" class="btn-dark">Choose <?php echo esc($name); ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
