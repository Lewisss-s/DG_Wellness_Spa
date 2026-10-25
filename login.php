

<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/auth.php";

// If already logged in, redirect to the correct dashboard.
if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    redirectByRole();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash, role, account_status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (
            $user &&
            password_verify($password, $user["password_hash"]) &&
            $user["account_status"] === "Active"
        ) {
            // Regenerate only after the session has started.
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            redirectByRole();
        } else {
            $error = "Invalid email or password, or your account is inactive.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DG Wellness Spa</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="auth-container">
    <h1>DG Wellness Spa</h1>
    <h2>Login</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account?
        <a href="register.php">Register as a client</a>
    </p>
</main>

</body>
</html>
