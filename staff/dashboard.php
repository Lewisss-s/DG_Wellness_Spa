
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Staff");

$fullName = $_SESSION["full_name"] ?? "Staff Member";
$today = date("Y-m-d");

$staffId = null;
$todayCount = 0;
$upcomingCount = 0;
$completedCount = 0;
$upcomingAppointments = [];
$dashboardError = false;

function staffDashboardEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function staffDashboardTime($time): string
{
    if (!$time) {
        return "Not specified";
    }

    return date("g:i A", strtotime($time));
}

try {
    // Find the staff profile linked to the logged-in user.
    $staffQuery = $pdo->prepare(
        "SELECT id
         FROM staff
         WHERE user_id = ?
         LIMIT 1"
    );

    $staffQuery->execute([$_SESSION["user_id"]]);
    $staff = $staffQuery->fetch();

    if ($staff) {
        $staffId = (int) $staff["id"];

        // Count appointments assigned to this staff member today.
        $todayQuery = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.id)
             FROM appointments a
             INNER JOIN staff_assignments sa
                ON sa.appointment_id = a.id
             WHERE sa.staff_id = ?
               AND a.appointment_date = ?
               AND a.status IN ('Confirmed', 'In Progress')"
        );

        $todayQuery->execute([$staffId, $today]);
        $todayCount = (int) $todayQuery->fetchColumn();

        // Count future assigned appointments after today.
        $upcomingQuery = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.id)
             FROM appointments a
             INNER JOIN staff_assignments sa
                ON sa.appointment_id = a.id
             WHERE sa.staff_id = ?
               AND a.appointment_date > ?
               AND a.status = 'Confirmed'"
        );

        $upcomingQuery->execute([$staffId, $today]);
        $upcomingCount = (int) $upcomingQuery->fetchColumn();

        // Count completed appointments assigned to this staff member.
        $completedQuery = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.id)
             FROM appointments a
             INNER JOIN staff_assignments sa
                ON sa.appointment_id = a.id
             WHERE sa.staff_id = ?
               AND a.status = 'Completed'"
        );

        $completedQuery->execute([$staffId]);
        $completedCount = (int) $completedQuery->fetchColumn();

        // Retrieve today's and future confirmed appointments.
        $appointmentsQuery = $pdo->prepare(
            "SELECT
                a.id,
                a.appointment_date,
                a.start_time,
                a.end_time,
                a.status,
                u.full_name AS customer_name,
                GROUP_CONCAT(
                    DISTINCT aps.service_name_snapshot
                    ORDER BY aps.service_name_snapshot
                    SEPARATOR ', '
                ) AS services
             FROM appointments a
             INNER JOIN staff_assignments sa
                ON sa.appointment_id = a.id
             INNER JOIN customers c
                ON c.id = a.customer_id
             INNER JOIN users u
                ON u.id = c.user_id
             LEFT JOIN appointment_services aps
                ON aps.appointment_id = a.id
             WHERE sa.staff_id = ?
               AND a.appointment_date >= ?
               AND a.status IN ('Confirmed', 'In Progress')
             GROUP BY
                a.id,
                a.appointment_date,
                a.start_time,
                a.end_time,
                a.status,
                u.full_name
             ORDER BY
                a.appointment_date ASC,
                a.start_time ASC
             LIMIT 8"
        );

        $appointmentsQuery->execute([$staffId, $today]);
        $upcomingAppointments = $appointmentsQuery->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Staff dashboard error: " . $e->getMessage());
    $dashboardError = true;
}

$currentPage = "dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard | DG Wellness Spa</title>

    <link
        rel="stylesheet"
        href="/DG_Wellness_Spa/assets/css/style.css"
    >
</head>
<body class="staff-dashboard-page">

