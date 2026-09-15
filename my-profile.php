<?php
session_start();
require_once "includes/config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "provider") {
    header("Location: login.php");
    exit;
}
$provider_id = (int)$_SESSION["user_id"];
$message = "";
$error = "";
/* =========================
   GET PROVIDER DATA
========================= */
$stmt = $conn->prepare("
    SELECT
        u.full_name,
        u.phone,
        u.email,
        p.business_name,
        p.profile_image,
        p.location,
        p.address,
        p.bio,
        p.experience_years,
        p.whatsapp,
        p.rating,
        p.total_reviews
    FROM users u
    LEFT JOIN provider_profiles p
        ON u.id = p.provider_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();
if (!$provider) {
    die("Provider profile not found.");
}
/* =========================
   SAVE CHANGES
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $business_name = trim($_POST["business_name"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $bio = trim($_POST["bio"] ?? "");
    $experience_years = (int)($_POST["experience_years"] ?? 0);
    $whatsapp = trim($_POST["whatsapp"] ?? "");
    if ($full_name === "" || $phone === "" || $email === "") {
        $error = "Please fill in Full Name, Phone and Email.";
    } else {
        /* =========================
           UPDATE USERS
        ========================= */
        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, phone = ?, email = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssi",
            $full_name,
            $phone,
            $email,
            $provider_id
        );
        $stmt->execute();
        /* =========================
           CREATE PROFILE IF MISSING
        ========================= */
        $check = $conn->prepare("
            SELECT provider_id
            FROM provider_profiles
            WHERE provider_id = ?
            LIMIT 1
        ");
        $check->bind_param("i", $provider_id);
        $check->execute();
        $profile_exists = $check->get_result()->num_rows > 0;
        if (!$profile_exists) {
            $stmt = $conn->prepare("
                INSERT INTO provider_profiles
                (
                    provider_id,
                    business_name,
                    location,
                    address,
                    bio,
                    experience_years,
                    whatsapp
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "issssis",
                $provider_id,
                $business_name,
                $location,
                $address,
                $bio,
                $experience_years,
                $whatsapp
            );
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("
                UPDATE provider_profiles
                SET
                    business_name = ?,
                    location = ?,
                    address = ?,
                    bio = ?,
                    experience_years = ?,
                    whatsapp = ?
                WHERE provider_id = ?
            ");
            $stmt->bind_param(
                "ssssisi",
                $business_name,
                $location,
                $address,
                $bio,
                $experience_years,
                $whatsapp,
                $provider_id
            );
            $stmt->execute();
        }
        /* =========================
           PROFILE IMAGE UPLOAD
        ========================= */
        if (
            isset($_FILES["profile_image"]) &&
            $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK) {
                $error = "Failed to upload the image.";
            } else {
                $max_size = 5 * 1024 * 1024;
                if ($_FILES["profile_image"]["size"] > $max_size) {
                    $error = "Image must be less than 5MB.";
                } else {
                    $tmp_name = $_FILES["profile_image"]["tmp_name"];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);
                    $allowed = [
                        "image/jpeg" => "jpg",
                        "image/png"  => "png",
                        "image/webp" => "webp"
                    ];
                    if (!isset($allowed[$mime])) {
                        $error = "Only JPG, PNG or WEBP images are allowed.";
                    } else {
                        /* CREATE UPLOAD FOLDER */
                        $upload_dir = __DIR__ . "/uploads/profile_images/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        $extension = $allowed[$mime];
                        $filename =
                            "provider_" .
                            $provider_id .
                            "_" .
                            time() .
                            "_" .
                            bin2hex(random_bytes(4)) .
                            "." .
                            $extension;
                        $destination = $upload_dir . $filename;
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $image_path =
                                "uploads/profile_images/" . $filename;
                            /* GET OLD IMAGE */
                            $old_stmt = $conn->prepare("
                                SELECT profile_image
                                FROM provider_profiles
                                WHERE provider_id = ?
                                LIMIT 1
                            ");
                            $old_stmt->bind_param("i", $provider_id);
                            $old_stmt->execute();
                            $old_result = $old_stmt->get_result();
                            $old_data = $old_result->fetch_assoc();
                            $old_image = $old_data["profile_image"] ?? "";
                            /* SAVE NEW IMAGE */
                            $image_stmt = $conn->prepare("
                                UPDATE provider_profiles
                                SET profile_image = ?
                                WHERE provider_id = ?
                            ");
                            $image_stmt->bind_param(
                                "si",
                                $image_path,
                                $provider_id
                            );
                            $image_stmt->execute();
                            /* DELETE OLD IMAGE */
                            if (
                                !empty($old_image) &&
                                strpos($old_image, "uploads/profile_images/") === 0
                            ) {
                                $old_file = __DIR__ . "/" . $old_image;
                                if (
                                    file_exists($old_file) &&
                                    $old_image !== $image_path
                                ) {
                                    @unlink($old_file);
                                }
                            }
                        } else {
                            $error = "Could not save the uploaded image.";
                        }
                    }
                }
            }
        }
        if ($error === "") {
            $_SESSION["full_name"] = $full_name;
            $message = "Profile updated successfully.";
            /* Reload data */
            $stmt = $conn->prepare("
                SELECT
                    u.full_name,
                    u.phone,
                    u.email,
                    p.business_name,
                    p.profile_image,
                    p.location,
                    p.address,
                    p.bio,
                    p.experience_years,
                    p.whatsapp,
                    p.rating,
                    p.total_reviews
                FROM users u
                LEFT JOIN provider_profiles p
                    ON u.id = p.provider_id
                WHERE u.id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $provider_id);
            $stmt->execute();
            $provider = $stmt->get_result()->fetch_assoc();
        }
    }
}
$profile_image = $provider["profile_image"] ?? "";
$has_image = false;
if (
    !empty($profile_image) &&
    strpos($profile_image, "uploads/profile_images/") === 0 &&
    file_exists(__DIR__ . "/" . $profile_image)
) {
    $has_image = true;
}
$display_name =
    !empty($provider["business_name"])
        ? $provider["business_name"]
        : $provider["full_name"];
