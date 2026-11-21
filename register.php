
<?php
require_once "config.php";
session_start();

$error = "";

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (!hash_equals($_SESSION["csrf"], $_POST["csrf"] ?? "")) {
        $error = "Invalid request. Please refresh.";
    } elseif ($name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8) {
        $error = "Enter valid details. Password must be at least 8 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO students (name, email, password)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param("sss", $name, $email, $hash);

        if ($stmt->execute()) {
            session_regenerate_id(true);
            $_SESSION["student_id"] = $stmt->insert_id;
            $_SESSION["student_name"] = $name;

            header("Location: index.php");
            exit;
        } else {
            $error = $conn->errno === 1062
                ? "Email already registered."
                : "Registration failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register - SpendWise</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <h1>💰 SpendWise</h1>
    <h2>Create Student Account</h2>
    <p>Start managing your money wisely.</p>

    <?php if ($error): ?>
        <p class="auth-error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
            value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">

        <label>Full Name</label>
        <input type="text" name="name"
            placeholder="Enter your name" required>

        <label>Email Address</label>
        <input type="email" name="email"
            placeholder="Enter your email" required>

        <label>Password</label>
        <input type="password" name="password"
            minlength="8" placeholder="Minimum 8 characters" required>

        <button type="submit">Create Account</button>
    </form>

    <p class="auth-footer">
        Already registered?
        <a href="login.php">Login here</a>
    </p>
</div>

</body>
</html>