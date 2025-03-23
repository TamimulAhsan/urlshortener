<?php
//include database config
require '../config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//start database connection
$db = new mysqli($servername, $username, $password, $dbname);
//check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$is_success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    //check if email exists in database
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        //email exists, set password reset token in database
        $row = $result->fetch_assoc();
        //generate a 32 byte long recovery token
        $token = bin2hex(random_bytes(32)); 

        $stmt = $db->prepare("UPDATE users SET r_token = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);

        if ($stmt->execute()) { //send password reset link
            $mail = new PHPMailer(true);
            $username = htmlspecialchars($row["username"]); //get username
            $token_toSend = base64_encode($token);
            //set the recovery uri
            $recovery_link = "http://192.168.1.19:8080/recovery/recover.php?rt=" .$token_toSend;
            try{
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
                $mail->Subject = 'Password Recovery.';
                $mail->Body = "Hello " . $username . ",\n\nClick the link below and follow on screen steps to recover your password:\n" . $recovery_link;
                $mail->send();
                $status = "A password reset instruction has been sent to your email.";
            } catch (Exception $e) {
                $status = 'Email could not be sent. Mailer Error: ' . $e;
            }
        } 
        $stmt->close();
    }
}
$db->close();
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div id="form-container" class="text-center text-white">
        <h1 class="text-3xl font-bold mb-4">FORGOT PASSWORD</h1>
        <p class="mb-2">Enter the email address associated with your account</p>
        <p class="mb-6">We will send password reset instructions to your email if it exists in our record</p>
        <form class="space-y-4" method="POST">
            <div>
                <label for="email" class="block mb-2">Email address</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required>
            </div>
            <button type="submit" class="py-2 px-6 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-md focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-opacity-50">Send</button>
            <div class="mt5-text-center">
                <?php if (isset($status)): ?>
                    <p class="text-green-500 text-center"><?php echo $status; ?></p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>
