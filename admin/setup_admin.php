
<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

$count = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'Admin'"
)->fetchColumn();

if ($count > 0) {
    http_response_code(403);
    exit("An Admin account already exists. Remove this setup file.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid name and email.";
    } elseif (strlen($name) > 150 || strlen($email) > 150) {
        $error = "The name or email is too long.";
    } elseif (strlen($password) < 12) {
        $error = "Use an Admin password of at least 12 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users
                    (full_name, email, password_hash, role, account_status)
                 VALUES (?, ?, ?, 'Admin', 'Active')"
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            echo "Admin account created successfully. Delete setup_admin.php now.";
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $error = "This email address is already registered.";
            } else {
                error_log("Admin setup failed: " . $e->getMessage());
                $error = "Could not create the Admin account.";
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
    <title>Initial Admin Setup</title>
</head>
<body>

<h1>Create Initial Admin</h1>

<?php if ($error !== ""): ?>
    <p><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
<?php endif; ?>

<form method="POST">
    <label>Full Name</label>
    <input name="full_name" maxlength="150" required>

    <label>Email</label>
    <input type="email" name="email" maxlength="150" required>

    <label>Password (at least 12 characters)</label>
    <input type="password" name="password" minlength="12" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" minlength="12" required>

    <button type="submit">Create Admin</button>
</form>

</body>
</html>
