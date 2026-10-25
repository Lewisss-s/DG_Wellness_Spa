
<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Admin");

function returnToAppointments(string $result): void
{
    header(
        "Location: appointments.php?result=" . urlencode($result),
        true,
        303
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: appointments.php");
    exit;
}

$submittedToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($submittedToken) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $submittedToken)
) {
    returnToAppointments("error");
}

$appointmentId = filter_var(
    $_POST["appointment_id"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$action = $_POST["action"] ?? "";

if (!$appointmentId || !in_array($action, ["confirm", "reject"], true)) {
    returnToAppointments("error");
}

try {
    $pdo->beginTransaction();

    // Lock the appointment while its status and assignment are updated.
    $stmt = $pdo->prepare(
        "SELECT id, appointment_date, start_time, end_time, status
         FROM appointments
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();

    if (!$appointment || $appointment["status"] !== "Pending") {
        throw new RuntimeException(
            "This appointment no longer has a pending status."
        );
    }

    if ($action === "reject") {
        $stmt = $pdo->prepare(
            "UPDATE appointments
             SET status = 'Rejected'
             WHERE id = ? AND status = 'Pending'"
        );

        $stmt->execute([$appointmentId]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                "The appointment status could not be updated."
            );
        }

        $pdo->commit();
        returnToAppointments("rejected");
    }

    // Confirmation requires an active staff member.
    $staffId = filter_var(
        $_POST["staff_id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if (!$staffId) {
        throw new RuntimeException("Please select a staff member.");
    }

    $stmt = $pdo->prepare(
        "SELECT s.id
         FROM staff s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.id = ?
           AND s.is_active = 1
           AND u.account_status = 'Active'
           AND u.role = 'Staff'
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->execute([$staffId]);

    if (!$stmt->fetch()) {
        throw new RuntimeException(
            "The selected staff member is not active."
        );
    }

    // Reject the confirmation if this staff member is already booked
    // for an overlapping confirmed or in-progress appointment.
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.id)
         FROM appointments a
         LEFT JOIN staff_assignments sa
            ON sa.appointment_id = a.id
         WHERE a.id <> ?
           AND a.appointment_date = ?
           AND a.status IN ('Confirmed', 'In Progress')
           AND a.start_time < ?
           AND a.end_time > ?
           AND (
               sa.staff_id = ?
               OR a.preferred_staff_id = ?
           )"
    );

    $stmt->execute([
        $appointmentId,
        $appointment["appointment_date"],
        $appointment["end_time"],
        $appointment["start_time"],
        $staffId,
        $staffId
    ]);

    if ((int) $stmt->fetchColumn() > 0) {
        throw new RuntimeException(
            "This staff member has an overlapping appointment."
        );
    }

    // This appointment is pending, so replace any old assignment rows
    // before inserting the selected staff member.
    $stmt = $pdo->prepare(
        "DELETE FROM staff_assignments WHERE appointment_id = ?"
    );
    $stmt->execute([$appointmentId]);

    $stmt = $pdo->prepare(
        "INSERT INTO staff_assignments
            (appointment_id, staff_id, assigned_by)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $appointmentId,
        $staffId,
        $_SESSION["user_id"]
    ]);

    $stmt = $pdo->prepare(
        "UPDATE appointments
         SET status = 'Confirmed'
         WHERE id = ? AND status = 'Pending'"
    );

    $stmt->execute([$appointmentId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            "The appointment could not be confirmed."
        );
    }

    $pdo->commit();

    returnToAppointments("confirmed");

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Admin appointment update failed: " . $e->getMessage()
    );

    returnToAppointments("error");
}
