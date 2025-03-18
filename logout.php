<?php
session_start();
$session_id = session_id();
// Database connection
$servername = "localhost";
$username = "django_user";
$password = "yourpassword";
$dbname = "url_shortener_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Clear session from database
$query = "UPDATE users SET session_id = NULL WHERE session_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_id);
$stmt->execute();

// Clear session cookie
setcookie("PHPSESSID", "", time() - 3600, "/", "", false, true);
session_destroy();

// Redirect to login page
header("Location: /");
exit();
?>
