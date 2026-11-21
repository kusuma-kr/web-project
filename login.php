
<?php
require_once "config.php";
session_start();

if (isset($_SESSION["student_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (
    !isset($_SESSION["csrf"], $_POST["csrf"]) ||
    !hash_equals($_SESSION["csrf"], $_POST["csrf"])
) {
    $error = "Invalid security token. Please refresh the page.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - SpendWise</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <h1>💰 SpendWise</h1>
    <h2>Student Login</h2>
    <p>Welcome back!</p>

    <?php if ($error): ?>
        <p class="auth-error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
            value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">

        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p class="auth-footer">
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>
</div>

</body>
</html>
