
<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Client");

date_default_timezone_set("Asia/Manila");

$pageTitle = "Book an Appointment";
$currentPage = "booking";

$error = "";
$services = [];
$staffMembers = [];
$selectedServiceIds = [];
$appointmentDate = "";
$startTime = "";
$preferredStaffId = "";
$customerNotes = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Find the customer profile linked to the logged-in account.
$stmt = $pdo->prepare(
    "SELECT id FROM customers WHERE user_id = ? LIMIT 1"
);
$stmt->execute([$_SESSION["user_id"]]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(403);
    exit("Your account does not have a customer profile.");
}

$customerId = (int) $customer["id"];

// Load active services.
$stmt = $pdo->query(
    "SELECT id, category, service_name, description,
            price, duration_minutes
     FROM services
     WHERE is_active = 1
     ORDER BY category, service_name"
);
$services = $stmt->fetchAll();

// Load active staff members.
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appointmentDate = trim($_POST["appointment_date"] ?? "");
    $startTime = trim($_POST["start_time"] ?? "");
    $preferredStaffId = trim($_POST["preferred_staff_id"] ?? "");
    $customerNotes = trim($_POST["customer_notes"] ?? "");

    $submittedToken = $_POST["csrf_token"] ?? "";

    $rawServiceIds = $_POST["service_ids"] ?? [];

    if (!is_array($rawServiceIds)) {
        $rawServiceIds = [];
    }

    $selectedServiceIds = array_values(array_unique(
        array_filter(
            array_map("intval", $rawServiceIds),
            fn($id) => $id > 0
        )
    ));

    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = "Your session has expired. Please refresh the page and try again.";
    } elseif (empty($selectedServiceIds)) {
        $error = "Please select at least one service.";
    } elseif ($appointmentDate === "" || $startTime === "") {
        $error = "Please select an appointment date and start time.";
    } elseif (strlen($customerNotes) > 1000) {
        $error = "Your notes must not exceed 1,000 characters.";
    } else {
        try {
            // Validate the date and time.
            $timezone = new DateTimeZone("Asia/Manila");

            $startDateTime = DateTimeImmutable::createFromFormat(
                "!Y-m-d H:i",
                $appointmentDate . " " . $startTime,
                $timezone
            );

            if (!$startDateTime) {
                throw new RuntimeException(
                    "Please enter a valid appointment date and time."
                );
            }

            if ($startDateTime <= new DateTimeImmutable("now", $timezone)) {
                throw new RuntimeException(
                    "Please choose a future appointment time."
                );
            }

            // Only accept start times during business hours.
            if ($startDateTime->format("H:i") < "12:00") {
                throw new RuntimeException(
                    "Appointments can start no earlier than 12:00 PM."
                );
            }

            // Retrieve selected services from the database.
            $placeholders = implode(
                ",",
                array_fill(0, count($selectedServiceIds), "?")
            );

            $stmt = $pdo->prepare(
                "SELECT id, category, service_name, price, duration_minutes
                 FROM services
                 WHERE is_active = 1
                   AND id IN ($placeholders)"
            );

            $stmt->execute($selectedServiceIds);
            $selectedServices = $stmt->fetchAll();

            if (count($selectedServices) !== count($selectedServiceIds)) {
                throw new RuntimeException(
                    "One or more selected services are unavailable. Please refresh the page."
                );
            }

            // Calculate the total price and combined duration on the server.
            $totalAmount = 0;
            $totalDuration = 0;

            foreach ($selectedServices as $service) {
                $duration = (int) $service["duration_minutes"];
                $price = (float) $service["price"];

                if ($duration <= 0 || $price < 0) {
                    throw new RuntimeException(
                        "A selected service has invalid pricing or duration."
                    );
                }

                $totalDuration += $duration;
                $totalAmount += $price;
            }

            if ($totalDuration <= 0) {
                throw new RuntimeException(
                    "The selected services have an invalid total duration."
                );
            }

            $endDateTime = $startDateTime->modify(
                "+" . $totalDuration . " minutes"
            );

            if ($endDateTime->format("Y-m-d") !== $appointmentDate ||
                $endDateTime->format("H:i") > "21:00") {
                throw new RuntimeException(
                    "The selected services must finish by 9:00 PM."
                );
            }

            $endTime = $endDateTime->format("H:i:s");
            $startTimeForDb = $startDateTime->format("H:i:s");

            // Validate an optional preferred staff member.
            $staffId = null;

            if ($preferredStaffId !== "") {
                if (!ctype_digit($preferredStaffId)) {
                    throw new RuntimeException(
                        "Please select a valid staff member."
                    );
                }

                $staffId = (int) $preferredStaffId;

                $stmt = $pdo->prepare(
                    "SELECT s.id
                     FROM staff s
                     INNER JOIN users u ON u.id = s.user_id
                     WHERE s.id = ?
                       AND s.is_active = 1
                       AND u.account_status = 'Active'
                       AND u.role = 'Staff'
                     LIMIT 1"
                );

                $stmt->execute([$staffId]);

                if (!$stmt->fetch()) {
                    throw new RuntimeException(
                        "The selected staff member is unavailable."
                    );
                }

                // Check overlapping requests for this preferred staff member.
                $stmt = $pdo->prepare(
                    "SELECT COUNT(DISTINCT a.id)
                     FROM appointments a
                     LEFT JOIN staff_assignments sa
                       ON sa.appointment_id = a.id
                     WHERE a.appointment_date = ?
                       AND a.status IN (
                           'Pending', 'Confirmed', 'In Progress'
                       )
                       AND a.start_time < ?
                       AND a.end_time > ?
                       AND (
                           a.preferred_staff_id = ?
                           OR sa.staff_id = ?
                       )"
                );

                $stmt->execute([
                    $appointmentDate,
                    $endTime,
                    $startTimeForDb,
                    $staffId,
                    $staffId
                ]);

                if ((int) $stmt->fetchColumn() > 0) {
                    throw new RuntimeException(
                        "The selected staff member has an overlapping appointment request. Please choose another time or staff member."
                    );
                }
            }

            // Save the request and service snapshots together.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO appointments
                    (customer_id, preferred_staff_id,
                     appointment_date, start_time, end_time,
                     status, total_amount, customer_notes)
                 VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)"
            );

            $stmt->execute([
                $customerId,
                $staffId,
                $appointmentDate,
                $startTimeForDb,
                $endTime,
                $totalAmount,
                $customerNotes !== "" ? $customerNotes : null
            ]);

            $appointmentId = (int) $pdo->lastInsertId();

            $serviceInsert = $pdo->prepare(
                "INSERT INTO appointment_services
                    (appointment_id, service_id, service_name_snapshot,
                     unit_price, duration_minutes, quantity)
                 VALUES (?, ?, ?, ?, ?, 1)"
            );

            foreach ($selectedServices as $service) {
                $serviceInsert->execute([
                    $appointmentId,
                    $service["id"],
                    $service["service_name"],
                    $service["price"],
                    $service["duration_minutes"]
                ]);
            }

            $pdo->commit();

            header("Location: my_appointments.php?created=1");
            exit;

        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Appointment booking failed: " . $e->getMessage());

            $error = "We could not submit your appointment. Please try again.";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";

function bookingEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<section class="page-banner">
    <div class="container">
        <h1>Book an Appointment</h1>
        <p>Select your preferred services, date, and time.</p>
    </div>
</section>

<section class="booking-section container">
    <div class="booking-form-card">
        <h2>Appointment Details</h2>

        <p class="booking-note">
            Your request will remain pending until the spa confirms it.
            Please wait for confirmation before visiting.
        </p>

        <?php if ($error !== ""): ?>
            <div class="booking-alert booking-alert-error">
                <?= bookingEscape($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($services)): ?>
            <div class="booking-alert booking-alert-error">
                No active services are available at the moment.
                Please contact the spa.
            </div>
        <?php else: ?>

        <form method="POST" action="book_appointment.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= bookingEscape($csrfToken) ?>"
            >

            <h3>1. Choose Your Services</h3>

            <div class="booking-service-list">
                <?php foreach ($services as $service): ?>
                    <label class="booking-service-option">
                        <input
                            type="checkbox"
                            name="service_ids[]"
                            value="<?= (int) $service["id"] ?>"
                            <?= in_array(
                                (int) $service["id"],
                                $selectedServiceIds,
                                true
                            ) ? "checked" : "" ?>
                        >

                        <span class="booking-service-details">
                            <strong>
                                <?= bookingEscape($service["service_name"]) ?>
                            </strong>

                            <small>
                                <?= bookingEscape($service["category"]) ?>
                                ·
                                <?= (int) $service["duration_minutes"] ?>
                                minutes
                            </small>
                        </span>

                        <strong class="booking-service-price">
                            ₱<?= number_format((float) $service["price"], 2) ?>
                        </strong>
                    </label>
                <?php endforeach; ?>
            </div>

            <h3>2. Choose Date and Time</h3>

            <div class="booking-fields">
                <div class="booking-field">
                    <label for="appointment_date">
                        Appointment Date
                    </label>

                    <input
                        type="date"
                        id="appointment_date"
                        name="appointment_date"
                        min="<?= date("Y-m-d") ?>"
                        value="<?= bookingEscape($appointmentDate) ?>"
                        required
                    >
                </div>

                <div class="booking-field">
                    <label for="start_time">
                        Start Time
                    </label>

                    <input
                        type="time"
                        id="start_time"
                        name="start_time"
                        min="12:00"
                        max="20:59"
                        value="<?= bookingEscape($startTime) ?>"
                        required
                    >
                </div>
            </div>

            <p class="booking-help">
                Business hours: 12:00 PM–9:00 PM.
                The combined duration of your services must fit within
                these hours.
            </p>

            <h3>3. Preferred Staff (Optional)</h3>

            <div class="booking-field">
                <label for="preferred_staff_id">
                    Choose a staff member
                </label>

                <select
                    id="preferred_staff_id"
                    name="preferred_staff_id"
                >
                    <option value="">No preference — let the spa assign staff</option>

                    <?php foreach ($staffMembers as $member): ?>
                        <option
                            value="<?= (int) $member["id"] ?>"
                            <?= (string) $member["id"] === $preferredStaffId
                                ? "selected" : "" ?>
                        >
                            <?= bookingEscape($member["full_name"]) ?>
                            <?php if (!empty($member["position"])): ?>
                                — <?= bookingEscape($member["position"]) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3>4. Additional Notes (Optional)</h3>

            <div class="booking-field">
                <label for="customer_notes">
                    Notes or special requests
                </label>

                <textarea
                    id="customer_notes"
                    name="customer_notes"
                    rows="4"
                    maxlength="1000"
                    placeholder="Enter any relevant requests..."
                ><?= bookingEscape($customerNotes) ?></textarea>
            </div>

            <button type="submit" class="booking-submit-button">
                Submit Appointment Request
            </button>
        </form>

        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
