<?php
// Include database connection file
include '_config.php';

// Initialize variables
$email = '';
$current_password = '';
$new_password = '';
$confirm_password = '';
$message = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $email = $_POST['email'];
    $current_password = $_POST['current-password'];
    $new_password = $_POST['new-password'];
    $confirm_password = $_POST['confirm-password'];

    // Check if new password and confirm password match
    if ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
    } else {
        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Verify the current password
            if (password_verify($current_password, $row['password'])) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update_stmt->bind_param("ss", $hashed_password, $email);
                if ($update_stmt->execute()) {
                    $message = "Password updated successfully.";
                } else {
                    $message = "Error updating password. Please try again.";
                }
            } else {
                $message = "Current password is incorrect.";
            }
        } else {
            $message = "No user found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="./css/main.css">
    <link rel="stylesheet" type="text/css" href="./css/admin.css">
    <link rel="stylesheet" type="text/css" href="./css/util.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="form-container">
    <div class="admin-settings">
        <h2>Admin Settings</h2>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="Email">Email</label>
            <input type="text" id="email" name="email" required>

            <label for="current-password">Current Password</label>
            <input type="password" id="current-password" name="current-password" required>

            <label for="new-password">New Password</label>
            <input type="password" id="new-password" name="new-password" required>

            <label for="confirm-password">Confirm New Password</label>
            <input type="password" id="confirm-password" name="confirm-password" required>

            <div class="show-password">
                <input type="checkbox" id="show-password" onclick="togglePasswordVisibility()">
                <label for="show-password">Show Password</label>
            </div>

            <button type="submit">Update Account</button>
        </form>
    </div>
</div>
<script>
    function togglePasswordVisibility() {
        const passwordFields = [
            document.getElementById('current-password'),
            document.getElementById('new-password'),
            document.getElementById('confirm-password')
        ];
        passwordFields.forEach(field => {
            field.type = field.type === 'password' ? 'text' : 'password';
        });
    }
</script>
</body>
</html>
