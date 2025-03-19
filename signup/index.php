<!-- TODO: Add email verification -->
<?php
session_start();
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // Check if the passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if the username or email already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the existing user data
            $row = $result->fetch_assoc();

            if ($row['username'] === $username && $row['email'] === $email) {
                $error = "Both username and email are already taken.";
            } elseif ($row['username'] === $username) {
                $error = "Username already exists.";
            } elseif ($row['email'] === $email) {
                $error = "Email already exists.";
            }
        } else {
            // Add the user to the Database
            $unique_id = uniqid();
            $is_verified = 0; // Set default to 0 for "not verified"
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $timestamp = date("Y-m-d H:i:s");
            $session_id = session_id();
            $token_gen = bin2hex(random_bytes(32)); //generate a 32 byte long token
            $token = base64_encode($token_gen); //base64 encoding

            $stmt = $db->prepare("INSERT INTO users (unique_id, username, email, is_verified, hashed_password, timestamp, session_id, token) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissss", $unique_id, $username, $email, $is_verified, $hashed_password, $timestamp, $session_id, $token);

            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                $verification_link = "http://192.168.1.19:8080/verify.php?it=" . $token;
                // Signup successful, show a message and redirect after 5 seconds
                $success = "Account Created. Please verify your email.";
                try {
                    //server settings
                    $mail->isSMTP();
                    $mail->Host = 'stmp.domain.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'your-email';
                    $mail->Password = 'smtp-password';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587; //change smtp port if necessary

                    //Recipients
                    $mail->setFrom('your-email', 'UrlShortener');
                    $mail->addAddress($email, $username);

                    //Content
                    $mail->isHTML(false);
                    $mail->Subject = 'Verify your email';
                    $mail->Body = "Hello " . $username . ",\n\nCLick the link below to verify yuor email:\n" . $verification_link;
                    $mail->send();
                } catch (Exception $e) {
                    $error = 'Verification Email could not be sent. Error: '. $mail->ErrorInfo;
                }
                

            } else {
                $error = "Error Creating user: " . $stmt->error; 
            }
            $stmt->close();
        }
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
    <div class="w-full max-w-xs">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-white text-center mb-4">SIGN UP</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="block text-white mb-1" for="username">Username</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="text" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="block text-white mb-1" for="email">Email</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="email" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="block text-white mb-1" for="password">Password</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="password" id="password" name="password" required>
                </div>
                <div class="mb-4">
                    <label class="block text-white mb-1" for="confirm-password">Confirm Password</label>
                    <input class="w-full p-2 border border-gray-400 rounded" type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <div class="flex justify-center">
                    <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700" type="submit">Sign Up</button>
                </div>
                <div class="h-6 flex justify-center">
                    <?php if (isset($error)): ?>
                        <p class="text-red-500 text-center"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="success-message" style="color: green;">
                            <?php echo $success; ?>
                            <p>Redirecting to login in <span id="countdown">5</span> seconds...</p>
                        </div>

                        <script>
                            var countdown = 5;
                            var countdownElement = document.getElementById("countdown");

                            setInterval(function() {
                                countdown--;
                                countdownElement.textContent = countdown;
                                if (countdown === 0) {
                                    window.location.href = "/";
                                }
                            }, 1000);
                        </script>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="mt-4 text-center">
            <p class="text-white">Already have an account? <a href="/" class="text-purple-500 hover:text-white hover:underline">Login instead</a></p>
        </div>
    </div>
</body>
</html>