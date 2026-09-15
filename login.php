<?php

session_start();

require_once "includes/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {

        $message = "Please enter your phone/email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, full_name, phone, email, password, role
             FROM users
             WHERE phone = ? OR email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $message = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    // Save logged-in user's information
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["role"] = $user["role"];

                    // Go to user's dashboard
                    header("Location: dashboard.php");
                    exit;

                } else {

                    $message = "Incorrect password.";

                }

            } else {

                $message = "Account not found.";

            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | KaziHub Tanzania</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .auth-section {
            min-height: calc(100vh - 78px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
            background: #f8fafc;
        }

        .auth-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            padding: 40px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.10);
        }

        .auth-card h1 {
            font-size: 32px;
            margin: 8px 0;
        }

        .auth-description {
            color: #64748b;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #2563eb;
        }

        .login-submit {
            width: 100%;
            border: none;
            background: #2563eb;
            color: white;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 5px;
        }

        .login-submit:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 13px;
            border-radius: 8px;
            background: #fef2f2;
            color: #dc2626;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .register-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        .register-link a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

    </style>

</head>

<body>

<header class="navbar">

    <div class="container nav-container">

        <div class="logo">
    <span>KaziHub</span>
    <small>🇹🇿</small>
</div>

        <nav class="nav-links">

            <a href="index.php">
                Home
            </a>

        </nav>

    </div>

</header>


<section class="auth-section">

    <div class="auth-card">

        <span class="section-label">
            WELCOME BACK
        </span>

        <h1>
            Login to KaziHub
        </h1>

        <p class="auth-description">
            Sign in to manage your account and connect with Tanzania's service marketplace.
        </p>


        <?php if ($message !== ""): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="login.php">

            <div class="form-group">

                <label>
                    Phone or Email
                </label>

                <input
                    type="text"
                    name="login"
                    placeholder="Enter your phone or email"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="login-submit"
            >
                Login
            </button>

        </form>


        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>

    </div>

</section>

</body>

</html>