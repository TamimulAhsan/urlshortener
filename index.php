<?php
session_start();

// Database connection
$servername = "localhost";
$username = "django_user";
$password = "yourpassword";
$dbname = "url_shortener_db";

$db = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['username']; // Can be either username or email
    $password = $_POST['password'];

    try {
        // Check if input is email or username and fetch user details
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['hashed_password'])) {
            if ($user['is_verified'] == 0) {
                $error = "Please verify your email before logging in.";
            } else {
                $unique_id = $user['unique_id'] ?? uniqid();
                $_SESSION['unique_id'] = $unique_id;

                // Generate a new session ID
                session_regenerate_id(true);
                $session_id = session_id();

                // Store the session ID in the database
                $stmt = $db->prepare("UPDATE users SET session_id = ?, unique_id = ? WHERE username = ?");
                $stmt->bind_param("sss", $session_id, $unique_id, $user['username']);
                if (!$stmt->execute()) {
                    $error = "Error Updating Session ID: " . $stmt->error;
                }

                // Set the session cookie
                setcookie("PHPSESSID", $session_id, [
                    "expires" => time() + 3600,
                    "path" => "/",
                    "domain" => "192.168.1.19",
                    "secure" => false, // Set to true if using HTTPS
                    "httponly" => true,
                    "samesite" => "Lax"
                ]);

                // Update the last login time
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("UPDATE users SET timestamp = ? WHERE unique_id = ?");
                $stmt->bind_param("ss", $now, $user['unique_id']);
                $stmt->execute();

                // Redirect to main page
                header("Location: /app");
                exit();
            }
        } else {
            $error = "Invalid Username/Email or Password";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
    $stmt->close();
}
$db->close();
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-6">LOGIN</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-white mb-2" for="username">Username/Email</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="text" id="username" name="username">
                </div>
                <div class="mb-6">
                    <label class="block text-white mb-2" for="password">Password</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="password" id="password" name="password">
                </div>
                <div class="flex justify-center">
                    <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Login</button>
                </div>
                <div class="h-6 flex justify-center">
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 text-center"><?php echo $error; ?></p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="mt-4 text-center">
            <p class="text-white">New here? <a href="/signup" class="text-purple-500 hover:text-white hover:underline">Create an account</a></p>
        </div>
    </div>
</body>
</html>