$initial = strtoupper(substr($display_name, 0, 1));
$rating = number_format(
    (float)($provider["rating"] ?? 0),
    1
);
$total_reviews = (int)($provider["total_reviews"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">
<title>My Profile - KaziHub Tanzania</title>
<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f7fb;
    color: #172033;
}
.topbar {
    background: linear-gradient(135deg, #0f172a, #1d4ed8);
    color: white;
    padding: 18px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    font-size: 22px;
    font-weight: 800;
}
.logo small {
    font-size: 18px;
}
.back-btn {
    color: white;
    text-decoration: none;
    background: rgba(255,255,255,0.15);
    padding: 10px 16px;
    border-radius: 10px;
}
.container {
    width: min(1000px, 92%);
    margin: 35px auto;
}
.card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 12px 35px rgba(15,23,42,0.08);
    margin-bottom: 25px;
}
.profile-header {
    text-align: center;
}
.avatar {
    width: 125px;
    height: 125px;
    margin: 0 auto 15px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #2563eb, #0f172a);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 800;
    border: 5px solid #e8eefc;
}
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-header h1 {
    margin: 8px 0;
}
.profile-header p {
    color: #64748b;
}
.upload-box {
    margin-top: 25px;
    padding: 22px;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 15px;
    text-align: center;
}
.upload-box strong {
    display: block;
    margin-bottom: 8px;
}
input[type="file"] {
    margin-top: 12px;
    width: 100%;
    padding: 12px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
}
.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}
.field {
    display: flex;
    flex-direction: column;
}
.field.full {
    grid-column: 1 / -1;
}
label {
    font-weight: 700;
    margin-bottom: 7px;
}
input,
textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    font-size: 15px;
    outline: none;
}
input:focus,
textarea:focus {
    border-color: #2563eb;
}
textarea {
    min-height: 120px;
    resize: vertical;
}
.save-btn {
    width: 100%;
    margin-top: 25px;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
}
.save-btn:hover {
    opacity: 0.92;
}
.success {
    background: #dcfce7;
    color: #166534;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 700;
}
.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 700;
}
.stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 25px;
}
.stat {
    background: #f8fafc;
    padding: 20px;
    border-radius: 14px;
    text-align: center;
}
.stat strong {
    display: block;
    font-size: 25px;
    color: #2563eb;
}
@media (max-width: 700px) {
    .grid {
        grid-template-columns: 1fr;
    }
    .field.full {
        grid-column: auto;
    }
    .card {
        padding: 20px;
    }
    .topbar {
        padding: 15px 4%;
    }
    .stats {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<header class="topbar">
    <div class="logo">
        KaziHub <small>🇹🇿</small>
    </div>
    <a href="provider-dashboard.php" class="back-btn">
        ← Dashboard
    </a>
</header>
<div class="container">
    <?php if ($message): ?>
        <div class="success">
            ✅ <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <!-- PROFILE PHOTO -->
    <div class="card">
        <div class="profile-header">
            <div class="avatar">
                <?php if ($has_image): ?>
                    <img
                        src="<?= htmlspecialchars($profile_image) ?>"
                        alt="Profile Photo"
                    >
                <?php else: ?>
                    <?= htmlspecialchars($initial) ?>
                <?php endif; ?>
            </div>
            <h1>
                <?= htmlspecialchars($display_name) ?>
            </h1>
            <p>
                Provider Profile
            </p>
        </div>
        <form
            method="POST"
            enctype="multipart/form-data"
        >
            <div class="upload-box">
                <strong>
                    📷 Change Profile Photo
                </strong>
                <span>
                    Choose a professional photo for your provider profile.
                </span>
                <input
                    type="file"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                >
                <small>
                    JPG, PNG or WEBP • Maximum 5MB
                </small>
            </div>
    </div>
    <!-- PERSONAL INFORMATION -->
    <div class="card">
        <h2>👤 Personal Information</h2>
        <div class="grid">
            <div class="field">
                <label>Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    value="<?= htmlspecialchars($provider["full_name"] ?? "") ?>"
                    required
                >
            </div>
            <div class="field">
                <label>Phone</label>
                <input
                    type="text"
                    name="phone"
                    value="<?= htmlspecialchars($provider["phone"] ?? "") ?>"
                    required
                >
            </div>
            <div class="field">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($provider["email"] ?? "") ?>"
                    required
                >
            </div>
            <div class="field">
                <label>Business Name</label>
                <input
                    type="text"
                    name="business_name"
                    value="<?= htmlspecialchars($provider["business_name"] ?? "") ?>"
                    placeholder="Example: Nicodemo Plumbing Services"
                >
            </div>
            <div class="field">
                <label>Location</label>
                <input
                    type="text"
                    name="location"
                    value="<?= htmlspecialchars($provider["location"] ?? "") ?>"
                    placeholder="Example: Dar es Salaam"
                >
            </div>
            <div class="field">
                <label>Address</label>
                <input
                    type="text"
                    name="address"
                    value="<?= htmlspecialchars($provider["address"] ?? "") ?>"
                    placeholder="Example: Sinza, Dar es Salaam"
                >
            </div>
            <div class="field">
                <label>Experience Years</label>
                <input
                    type="number"
                    name="experience_years"
                    min="0"
                    value="<?= (int)($provider["experience_years"] ?? 0) ?>"
                >
            </div>
            <div class="field">
                <label>WhatsApp</label>
                <input
                    type="text"
                    name="whatsapp"
                    value="<?= htmlspecialchars($provider["whatsapp"] ?? "") ?>"
                    placeholder="Example: 0712345678"
                >
            </div>
            <div class="field full">
                <label>About Your Business</label>
                <textarea
                    name="bio"
                    placeholder="Tell customers about your services..."
                ><?= htmlspecialchars($provider["bio"] ?? "") ?></textarea>
            </div>
        </div>
        <button
            type="submit"
            class="save-btn"
        >
            💾 Save Changes
        </button>
        </form>
    </div>
    <!-- STATS -->
    <div class="card">
        <h2>⭐ Your Performance</h2>
        <div class="stats">
            <div class="stat">
                <strong>
                    <?= $rating ?>
                </strong>
                Rating
            </div>
            <div class="stat">
                <strong>
                    <?= $total_reviews ?>
                </strong>
                Reviews
            </div>
        </div>
    </div>
</div>
</body>
</html>