
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireLogin(): void
{
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        header("Location: /DG_Wellness_Spa/login.php");
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if ($_SESSION["role"] !== $role) {
        http_response_code(403);
        exit("403 Forbidden: You are not authorized to access this page.");
    }
}

function redirectByRole(): void
{
    switch ($_SESSION["role"] ?? "") {
        case "Admin":
            header("Location: /DG_Wellness_Spa/admin/dashboard.php");
            exit;

        case "Staff":
            header("Location: /DG_Wellness_Spa/staff/dashboard.php");
            exit;

        case "Client":
            header("Location: /DG_Wellness_Spa/client/dashboard.php");
            exit;

        default:
            session_destroy();
            header("Location: /DG_Wellness_Spa/login.php");
            exit;
    }
}
