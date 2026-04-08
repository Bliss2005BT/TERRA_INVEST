<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$subscription = getActiveSubscription();

echo json_encode([
    'isLoggedIn' => isUserLoggedIn(),
    'userName' => isUserLoggedIn() ? getCurrentUserName() : null,
    'userEmail' => isUserLoggedIn() ? getCurrentUserEmail() : null,
    'subscription' => $subscription ? $subscription['plan_type'] : null,
]);
?>
