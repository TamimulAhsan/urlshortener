<!-- TODO: Add email verification -->
<?php
session_start();
// Start Database Connection
$servername= "localhost";
$username= "django_user";
$password= "yourpassword";
$dbname = "url_shortener_db";

$db = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($db->connect_error) {
    die("Database Connection Failed: " . $db->connect_error);
}

// Handle Form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    //Check if the passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if the usename already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Add the user to the Database
            $unique_id = uniqid();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $timestamp = date("Y-m-d H:i:s");
            $session_id = session_id();

            $stmt = $db->prepare("INSERT INTO users (unique_id, username, hashed_password, timestamp, session_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $unique_id, $username, $hashed_password, $timestamp, $session_id);

            if ($stmt->execute()) {
                //Signup Successful. Prompt the user to login
                header("Location: /");
                exit();
            } else {
                $error = "Error Creating user" .$stmt->$error;
            }
        }
        $stmt->close();
    }
}
$db->close();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm">
        <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-6">SIGN UP</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-white mb-2" for="username">Username</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="text" id="username" name="username">
                </div>
                <div class="mb-4">
                    <label class="block text-white mb-2" for="password">Password</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="password" id="password" name="password">
                </div>
                <div class="mb-6">
                    <label class="block text-white mb-2" for="confirm-password">Confirm Password</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="password" id="confirm-password" name="confirm-password">
                </div>
                <div class="h-6 flex justify-center">
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 text-center"><?php echo $error; ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex justify-center">
                    <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Sign Up</button>
                </div>
            </form>
        </div>
        <div class="mt-4 text-center">
            <p class="text-white">Already have an account? <a href="/" class="text-purple-500 hover:text-white hover:underline">Login instead</a></p>
        </div>
    </div>
</body>
</html>
