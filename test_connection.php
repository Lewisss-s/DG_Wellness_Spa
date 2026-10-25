
<?php

require_once __DIR__ . "/config/database.php";

$tableCount = $pdo->query("
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = 'dg_wellness_spa'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
</head>
<body>

    <h1>DG Wellness Spa</h1>

    <p>Database connection successful!</p>

    <p>
        Number of tables:
        <?= htmlspecialchars((string) $tableCount, ENT_QUOTES, 'UTF-8') ?>
    </p>

</body>
</html>
