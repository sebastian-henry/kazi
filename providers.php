<?php
session_start();
require_once "includes/config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION["user_id"];
/* Get all registered providers */
$sql = "SELECT
            u.id,
            u.full_name,
            u.phone,
            u.email,
            u.profile_image,
            pp.business_name,
            pp.profile_image AS provider_image,
            pp.location,
            pp.bio,
            pp.experience_years,
            pp.is_verified,
            pp.rating,
            pp.total_reviews
        FROM users u
        LEFT JOIN provider_profiles pp
            ON u.id = pp.provider_id
        WHERE u.role = 'provider'
        ORDER BY pp.is_verified DESC, u.full_name ASC";
$result = $conn->query($sql);
if (!$result) {
    die("Error loading providers: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Providers | KaziHub Tanzania</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f6f8fc;
    color: #172033;
}
/* =========================
   TOP BAR
========================= */
.topbar {
    background: #0f172a;
    padding: 18px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
}
.logo {
    color: white;
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.5px;
}
.logo span {
    color: #22c55e;
}
.back-btn {
    text-decoration: none;
    color: white;
    background: #2563eb;
    padding: 11px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: 0.25s ease;
}
.back-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
/* =========================
   MAIN CONTAINER
========================= */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 45px auto;
}
/* =========================
   HEADER
========================= */
.page-header {
    margin-bottom: 32px;
}
.page-header h1 {
    font-size: 34px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 9px;
}
.page-header p {
    color: #64748b;
    font-size: 15px;
    line-height: 1.6;
}
/* =========================
   PROVIDER COUNT
========================= */
.provider-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    padding: 8px 13px;
    background: #eaf2ff;
    color: #2563eb;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}
