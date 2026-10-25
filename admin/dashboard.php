
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("Admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | DG Wellness Spa</title>
    <link rel="stylesheet" href="/DG_Wellness_Spa/assets/css/style.css">
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION["full_name"], ENT_QUOTES, "UTF-8") ?>!</p>
    <p>You are logged in as Admin.</p>

    
    <a href="appointments.php" class="booking-submit-button">
        Manage Appointments
    </a>


    <a href="/DG_Wellness_Spa/logout.php">Logout</a>
</body>
</html>
