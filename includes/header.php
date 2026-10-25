
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? "DG Wellness Spa";
$currentPage = $currentPage ?? "";

$isLoggedIn = isset($_SESSION["user_id"], $_SESSION["role"]);

$dashboardUrl = match ($_SESSION["role"] ?? "") {
    "Admin" => "/DG_Wellness_Spa/admin/dashboard.php",
    "Staff" => "/DG_Wellness_Spa/staff/dashboard.php",
    "Client" => "/DG_Wellness_Spa/client/dashboard.php",
    default => "/DG_Wellness_Spa/login.php"
};

function spaEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore wellness, massage, and nail care services at DG Wellness Spa in Dasmariñas City, Cavite.">
    <title><?= spaEscape($pageTitle) ?> | DG Wellness Spa</title>
    <link rel="stylesheet" href="/DG_Wellness_Spa/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="nav-container">
        <a class="brand" href="/DG_Wellness_Spa/index.php">
            <span class="brand-mark">DG</span>
            <span>
                <strong>DG Wellness Spa</strong>
                <small>Relax. Refresh. Reconnect.</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a
                href="/DG_Wellness_Spa/index.php"
                class="<?= $currentPage === "home" ? "active" : "" ?>"
            >Home</a>

            <a
                href="/DG_Wellness_Spa/services.php"
                class="<?= $currentPage === "services" ? "active" : "" ?>"
            >Services</a>

            <a href="/DG_Wellness_Spa/index.php#about">About</a>
        </nav>

        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <a class="button button-outline" href="<?= spaEscape($dashboardUrl) ?>">
                    My Dashboard
                </a>
            <?php else: ?>
                <a class="login-link" href="/DG_Wellness_Spa/login.php">
                    Log In
                </a>
                <a class="button button-primary" href="/DG_Wellness_Spa/register.php">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