/* =========================
   PROVIDER GRID
========================= */
.providers-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
/* =========================
   PROVIDER CARD
========================= */
.provider-card {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 30px rgba(15,23,42,0.06);
    transition: 0.3s ease;
    position: relative;
    overflow: hidden;
}
.provider-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(15,23,42,0.12);
}
/* =========================
   VERIFIED BADGE
========================= */
.verified-badge {
    position: absolute;
    top: 17px;
    right: 17px;
    background: #ecfdf3;
    color: #16a34a;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
/* =========================
   PROVIDER TOP
========================= */
.provider-top {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
/* =========================
   AVATAR
========================= */
.avatar {
    width: 72px;
    height: 72px;
    min-width: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
    font-weight: 800;
    overflow: hidden;
    border: 4px solid #eef4ff;
}
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
/* =========================
   PROVIDER NAME
========================= */
.provider-name {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 5px;
}
.full-name {
    color: #64748b;
    font-size: 13px;
}
/* =========================
   DETAILS
========================= */
.provider-details {
    border-top: 1px solid #edf0f5;
    padding-top: 17px;
}
.detail {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #475569;
    font-size: 13px;
    margin-bottom: 10px;
}
.rating {
    color: #f59e0b;
    font-weight: 700;
}
/* =========================
   BIO
========================= */
.bio {
    margin-top: 15px;
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.55;
}
/* =========================
   BUTTONS
========================= */
.buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.view-btn {
    width: 100%;
    text-decoration: none;
    text-align: center;
    padding: 12px 14px;
    border-radius: 10px;
    background: #2563eb;
    color: white;
    font-size: 14px;
    font-weight: 700;
    transition: 0.25s ease;
}
.view-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
/* =========================
   EMPTY STATE
========================= */
.empty {
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 20px;
    padding: 65px 25px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(15,23,42,0.05);
}
.empty-icon {
    font-size: 50px;
    margin-bottom: 15px;
}
.empty h2 {
    color: #0f172a;
    margin-bottom: 10px;
}
.empty p {
    color: #64748b;
}
/* =========================
   MOBILE
========================= */
@media (max-width: 950px) {
    .providers-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 650px) {
    .topbar {
        padding: 15px 5%;
    }
    .logo {
        font-size: 20px;
    }
    .back-btn {
        padding: 9px 13px;
        font-size: 13px;
    }
    .container {
        width: 92%;
        margin: 30px auto;
    }
    .page-header h1 {
        font-size: 27px;
    }
    .providers-grid {
        grid-template-columns: 1fr;
    }
    .provider-card {
        padding: 21px;
    }
}
</style>
</head>
<body>
<!-- TOP BAR -->
<div class="topbar">
<div class="logo">
    KaziHub <span>🇹🇿</span>
</div>
<a href="dashboard.php" class="back-btn">
    ← Dashboard
</a>
</div>
<!-- MAIN -->
<div class="container">
<div class="page-header">
    <h1>Find Providers</h1>
    <p>
        Discover trusted service providers on KaziHub Tanzania
        and view their profiles, services and previous work.
    </p>
    <div class="provider-count">
        👥 <?= (int)$result->num_rows ?> Registered Provider<?= $result->num_rows != 1 ? 's' : '' ?>
    </div>
</div>
<?php if ($result->num_rows === 0): ?>
    <div class="empty">
        <div class="empty-icon">
            👥
        </div>
        <h2>No Providers Available</h2>
        <p>
            There are currently no registered providers on KaziHub Tanzania.
        </p>
    </div>
<?php else: ?>
    <div class="providers-grid">
        <?php while ($provider = $result->fetch_assoc()): ?>
            <?php
            $display_name = !empty($provider["business_name"])
                ? $provider["business_name"]
                : $provider["full_name"];
            $first_letter = strtoupper(
                mb_substr($display_name, 0, 1)
            );
            $rating = number_format(
                (float)($provider["rating"] ?? 0),
                1
            );
            $total_reviews = (int)($provider["total_reviews"] ?? 0);
            ?>
            <div class="provider-card">
                <?php if (!empty($provider["is_verified"])): ?>
                    <div class="verified-badge">
                        ✓ Verified
                    </div>
                <?php endif; ?>
                <!-- PROVIDER PROFILE HEADER -->
                <div class="provider-top">
                    <div class="avatar">
                        <?php if (!empty($provider["provider_image"])): ?>
                            <img
                                src="<?= htmlspecialchars($provider["provider_image"]) ?>"
                                alt="<?= htmlspecialchars($display_name) ?>"
                            >
                        <?php elseif (!empty($provider["profile_image"])): ?>
                            <img
                                src="<?= htmlspecialchars($provider["profile_image"]) ?>"
                                alt="<?= htmlspecialchars($display_name) ?>"
                            >
                        <?php else: ?>
                            <?= htmlspecialchars($first_letter) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="provider-name">
                            <?= htmlspecialchars($display_name) ?>
                        </div>
                        <?php if (
                            !empty($provider["business_name"]) &&
                            $provider["business_name"] !== $provider["full_name"]
                        ): ?>
                            <div class="full-name">
                                <?= htmlspecialchars($provider["full_name"]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- PROVIDER DETAILS -->
                <div class="provider-details">
                    <?php if (!empty($provider["location"])): ?>
                        <div class="detail">
                            📍
                            <span>
                                <?= htmlspecialchars($provider["location"]) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="detail rating">
                        ⭐ <?= $rating ?>
                        <span style="color:#64748b;font-weight:normal;">
                            (<?= $total_reviews ?> reviews)
                        </span>
                    </div>
                    <?php if (
                        isset($provider["experience_years"]) &&
                        (int)$provider["experience_years"] > 0
                    ): ?>
                        <div class="detail">
                            🏆
                            <span>
                                <?= (int)$provider["experience_years"] ?>
                                years experience
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($provider["phone"])): ?>
                        <div class="detail">
                            📞
                            <span>
                                <?= htmlspecialchars($provider["phone"]) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- BIO -->
                <?php if (!empty($provider["bio"])): ?>
                    <div class="bio">
                        <?= htmlspecialchars(
                            mb_substr($provider["bio"], 0, 110)
                        ) ?>
                        <?php if (mb_strlen($provider["bio"]) > 110): ?>
                            ...
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <!-- VIEW PROVIDER -->
                <div class="buttons">
                    <a
                        href="provider-profile.php?provider_id=<?= (int)$provider["id"] ?>"
                        class="view-btn"
                    >
                        👤 View Provider
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>