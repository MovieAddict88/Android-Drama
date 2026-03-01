<?php
session_start();

if (file_exists('includes/config.php')) {
    die("Installation already completed. Delete includes/config.php to re-install.");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    $admin_user = $_POST['admin_user'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$db_name`;");

        $schema = file_get_contents('database/schema.sql');
        $pdo->exec($schema);

        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$admin_user]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$admin_user, $admin_pass]);
        }

        // Create config.php
        $config_content = "<?php\n" .
            "define('DB_HOST', " . var_export($db_host, true) . ");\n" .
            "define('DB_NAME', " . var_export($db_name, true) . ");\n" .
            "define('DB_USER', " . var_export($db_user, true) . ");\n" .
            "define('DB_PASS', " . var_export($db_pass, true) . ");\n";

        file_put_contents('includes/config.php', $config_content);

        $success = "Installation successful! <a href='admin/login.php'>Go to Admin Panel</a>";
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drama Website Auto-Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Installation</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php else: ?>
                            <form method="POST">
                                <h4 class="mb-3">Database Connection</h4>
                                <div class="mb-3">
                                    <label class="form-label">Host</label>
                                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" name="db_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="db_user" class="form-control" value="root" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="db_pass" class="form-control">
                                </div>
                                <hr>
                                <h4 class="mb-3">Admin Account</h4>
                                <div class="mb-3">
                                    <label class="form-label">Admin Username</label>
                                    <input type="text" name="admin_user" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Admin Password</label>
                                    <input type="password" name="admin_pass" class="form-control" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Install Now</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
