
<?php
/*
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
</html>*/



require_once __DIR__ . "/../includes/auth.php";
requireRole("Client");

$fullName = htmlspecialchars(
    $_SESSION["full_name"],
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Dashboard | DG Wellness Spa</title>

    <link rel="stylesheet"
          href="/DG_Wellness_Spa/assets/css/clientDashB.css">
</head>

<body class="client-dashboard-page">

    <header class="client-header">
        <a href="/DG_Wellness_Spa/index.php" class="client-brand">
            <span class="client-brand-mark">DG</span>

            <span class="client-brand-text">
                <strong>DG Wellness Spa</strong>
                <small>Relax. Refresh. Renew.</small>
            </span>
        </a>

        <a href="/DG_Wellness_Spa/logout.php"
           class="logout-button">
            Logout
        </a>
    </header>

    <main class="client-dashboard-main">

        <section class="welcome-section">
            <p class="dashboard-eyebrow">
                WELCOME TO YOUR WELLNESS SPACE
            </p>

            <h1>Hello, <span><?= $fullName ?></span>!</h1>

            <p>
                Your time for relaxation starts here.
                Manage your appointments, explore our services,
                and enjoy a more convenient spa experience.
            </p>
        </section>

        <section class="booking-section">

            <div class="dashboard-section-heading">
                <h2>How can we assist you?</h2>
                <p>Select an option to get started.</p>
            </div>

            <div class="dashboard-actions">

                <!-- Book Appointment -->
                <a href="book_appointment.php"
                   class="dashboard-card">

                    <span class="dashboard-card-number">01</span>

                    <span class="dashboard-card-icon">✦</span>

                    <h3>Book an Appointment</h3>

                    <p>
                        Schedule your next visit and choose
                        the spa services you want to enjoy.
                    </p>

                    <span class="dashboard-card-link">
                        Book Now <span>→</span>
                    </span>
                </a>

                <!-- My Appointments -->
                <a href="my_appointments.php"
                   class="dashboard-card">

                    <span class="dashboard-card-number">02</span>

                    <span class="dashboard-card-icon">◷</span>

                    <h3>My Appointments</h3>

                    <p>
                        View your appointment requests,
                        check booking status, and review
                        your appointment history.
                    </p>

                    <span class="dashboard-card-link">
                        View Appointments <span>→</span>
                    </span>
                </a>

                <!-- View Services -->
                <a href="../services.php"
                   class="dashboard-card">

                    <span class="dashboard-card-number">03</span>

                    <span class="dashboard-card-icon">❀</span>

                    <h3>Explore Our Services</h3>

                    <p>
                        Discover our available spa treatments,
                        service descriptions, prices, and durations.
                    </p>

                    <span class="dashboard-card-link">
                        Explore Services <span>→</span>
                    </span>
                </a>

            </div>
        </section>

        <p class="dashboard-note">
            Take a moment for yourself. You deserve it.
        </p>

    </main>

    <footer class="client-footer">
        <p>
            &copy; <?= date("Y") ?> DG Wellness Spa.
            All rights reserved.
        </p>
        <p>Relax. Refresh. Renew.</p>
    </footer>

</body>
</html>
