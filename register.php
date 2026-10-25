
<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

if (isset($_SESSION["user_id"])) {
    redirectByRole();
}

$error = "";
$fullName = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $fullName === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $error = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($fullName) > 150 || strlen($email) > 150) {
        $error = "The name or email is too long.";
    } elseif (strlen($phone) > 30) {
        $error = "The phone number is too long.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO users
                    (full_name, email, password_hash, role, account_status)
                 VALUES (?, ?, ?, 'Client', 'Active')"
            );

            $stmt->execute([
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO customers (user_id, phone)
                 VALUES (?, ?)"
            );

            $stmt->execute([$userId, $phone]);

            $pdo->commit();

            header("Location: login.php?registered=1");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === "23000") {
                $error = "This email address is already registered.";
            } else {
                error_log("Registration failed: " . $e->getMessage());
                $error = "Registration failed. Please try again.";
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
    <title>Client Registration | DG Wellness Spa</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="auth-container">
    <h1>DG Wellness Spa</h1>
    <h2>Client Registration</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label for="full_name">Full Name</label>
        <input
            type="text"
            id="full_name"
            name="full_name"
            maxlength="150"
            value="<?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>"
            autocomplete="name"
            required
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
            autocomplete="email"
            required
        >

        <label for="phone">Contact Number</label>
        <input
            type="tel"
            id="phone"
            name="phone"
            maxlength="30"
            value="<?= htmlspecialchars($phone, ENT_QUOTES, "UTF-8") ?>"
            autocomplete="tel"
            required
        >

        <label for="password">Password (at least 8 characters)</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <button type="submit">Create Account</button>
    </form>

    <p>Already registered? <a href="login.php">Login here</a></p>
</main>

</body>
</html>
