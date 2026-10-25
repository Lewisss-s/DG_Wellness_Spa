
<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/auth.php";

requireRole("Admin");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $position = trim($_POST["position"] ?? "");

    if (
        $fullName === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $position === ""
    ) {
        $error = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (strlen($fullName) > 150 || strlen($email) > 150) {
        $error = "Name or email is too long.";
    } elseif (strlen($phone) > 30 || strlen($position) > 100) {
        $error = "Phone number or position is too long.";
    } else {
        try {
            $pdo->beginTransaction();

            // Create the Staff user account.
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (full_name, email, password_hash, role, account_status)
                VALUES (?, ?, ?, 'Staff', 'Active')"
            );

            $stmt->execute([
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = (int) $pdo->lastInsertId();

            // Create the corresponding Staff profile.
            $stmt = $pdo->prepare(
                "INSERT INTO staff
                (user_id, phone, position, is_active)
                VALUES (?, ?, ?, 1)"
            );

            $stmt->execute([$userId, $phone, $position]);

            $pdo->commit();

            $success = "Staff account created successfully.";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === "23000") {
                $error = "This email address is already registered.";
            } else {
                error_log("Staff account creation failed: " . $e->getMessage());
                $error = "Unable to create the staff account. Please check your database tables.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Staff Account | DG Wellness Spa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-container">
    <h1>Create Staff Account</h1>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p style="color: green;">
            <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="full_name">Full Name</label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            maxlength="150"
            required
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            required
        >

        <label for="phone">Phone Number</label>
        <input
            type="text"
            id="phone"
            name="phone"
            maxlength="30"
            required
        >

        <label for="position">Position</label>
        <select id="position" name="position" required>
            <option value="">Select position</option>
            <option value="Massage Therapist">Massage Therapist</option>
            <option value="Nail Technician">Nail Technician</option>
        </select>

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
        >

        <button type="submit">Create Staff Account</button>
    </form>

    <p>
        <a href="dashboard.php">Back to Admin Dashboard</a>
    </p>
</div>

</body>
</html>
