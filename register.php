<?php

session_start();

require_once "includes/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "customer";

    // Check required fields
    if (
        $full_name === "" ||
        $phone === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $message = "Please fill in all required fields.";

    // Check password confirmation
    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        // Check if phone or email already exists
        $check = $conn->prepare(
            "SELECT id FROM users
             WHERE phone = ? OR (email = ? AND email IS NOT NULL AND email != '')
             LIMIT 1"
        );

        $check->bind_param("ss", $phone, $email);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $message = "Phone number or email already exists.";

            $check->close();

        } else {

            $check->close();

            // Hash password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Convert empty email to NULL
            $email_value = $email === "" ? null : $email;

            // Insert new user
            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, phone, email, password, role)
                VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt) {

                $message = "Database error: " . $conn->error;

            } else {

                $stmt->bind_param(
                    "sssss",
                    $full_name,
                    $phone,
                    $email_value,
                    $hashed_password,
                    $role
                );

                if ($stmt->execute()) {

                    // Save new user's information in session
                    $_SESSION["user_id"] = $stmt->insert_id;
                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["role"] = $role;

                    // Send user directly to Dashboard
                    header("Location: dashboard.php");
                    exit;

                } else {

                    $message = "Registration failed. Please try again.";

                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | KaziHub Tanzania</title>

    <!-- IMPORTANT: CSS is inside the css folder -->
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
            max-width: 520px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2563eb;
        }

        .register-submit {
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

        .register-submit:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 13px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            font-weight: 700;
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


        <div class="nav-buttons">
            </a>

        </div>

    </div>

</header>



<section class="auth-section">

    <div class="auth-card">

        <span class="section-label">
            JOIN KaziHub Tanzania
        </span>


        <h1>
            Create your account
        </h1>


        <p class="auth-description">
            Join KaziHub Tanzania and connect with customers or professionals.
        </p>


        <?php if ($message !== ""): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="register.php">


            <div class="form-group">

                <label>
                    Full Name *
                </label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    required
                >

            </div>



            <div class="form-group">

                <label>
                    Phone Number *
                </label>

                <input
                    type="text"
                    name="phone"
                    placeholder="e.g. 0712 345 678"
                    required
                >

            </div>



            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="example@email.com"
                >

            </div>



            <div class="form-group">

                <label>
                    Password *
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

            </div>

            <div class="form-group">

    <label>
        Confirm Password *
    </label>

    <input
        type="password"
        name="confirm_password"
        placeholder="Confirm your password"
        required
    >

</div>



            <div class="form-group">

                <label>
                    Account Type *
                </label>

                <select name="role" required>

                    <option value="customer">
                        Customer — I need services
                    </option>

                    <option value="provider">
                        Provider — I offer services
                    </option>

                </select>

            </div>



            <button
                type="submit"
                class="register-submit"
            >
                Create Account
            </button>


        </form>


        <div class="login-link">

            Already have an account?

            <a href="#">
                Login
            </a>

        </div>


    </div>

</section>


</body>

</html>