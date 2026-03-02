<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_user'] = 'admin';
echo "Session set. <a href='admin/index.php'>Go to admin</a>";
