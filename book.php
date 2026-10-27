
<?php
require_once __DIR__ . "/includes/auth.php";

// If the visitor is already logged in, send them to the correct dashboard.
if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    redirectByRole();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment | DG Wellness Spa</title>
    <link rel="stylesheet" href="/DG_Wellness_Spa/assets/css/booking.css">
</head>

<body class="booking-entry-page">

    <header class="home-header">
        <a class="spa-brand" href="/DG_Wellness_Spa/index.php">
            <span class="brand-mark">DG</span>
            <span>
                <strong>DG Wellness Spa</strong>
                <small>Relax. Refresh. Renew.</small>
            </span>
        </a>

        <a class="outline-button" href="/DG_Wellness_Spa/index.php">
            Back to Home
        </a>
    </header>

    <main class="booking-entry-main">
        <section class="booking-entry-card">
            <span class="booking-entry-icon">✦</span>

            <p class="section-eyebrow">YOUR WELLNESS JOURNEY</p>

            <h1>Let's make time for you.</h1>

            <p class="booking-entry-description">
                To request an appointment with DG Wellness Spa,
                log in to your account or register as a new customer.
            </p>

            <a class="book-button booking-entry-button"
               href="/DG_Wellness_Spa/login.php">
                Log In to Your Account
            </a>

            <a class="outline-button booking-entry-button"
               href="/DG_Wellness_Spa/register.php">
                Create a New Account
            </a>

            <p class="booking-entry-note">
                Already have an account? Log in to continue.
            </p>
        </section>
    </main>

    <footer class="home-footer">
        <p>&copy; <?= date("Y") ?> DG Wellness Spa. All rights reserved.</p>
    </footer>

</body>
</html>
