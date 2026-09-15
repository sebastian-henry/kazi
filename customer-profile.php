<?php
session_start();
require_once "includes/config.php";
/* =========================
   CUSTOMER LOGIN CHECK
========================= */
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "customer") {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
$message = "";
$message_type = "";
/* =========================
   LOAD CUSTOMER
========================= */
$stmt = $conn->prepare("
    SELECT id, full_name, phone, email, profile_image
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$stmt->close();
/* =========================
   UPDATE PROFILE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    if ($full_name === "" || $phone === "" || $email === "") {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        /* =========================
           PROFILE IMAGE UPLOAD
        ========================= */
        $profile_image = $customer["profile_image"];
        if (
            isset($_FILES["profile_image"]) &&
            $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES["profile_image"]["error"] === UPLOAD_ERR_OK) {
                $max_size = 5 * 1024 * 1024;
                if ($_FILES["profile_image"]["size"] > $max_size) {
                    $message = "Profile image must be less than 5MB.";
                    $message_type = "error";
                } else {
                    $allowed_types = [
                        "image/jpeg" => "jpg",
                        "image/png"  => "png",
                        "image/webp" => "webp"
                    ];
                    $file_type = mime_content_type($_FILES["profile_image"]["tmp_name"]);
                    if (!isset($allowed_types[$file_type])) {
                        $message = "Only JPG, PNG and WEBP images are allowed.";
                        $message_type = "error";
                    } else {
                        $upload_dir = __DIR__ . "/uploads/profile_images/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0775, true);
                        }
                        $extension = $allowed_types[$file_type];
                        $filename =
                            "customer_" .
                            $user_id . "_" .
                            time() . "_" .
                            bin2hex(random_bytes(4)) .
                            "." .
                            $extension;
                        $target = $upload_dir . $filename;
                        if (move_uploaded_file(
                            $_FILES["profile_image"]["tmp_name"],
                            $target
                        )) {
                            /* Delete old customer image */
                            if (
                                !empty($profile_image) &&
                                strpos($profile_image, "uploads/profile_images/") === 0
                            ) {
                                $old_file = __DIR__ . "/" . $profile_image;
                                if (file_exists($old_file)) {
                                    @unlink($old_file);
                                }
                            }
                            $profile_image =
                                "uploads/profile_images/" . $filename;
                        } else {
                            $message = "Failed to upload profile image.";
                            $message_type = "error";
                        }
                    }
                }
            } else {
                $message = "There was a problem uploading the image.";
                $message_type = "error";
            }
        }
        /* =========================
           SAVE PROFILE
        ========================= */
        if ($message_type !== "error") {
            $stmt = $conn->prepare("
                UPDATE users
                SET full_name = ?,
                    phone = ?,
                    email = ?,
                    profile_image = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssi",
                $full_name,
                $phone,
                $email,
                $profile_image,
                $user_id
            );
            if ($stmt->execute()) {
                $_SESSION["full_name"] = $full_name;
                $message = "Profile updated successfully.";
                $message_type = "success";
                $customer["full_name"] = $full_name;
                $customer["phone"] = $phone;
                $customer["email"] = $email;
                $customer["profile_image"] = $profile_image;
            } else {
                $message = "Failed to update profile.";
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}
/* =========================
   PROFILE IMAGE
========================= */
$profile_image = $customer["profile_image"] ?? "";
$has_profile_image = false;
if (
    !empty($profile_image) &&
    strpos($profile_image, "uploads/") === 0 &&
    file_exists(__DIR__ . "/" . $profile_image)
) {
    $has_profile_image = true;
}
$initial = strtoupper(
    substr(
        trim($customer["full_name"] ?: "Customer"),
        0,
        1
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">
<title>My Profile | KaziHub Tanzania</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f7fb;
    color: #172033;
}
.navbar {
    background: #ffffff;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 15px rgba(0,0,0,0.06);
}
.logo {
    font-size: 22px;
    font-weight: 800;
    color: #172033;
}
.logo small {
    font-size: 18px;
}
.back-btn {
    text-decoration: none;
    color: #2563eb;
    font-weight: 700;
}
.container {
    width: 92%;
    max-width: 900px;
    margin: 40px auto;
}
.profile-card {
    background: white;
    border-radius: 24px;
    padding: 35px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.08);
}
.header {
    text-align: center;
    margin-bottom: 30px;
}
.header h1 {
    font-size: 30px;
    margin-bottom: 8px;
}
.header p {
    color: #64748b;
}
.avatar {
    width: 120px;
    height: 120px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #0f172a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
    font-weight: 800;
    overflow: hidden;
    border: 5px solid #eef4ff;
}
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-label {
    display: inline-block;
    background: #eff6ff;
    color: #2563eb;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 28px;
}
.photo-label:hover {
    background: #dbeafe;
}
input[type="file"] {
    display: none;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 700;
    color: #334155;
}
.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #dbe2ea;
    border-radius: 12px;
    font-size: 15px;
    outline: none;
    transition: 0.2s;
}
.form-group input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.save-btn {
    width: 100%;
    border: none;
    padding: 15px;
    border-radius: 12px;
    background: #2563eb;
    color: white;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    margin-top: 8px;
}
.save-btn:hover {
    background: #1d4ed8;
}
.message {
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-weight: 600;
}
.success {
    background: #dcfce7;
    color: #166534;
}
.error {
    background: #fee2e2;
    color: #991b1b;
}
@media (max-width: 600px) {
    .navbar {
        padding: 16px 5%;
    }
    .container {
        width: 94%;
        margin: 25px auto;
    }
    .profile-card {
        padding: 24px 18px;
        border-radius: 20px;
    }
    .header h1 {
        font-size: 25px;
    }
}
</style>
</head>
<body>
<nav class="navbar">
    <div class="logo">
        KaziHub <small>🇹🇿</small>
    </div>
    <a href="dashboard.php" class="back-btn">
        ← Dashboard
    </a>
</nav>
<div class="container">
    <div class="profile-card">
        <div class="header">
            <div class="avatar">
                <?php if ($has_profile_image): ?>
                    <img
                        src="<?= htmlspecialchars($profile_image) ?>"
                        alt="Profile Photo"
                    >
                <?php else: ?>
                    <?= htmlspecialchars($initial) ?>
                <?php endif; ?>
            </div>
            <h1>My Profile</h1>
            <p>Manage your KaziHub Tanzania account</p>
        </div>
        <?php if ($message !== ""): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form
            method="POST"
            enctype="multipart/form-data"
        >
            <div style="text-align:center;">
                <label for="profile_image" class="photo-label">
                    📷 Change Profile Photo
                </label>
                <input
                    type="file"
                    id="profile_image"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                >
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    value="<?= htmlspecialchars($customer["full_name"]) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input
                    type="text"
                    name="phone"
                    value="<?= htmlspecialchars($customer["phone"]) ?>"
                    required
                >
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($customer["email"]) ?>"
                    required
                >
            </div>
            <button type="submit" class="save-btn">
                💾 Save Changes
            </button>
        </form>
    </div>
</div>
</body>
</html>