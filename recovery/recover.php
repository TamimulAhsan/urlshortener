<?php
// Include database config
require '../config.php';

$db = new mysqli($servername, $username, $password, $dbname);

if (!isset($_GET['rt'])) {
    die("");
}

$r_token_rec = $_GET['rt'];
$r_token = base64_decode($r_token_rec);

$stmt = $db->prepare("SELECT username FROM users WHERE r_token = ?");
$stmt->bind_param("s", $r_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $recovery_status = "invalid"; // invalid token: show the error message
} else {
    $recovery_status = "processing"; // processing: show recovery mechanisms
    $row = $result->fetch_assoc();
    $username = htmlspecialchars($row['username']);
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        //Check if the entered passwords match
        if($new_password == $confirm_password){
            //match. update in database.
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET hashed_password = ?, r_token = NULL WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $username);
            $stmt->execute();
            $recovery_status = "success"; // success: show success message
        } else {
            $error = "Passwords Do not match.";
        }
    }
}
$stmt->close();
$db->close();

?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const recovery_status = "<?php echo $recovery_status; ?>";
            const username = "<?php echo isset($username) ? $username : ''; ?>";

            if (recovery_status === "invalid") {
                document.getElementById("message").textContent = "Invalid Recovery Token";
                document.getElementById("submessage").textContent = "You'll be redirected to login page in 5 seconds.";
                document.getElementById("countdown").style.display = "inline-block";
                startCountdown();
            } else if (recovery_status === "processing") {
                document.getElementById("resetForm").style.display = "block";
                document.getElementById("username").value = username;
            } else if (recovery_status === "success") {
                document.getElementById("message").textContent = "Your password has been reset";
                document.getElementById("submessage").textContent = "You can now login with your new password. You'll be redirected to login page in 5 seconds.";
                document.getElementById("countdown").style.display = "inline-block";
                startCountdown();
            }
        });

        function startCountdown() {
            const countdownElement = document.getElementById('countdown');
            let countdown = 5;
            const interval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown === 0) {
                    clearInterval(interval);
                    // Redirect to login page
                    window.location.href = '/';
                }
            }, 1000);
        }
    </script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 id="message" class="text-3xl font-bold text-white mb-4"></h1>
        <p id="submessage" class="text-lg text-gray-300 mb-6"></p>
        <p id="countdown" class="text-lg text-gray-300 mb-6" style="display: none;">5</p>
        <div id="resetForm" class="bg-gray-800 p-8 rounded-lg shadow-lg border-2 border-purple-500 w-96" style="display: none;">
            <h2 class="text-2xl font-bold text-white text-center mb-6">PASSWORD RESET</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-white mb-2" for="username">Username</label>
                    <input class="w-full p-2 rounded border border-gray-600 bg-gray-700 text-gray-400" type="text" id="username" name="username" disabled>
                </div>
                <div class="mb-4">
                    <label class="block text-white mb-2" for="new-password">New Password</label>
                    <input class="w-full p-2 rounded border border-gray-600 bg-gray-700 text-white" type="password" id="new-password" name="new_password">
                </div>
                <div class="mb-6">
                    <label class="block text-white mb-2" for="confirm-password">Confirm Password</label>
                    <input class="w-full p-2 rounded border border-gray-600 bg-gray-700 text-white" type="password" id="confirm-password" name="confirm_password">
                </div>
                <?php if (isset($error)): ?>
                    <p class="text-red-500 text-center mb-4"><?php echo $error; ?></p>
                <?php endif; ?>
                <div class="text-center">
                    <button class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700">Reset</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>