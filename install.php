<?php
// php_project/install.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists('config.php')) {
    die("Application already installed. Delete config.php to reinstall.");
}

$message = "";
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $token_url = $_POST['token_url'] ?? '';

    try {
        // Try to connect to the database directly first (common for InfinityFree)
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            // If failed, try to connect without dbname and create it (for local dev)
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name` ");

            // Reconnect
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create donors table
        $pdo->exec("CREATE TABLE IF NOT EXISTS donors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            amount INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Add dummy donor
        $stmt = $pdo->prepare("INSERT INTO donors (name, amount) VALUES (?, ?)");
        $stmt->execute(['Felix', 50000]);

        // Create config.php
        $config_content = "<?php\n";
        $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
        $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
        $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
        $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n\n";
        $config_content .= "define('DRAMABOX_TOKEN_URL', " . var_export($token_url, true) . ");\n";
        $config_content .= "define('DRAMABOX_VERSION_CODE', '430');\n";
        $config_content .= "define('DRAMABOX_VERSION_NAME', '4.3.0');\n";
        $config_content .= "define('DRAMABOX_CID', 'DRA1000042');\n";
        $config_content .= "define('DRAMABOX_PACKAGE_NAME', 'com.storymatrix.drama');\n";
        $config_content .= "define('DRAMABOX_APN', '1');\n";
        $config_content .= "define('DRAMABOX_LANGUAGE', 'in');\n";
        $config_content .= "define('DRAMABOX_PLATFORM_P', '43');\n";
        $config_content .= "define('PROJECT_NAME', 'DramaBoxGratis');\n";

        if (file_put_contents('config.php', $config_content)) {
            $message = "Installation successful! <a href='index.php' class='underline'>Go to Homepage</a>";
        } else {
            $message = "Failed to create config.php. Check permissions.";
            $error = true;
        }

    } catch (PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install DramaBoxGratis PHP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full p-8 bg-slate-800 rounded-xl shadow-2xl border border-slate-700">
        <h1 class="text-2xl font-bold mb-6 text-center text-blue-400">DramaBoxGratis Installer</h1>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded <?php echo $error ? 'bg-red-500/20 text-red-400 border border-red-500/50' : 'bg-green-500/20 text-green-400 border border-green-500/50'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$message || $error): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">DB Host</label>
                <input type="text" name="db_host" value="localhost" required class="w-full p-2 bg-slate-700 border border-slate-600 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">DB Name</label>
                <input type="text" name="db_name" required class="w-full p-2 bg-slate-700 border border-slate-600 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">DB User</label>
                <input type="text" name="db_user" required class="w-full p-2 bg-slate-700 border border-slate-600 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">DB Password</label>
                <input type="password" name="db_pass" class="w-full p-2 bg-slate-700 border border-slate-600 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">DRAMABOX_TOKEN_URL</label>
                <input type="url" name="token_url" placeholder="https://..." required class="w-full p-2 bg-slate-700 border border-slate-600 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-slate-400 mt-1">URL to fetch DramaBox auth token.</p>
            </div>
            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition duration-200">
                Install Now
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
