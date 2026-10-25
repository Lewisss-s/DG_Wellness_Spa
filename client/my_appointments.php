
<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Client");

$pageTitle = "My Appointments";
$currentPage = "my-appointments";

$stmt = $pdo->prepare(
    "SELECT
        a.id,
        a.appointment_date,
        a.start_time,
        a.end_time,
        a.status,
        a.total_amount,
        a.customer_notes,
        a.preferred_staff_id,
        u.full_name AS preferred_staff_name,
        GROUP_CONCAT(
            aps.service_name_snapshot
            ORDER BY aps.id
            SEPARATOR ', '
        ) AS service_names
     FROM appointments a
     INNER JOIN customers c
        ON c.id = a.customer_id
     LEFT JOIN appointment_services aps
        ON aps.appointment_id = a.id
     LEFT JOIN staff s
        ON s.id = a.preferred_staff_id
     LEFT JOIN users u
        ON u.id = s.user_id
     WHERE c.user_id = ?
     GROUP BY
        a.id,
        a.appointment_date,
        a.start_time,
        a.end_time,
        a.status,
        a.total_amount,
        a.customer_notes,
        a.preferred_staff_id,
        u.full_name
     ORDER BY
        a.appointment_date DESC,
        a.start_time DESC"
);

$stmt->execute([$_SESSION["user_id"]]);
$appointments = $stmt->fetchAll();

require_once __DIR__ . "/../includes/header.php";

function appointmentStatusClass(string $status): string
{
    return strtolower(str_replace(" ", "-", $status));
}
?>

<section class="page-banner">
    <div class="container">
        <h1>My Appointments</h1>
        <p>Review your appointment requests and their current status.</p>
    </div>
</section>

<section class="my-appointments-section container">

    <?php if (isset($_GET["created"]) && $_GET["created"] === "1"): ?>
        <div class="booking-alert booking-alert-success">
            Your appointment request was submitted successfully.
            It is currently pending confirmation.
        </div>
    <?php endif; ?>

    <div class="appointments-heading">
        <div>
            <h2>Appointment History</h2>
            <p>Check the details and status of your bookings below.</p>
        </div>

        <a
            href="book_appointment.php"
            class="booking-submit-button booking-link-button"
        >
            Book an Appointment
        </a>
    </div>

    <?php if (empty($appointments)): ?>

        <div class="appointments-empty">
            <h3>No appointments yet</h3>
            <p>You have not submitted any appointment requests.</p>

            <a href="book_appointment.php">
                Book your first appointment
            </a>
        </div>

    <?php else: ?>

        <div class="appointments-list">

            <?php foreach ($appointments as $appointment): ?>

                <article class="appointment-card">

                    <div class="appointment-card-heading">
                        <div>
                            <h3>
                                Appointment #<?= (int) $appointment["id"] ?>
                            </h3>

                            <p class="appointment-date">
                                <?= date(
                                    "F j, Y",
                                    strtotime($appointment["appointment_date"])
                                ) ?>
                            </p>
                        </div>

                        <span class="appointment-status status-<?= htmlspecialchars(
                            appointmentStatusClass($appointment["status"]),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>">
                            <?= htmlspecialchars(
                                $appointment["status"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>
                    </div>

                    <div class="appointment-card-details">

                        <p>
                            <strong>Services:</strong>
                            <?= htmlspecialchars(
                                $appointment["service_names"] ?? "No services listed",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                        <p>
                            <strong>Time:</strong>
                            <?= date(
                                "g:i A",
                                strtotime($appointment["start_time"])
                            ) ?>
                            –
                            <?= date(
                                "g:i A",
                                strtotime($appointment["end_time"])
                            ) ?>
                        </p>

                        <p>
                            <strong>Preferred staff:</strong>
                            <?= htmlspecialchars(
                                $appointment["preferred_staff_name"]
                                    ?? "No preference",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                        <p>
                            <strong>Estimated total:</strong>
                            ₱<?= number_format(
                                (float) $appointment["total_amount"],
                                2
                            ) ?>
                        </p>

                        <?php if (!empty($appointment["customer_notes"])): ?>
                            <p>
                                <strong>Your notes:</strong>
                                <?= nl2br(htmlspecialchars(
                                    $appointment["customer_notes"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )) ?>
                            </p>
                        <?php endif; ?>

                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
