
<?php
require_once __DIR__ . "/../includes/auth.php";
requireRole("Client");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | DG Wellness Spa</title>
</head>
<body>
    <h1>Client Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION["full_name"], ENT_QUOTES, "UTF-8") ?>!</p>
    <p>You are logged in as Client.</p>


<section class="booking-section">
    <h2>Client Services</h2>
    <p>Manage your spa appointments online.</p>

    <div class="dashboard-actions">
        <a
            href="book_appointment.php"
            class="booking-submit-button"
        >
            Book an Appointment
        </a>

        <a
            href="my_appointments.php"
            class="booking-submit-button"
        >
            My Appointments
        </a>

        <a
            href="../services.php"
            class="booking-submit-button"
        >
            View Services
        </a>
    </div>
</section>


    <a href="/DG_Wellness_Spa/logout.php">Logout</a>
</body>
</html>