<?php
require 'config.php';

// Include database connection
$db = new mysqli($servername, $username, $password, $dbname);

if (!isset($_GET['it'])) {
    die("");
}

$token_rec = $_GET['it'];
$token = base64_decode($token_rec);

// Fetch user from the database based on the token
$stmt = $db->prepare("SELECT username, is_verified FROM users WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $verificationStatus = "failed"; // Token not found
} else {
    $user = $result->fetch_assoc();
    $username = htmlspecialchars($user['username']);
    
    if ($user['is_verified'] == 1) {
        $verificationStatus = "already_verified"; // Already verified
        header("refresh:5;url=/");
    } else {
        // Update the user to mark as verified
        $stmt = $db->prepare("UPDATE users SET is_verified = 1, token = NULL WHERE token = ?");
        $stmt->bind_param("s", $token);
        if ($stmt->execute()) {
            $verificationStatus = "success"; // Successfully verified
        } else {
            $verificationStatus = "failed"; // Database update error
        }
    }
}

$stmt->close();
$db->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const verificationStatus = "<?php echo $verificationStatus; ?>";
            const username = "<?php echo isset($username) ? $username : ''; ?>";

            if (verificationStatus === "success") {
                document.getElementById("message").innerHTML = `Congratulations <span id="username">${username}</span>!`;
                document.getElementById("submessage").textContent = "Your account has been verified. You can now log in.";
                document.getElementById("loginButton").style.display = "inline-block";
            } else if (verificationStatus === "already_verified") {
                document.getElementById("message").textContent = "Already Verified!";
                document.getElementById("submessage").textContent = "Your email has already been verified. Redirecting to Login in 5 seconds.";
                document.getElementById("loginButton").style.display = "inline-block";
            } else {
                document.getElementById("message").textContent = "Verification Failed!";
                document.getElementById("submessage").textContent = "Invalid or expired verification link. Please try again.";
                document.getElementById("loginButton").style.display = "none";
            }
        });
    </script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 id="message" class="text-3xl font-bold text-white mb-4"></h1>
        <p id="submessage" class="text-lg text-gray-300 mb-6"></p>
        <a id="loginButton" href="/" class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-opacity-50" style="display: none;">Login</a>
    </div>
</body>
</html>