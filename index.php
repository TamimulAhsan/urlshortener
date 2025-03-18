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
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
	$result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['hashed_password'])) {
            $unique_id = $user['unique_id'] ?? uniqid();

            $_SESSION['unique_id'] = $unique_id;

            //Generate a new session id
            session_regenerate_id(true);
            $session_id = session_id();

            //store the session ID and make sure the user has a unique id in the db
            try {
                $stmt = $db->prepare("UPDATE users SET session_id = ?, unique_id = ? WHERE username = ?");
                $stmt->bind_param("sss", $session_id, $unique_id, $username);
                if (!$stmt->execute()) {
                    $error = "Error Updating Session ID: " . $stmt->error;
                }
                $stmt->execute();
            } catch (Exception $e) {
                $error = "Error Updating Session ID: " . $e->getMessage();
            }

            //Set the session cookie
            setcookie("PHPSESSID", $session_id, [
                "expires" => time() + 3600,
                "path" => "/",
                "domain" => "192.168.1.19",
                "secure" => false, //Set to true if using https
                "httponly" => true, //set to false if using https
                "samesite" => "Lax"
            ]);

            //Update the last login time
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE users SET timestamp = ? WHERE unique_id = ?");
            $stmt->bind_param("ss", $now, $user['unique_id']);
            $stmt->execute();

            //Redirect to main page
            header("Location: /app");
            exit();
        
        } else {
            $error = "Invalid Username or Password";
        } 
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
    $stmt->close();
}
$db->close();
?>

<?php
$resultMessage = ''; // Initialize the variable to avoid undefined variable warning
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
                    <label class="block text-white mb-2" for="username">Username</label>
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