<div class="staff-layout">

    <aside class="staff-sidebar">
        <a href="dashboard.php" class="staff-brand">
            <span class="staff-brand-icon">DG</span>
            <span>
                <strong>DG Wellness Spa</strong>
                <small>Staff Portal</small>
            </span>
        </a>

        <nav class="staff-navigation" aria-label="Staff navigation">
            <a href="dashboard.php" class="staff-nav-link active">
                <span aria-hidden="true">▦</span>
                Dashboard
            </a>

            <a
                href="#appointments"
                class="staff-nav-link"
            >
                <span aria-hidden="true">▣</span>
                My Appointments
            </a>

            <a
                href="#schedule"
                class="staff-nav-link"
            >
                <span aria-hidden="true">◷</span>
                My Schedule
            </a>

            <a
                href="#profile"
                class="staff-nav-link"
            >
                <span aria-hidden="true">♙</span>
                My Profile
            </a>
        </nav>

        <div class="staff-sidebar-bottom">
            <p>Logged in as</p>
            <strong><?= staffDashboardEscape($fullName) ?></strong>
            <span>Staff</span>

            <a
                href="/DG_Wellness_Spa/logout.php"
                class="staff-logout-link"
            >
                Log Out
            </a>
        </div>
    </aside>

    <main class="staff-main">

        <header class="staff-topbar">
            <div>
                <p class="staff-eyebrow">DG WELLNESS SPA</p>
                <h1>Staff Dashboard</h1>
            </div>

            <div class="staff-account">
                <div class="staff-avatar">
                    <?= staffDashboardEscape(
                        strtoupper(substr($fullName, 0, 1))
                    ) ?>
                </div>
                <div>
                    <strong><?= staffDashboardEscape($fullName) ?></strong>
                    <small>Staff Account</small>
                </div>
            </div>
        </header>

        <section class="staff-welcome">
            <div>
                <p class="staff-eyebrow">YOUR WORKSPACE</p>
                <h2>Welcome, <?= staffDashboardEscape($fullName) ?>!</h2>
                <p>
                    View your assigned appointments and keep track
                    of your daily schedule.
                </p>
            </div>

            <div class="staff-date">
                <span>Today</span>
                <strong><?= date("F j, Y") ?></strong>
            </div>
        </section>

        <?php if ($dashboardError): ?>
            <div class="staff-alert staff-alert-error">
                We could not load your dashboard information.
                Please check the database configuration and PHP error log.
            </div>
        <?php elseif ($staffId === null): ?>
            <div class="staff-alert">
                Your login is working, but no staff profile is linked
                to your account yet. Please ask the administrator to
                check your staff account.
            </div>
        <?php endif; ?>

        <section class="staff-summary-grid" aria-label="Appointment summary">

            <article class="staff-summary-card">
                <div class="staff-summary-icon">▣</div>
                <p>Today's Appointments</p>
                <strong><?= $todayCount ?></strong>
                <small>Confirmed or in progress</small>
            </article>

            <article class="staff-summary-card">
                <div class="staff-summary-icon">◷</div>
                <p>Upcoming Appointments</p>
                <strong><?= $upcomingCount ?></strong>
                <small>Scheduled after today</small>
            </article>

            <article class="staff-summary-card">
                <div class="staff-summary-icon">✓</div>
                <p>Completed Appointments</p>
                <strong><?= $completedCount ?></strong>
                <small>All assigned appointments</small>
            </article>

        </section>

        <section
            class="staff-content-card"
            id="appointments"
        >
            <div class="staff-section-heading">
                <div>
                    <h2>My Appointments</h2>
                    <p>Your confirmed appointments, starting today.</p>
                </div>
                <span class="staff-section-label">
                    <?= count($upcomingAppointments) ?> shown
                </span>
            </div>

            <?php if (empty($upcomingAppointments)): ?>
                <div class="staff-empty-state">
                    <div class="staff-empty-icon">▣</div>
                    <h3>No upcoming appointments</h3>
                    <p>
                        Your assigned appointments will appear here
                        once they are confirmed.
                    </p>
                </div>
            <?php else: ?>
                <div class="staff-table-wrapper">
                    <table class="staff-appointments-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service(s)</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= staffDashboardEscape(
                                                $appointment["customer_name"]
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= staffDashboardEscape(
                                            $appointment["services"] ?: "No service listed"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            "M j, Y",
                                            strtotime($appointment["appointment_date"])
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= staffDashboardEscape(
                                            staffDashboardTime($appointment["start_time"])
                                        ) ?>
                                        –
                                        <?= staffDashboardEscape(
                                            staffDashboardTime($appointment["end_time"])
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="staff-status-badge">
                                            <?= staffDashboardEscape(
                                                $appointment["status"]
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="staff-content-card staff-placeholder-card" id="schedule">
            <div>
                <h2>My Schedule</h2>
                <p>
                    A dedicated calendar or daily schedule view can be
                    integrated in a later module.
                </p>
            </div>
            <span class="staff-coming-soon">Planned</span>
        </section>

        <section class="staff-content-card staff-placeholder-card" id="profile">
            <div>
                <h2>My Profile</h2>
                <p>
                    Staff profile details and account settings can be
                    added in a later module.
                </p>
            </div>
            <span class="staff-coming-soon">Planned</span>
        </section>

        <footer class="staff-footer">
            DG Wellness Spa Management System
        </footer>

    </main>
</div>

</body>
</html>
