
<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Admin");

$pageTitle = "Manage Appointments";
$currentPage = "appointments";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

$filter = $_GET["status"] ?? "All";

$allowedStatuses = [
    "All",
    "Pending",
    "Confirmed",
    "Rejected",
    "Cancelled",
    "In Progress",
    "Completed"
];

if (!in_array($filter, $allowedStatuses, true)) {
    $filter = "All";
}

$sql = "
    SELECT
        a.id,
        a.appointment_date,
        a.start_time,
        a.end_time,
        a.status,
        a.total_amount,
        a.customer_notes,
        c.id AS customer_id,
        u.full_name AS customer_name,
        u.email AS customer_email,
        GROUP_CONCAT(
            DISTINCT aps.service_name_snapshot
            ORDER BY aps.service_name_snapshot
            SEPARATOR ', '
        ) AS service_names,
        GROUP_CONCAT(
            DISTINCT assigned_user.full_name
            SEPARATOR ', '
        ) AS assigned_staff_names
    FROM appointments a
    INNER JOIN customers c
        ON c.id = a.customer_id
    INNER JOIN users u
        ON u.id = c.user_id
    LEFT JOIN appointment_services aps
        ON aps.appointment_id = a.id
    LEFT JOIN staff_assignments sa
        ON sa.appointment_id = a.id
    LEFT JOIN staff assigned_staff
        ON assigned_staff.id = sa.staff_id
    LEFT JOIN users assigned_user
        ON assigned_user.id = assigned_staff.user_id
";

$params = [];

if ($filter !== "All") {
    $sql .= " WHERE a.status = ?";
    $params[] = $filter;
}

$sql .= "
    GROUP BY
        a.id,
        a.appointment_date,
        a.start_time,
        a.end_time,
        a.status,
        a.total_amount,
        a.customer_notes,
        c.id,
        u.full_name,
        u.email
    ORDER BY
        CASE WHEN a.status = 'Pending' THEN 0 ELSE 1 END,
        a.appointment_date DESC,
        a.start_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT s.id, s.position, u.full_name
     FROM staff s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.is_active = 1
       AND u.account_status = 'Active'
       AND u.role = 'Staff'
     ORDER BY u.full_name"
);
$staffMembers = $stmt->fetchAll();

function adminAppointmentEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function adminAppointmentStatusClass(string $status): string
{
    return strtolower(str_replace(" ", "-", $status));
}

require_once __DIR__ . "/../includes/header.php";
?>

<section class="page-banner">
    <div class="container">
        <h1>Manage Appointments</h1>
        <p>Review requests, confirm bookings, and assign staff.</p>
    </div>
</section>

<section class="admin-appointments-section container">

    <?php if (isset($_GET["result"])): ?>
        <?php
        $messages = [
            "confirmed" => "Appointment confirmed and staff assigned.",
            "rejected" => "Appointment request rejected.",
            "error" => "The appointment could not be updated."
        ];
        $result = $_GET["result"];
        ?>

        <?php if (isset($messages[$result])): ?>
            <div class="booking-alert <?= $result === "error"
                ? "booking-alert-error"
                : "booking-alert-success" ?>">
                <?= adminAppointmentEscape($messages[$result]) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="admin-appointments-toolbar">
        <div>
            <h2>Appointment Requests</h2>
            <p><?= count($appointments) ?> appointment(s) shown</p>
        </div>

        <form method="GET" action="appointments.php">
            <label for="status">Filter by status</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <?php foreach ($allowedStatuses as $status): ?>
                    <option
                        value="<?= adminAppointmentEscape($status) ?>"
                        <?= $filter === $status ? "selected" : "" ?>
                    >
                        <?= adminAppointmentEscape($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($appointments)): ?>

        <div class="appointments-empty">
            <h3>No appointments found</h3>
            <p>There are no appointments matching this filter.</p>
        </div>

    <?php else: ?>

        <div class="admin-appointments-list">

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

                        <span class="appointment-status status-<?= adminAppointmentEscape(
                            adminAppointmentStatusClass($appointment["status"])
                        ) ?>">
                            <?= adminAppointmentEscape($appointment["status"]) ?>
                        </span>
                    </div>

                    <div class="appointment-card-details">
                        <p>
                            <strong>Client:</strong>
                            <?= adminAppointmentEscape($appointment["customer_name"]) ?>
                        </p>

                        <p>
                            <strong>Email:</strong>
                            <?= adminAppointmentEscape($appointment["customer_email"]) ?>
                        </p>

                        <p>
                            <strong>Services:</strong>
                            <?= adminAppointmentEscape(
                                $appointment["service_names"] ?? "No services listed"
                            ) ?>
                        </p>

                        <p>
                            <strong>Schedule:</strong>
                            <?= date("g:i A", strtotime($appointment["start_time"])) ?>
                            –
                            <?= date("g:i A", strtotime($appointment["end_time"])) ?>
                        </p>

                        <p>
                            <strong>Estimated total:</strong>
                            ₱<?= number_format(
                                (float) $appointment["total_amount"],
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Assigned staff:</strong>
                            <?= adminAppointmentEscape(
                                $appointment["assigned_staff_names"] ?? "Not assigned"
                            ) ?>
                        </p>

                        <?php if (!empty($appointment["customer_notes"])): ?>
                            <p>
                                <strong>Client notes:</strong>
                                <?= nl2br(adminAppointmentEscape(
                                    $appointment["customer_notes"]
                                )) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($appointment["status"] === "Pending"): ?>

                        <form
                            method="POST"
                            action="update_appointment.php"
                            class="admin-appointment-actions"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= adminAppointmentEscape($csrfToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="appointment_id"
                                value="<?= (int) $appointment["id"] ?>"
                            >

                            <div class="booking-field">
                                <label for="staff_<?= (int) $appointment["id"] ?>">
                                    Assign staff to confirm
                                </label>

                                <select
                                    id="staff_<?= (int) $appointment["id"] ?>"
                                    name="staff_id"
                                    required
                                >
                                    <option value="">Select staff member</option>

                                    <?php foreach ($staffMembers as $member): ?>
                                        <option value="<?= (int) $member["id"] ?>">
                                            <?= adminAppointmentEscape($member["full_name"]) ?>
                                            <?php if (!empty($member["position"])): ?>
                                                — <?= adminAppointmentEscape($member["position"]) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-appointment-buttons">
                                <button
                                    type="submit"
                                    name="action"
                                    value="confirm"
                                    class="booking-submit-button"
                                    <?= empty($staffMembers) ? "disabled" : "" ?>
                                >
                                    Confirm Appointment
                                </button>

                                <button
                                    type="submit"
                                    name="action"
                                    value="reject"
                                    class="admin-reject-button"
                                    formnovalidate
                                    onclick="return confirm('Reject this appointment request?');"
                                >
                                    Reject Request
                                </button>
                            </div>
                        </form>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
