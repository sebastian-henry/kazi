<?php
session_start();
require_once "includes/config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$provider_id = intval($_GET["provider_id"] ?? 0);
if ($provider_id <= 0) {
    header("Location: find-services.php");
    exit;
}
/* =========================
   GET PROVIDER INFORMATION
========================= */
$sql = "SELECT
            u.id,
            u.full_name,
            u.phone,
            u.email,
            u.profile_image,
            pp.business_name,
            pp.profile_image AS provider_image,
            pp.location,
            pp.address,
            pp.bio,
            pp.experience_years,
            pp.whatsapp,
            pp.is_verified,
            pp.rating,
            pp.total_reviews
        FROM users u
        LEFT JOIN provider_profiles pp
            ON u.id = pp.provider_id
        WHERE u.id = ?
        AND u.role = 'provider'
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();
$stmt->close();
if (!$provider) {
    die("Provider not found.");
}
/* =========================
   GET PROVIDER SERVICES
========================= */
$sql = "SELECT
            ps.id,
            ps.price,
            ps.price_type,
            ps.service_description,
            ps.location AS service_location,
            ps.availability,
            s.service_name,
            s.category
        FROM provider_services ps
        INNER JOIN services s
            ON ps.service_id = s.id
        WHERE ps.provider_id = ?
        ORDER BY ps.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
$stmt->close();
/* =========================
   GET PROVIDER PORTFOLIO
========================= */
$sql = "SELECT
            id,
            image,
            title,
            description,
            created_at
        FROM provider_portfolio
        WHERE provider_id = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$portfolio = [];
while ($row = $result->fetch_assoc()) {
    $portfolio[] = $row;
}
$stmt->close();
/* =========================
   PROVIDER DISPLAY DATA
========================= */
$display_name = $provider["business_name"] ?: $provider["full_name"];
$rating = number_format(
    (float)$provider["rating"],
    1
);
$initial = strtoupper(
    mb_substr(
        trim($display_name),
        0,
        1,
        "UTF-8"
    )
);
/*
   Use provider_profiles image first.
   If it does not exist, use users image.
*/
$profile_image = "";
if (!empty($provider["provider_image"])) {
    $profile_image = $provider["provider_image"];
} elseif (!empty($provider["profile_image"])) {
    $profile_image = $provider["profile_image"];
}
/*
   Make sure image path is safe.
*/
$has_profile_image = false;
if (!empty($profile_image)) {
    if (
        strpos($profile_image, "uploads/") === 0 &&
        file_exists(__DIR__ . "/" . $profile_image)
    ) {
        $has_profile_image = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>
<title>
    <?= htmlspecialchars($display_name) ?> -
    KaziHub Tanzania
</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #172033;
}
/* =========================
   TOP BAR
========================= */
.topbar {
    background: #0f172a;
    color: white;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
}
.logo {
    font-size: 22px;
    font-weight: bold;
}
.logo span {
    color: #22c55e;
}
.back-btn {
    text-decoration: none;
    color: white;
    background: #2563eb;
    padding: 10px 17px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: bold;
    transition: 0.3s;
}
.back-btn:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
}
/* =========================
   CONTAINER
========================= */
.container {
    width: 90%;
    max-width: 1100px;
    margin: 35px auto;
}
/* =========================
   PROFILE CARD
========================= */
.profile-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow:
        0 10px 30px rgba(0,0,0,0.08);
    display: flex;
    gap: 25px;
    align-items: center;
    margin-bottom: 30px;
}
/* =========================
   PROVIDER AVATAR
========================= */
.profile-image {
    width: 115px;
    height: 115px;
    border-radius: 50%;
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #0f172a
        );
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 42px;
    font-weight: bold;
    flex-shrink: 0;
    box-shadow:
        0 8px 20px rgba(37,99,235,0.25);
    overflow: hidden;
}
/* REAL PROVIDER PHOTO */
.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* PROVIDER INITIAL */
.profile-initial {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.profile-info {
    flex: 1;
}
.profile-info h1 {
    font-size: 30px;
    margin-bottom: 8px;
}
.verified {
    color: #16a34a;
    font-weight: bold;
}
.rating {
    margin: 10px 0;
    color: #f59e0b;
    font-weight: bold;
}
.location {
    color: #64748b;
    margin-top: 8px;
}
/* =========================
   SECTIONS
========================= */
.section {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow:
        0 8px 25px rgba(0,0,0,0.06);
}
.section-header {
    margin-bottom: 22px;
}
.section-header h2 {
    font-size: 24px;
    margin-bottom: 6px;
}
.section-header p {
    color: #64748b;
    font-size: 14px;
}
.bio {
    line-height: 1.8;
    color: #475569;
}
/* =========================
   SERVICES
========================= */
.service-card {
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    transition: 0.3s;
}
.service-card:hover {
    border-color: #bfdbfe;
    transform: translateY(-2px);
    box-shadow:
        0 8px 20px rgba(0,0,0,0.05);
}
.service-card h3 {
    margin-bottom: 7px;
    font-size: 19px;
}
.category {
    color: #2563eb;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 10px;
}
.description {
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 12px;
}
.service-info {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 12px;
    font-size: 14px;
}
.available {
    color: #16a34a;
    font-weight: bold;
}
.busy {
    color: #f59e0b;
    font-weight: bold;
}
.unavailable {
    color: #dc2626;
    font-weight: bold;
}
/* =========================
   PORTFOLIO
========================= */
.portfolio-grid {
    display: grid;
    grid-template-columns:
        repeat(3, 1fr);
    gap: 20px;
}
.portfolio-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: 0.3s;
}
.portfolio-card:hover {
    transform: translateY(-5px);
    box-shadow:
        0 15px 30px rgba(0,0,0,0.10);
}
.portfolio-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    background: #e2e8f0;
    cursor: pointer;
}
.portfolio-content {
    padding: 17px;
}
.portfolio-content h3 {
    font-size: 18px;
    margin-bottom: 8px;
}
.portfolio-content p {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}
.portfolio-date {
    margin-top: 12px;
    color: #94a3b8;
    font-size: 12px;
}
/* =========================
   PORTFOLIO BUTTONS
========================= */
.portfolio-actions {
    display: grid;
    grid-template-columns:
        1fr 1fr;
    gap: 10px;
    margin-top: 17px;
}
.portfolio-btn {
    text-decoration: none;
    text-align: center;
    padding: 11px 8px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: bold;
    transition: 0.3s;
}
.chat-btn {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
.chat-btn:hover {
    background: #dbeafe;
}
.book-btn {
    background: #2563eb;
    color: white;
}
.book-btn:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
}
/* =========================
   EMPTY PORTFOLIO
========================= */
.empty-portfolio {
    text-align: center;
    padding: 45px 20px;
    border: 2px dashed #dbeafe;
    border-radius: 15px;
    color: #64748b;
}
.empty-portfolio-icon {
    font-size: 45px;
    margin-bottom: 12px;
}
/* =========================
   IMAGE MODAL
========================= */
.image-modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background:
        rgba(15,23,42,0.92);
    justify-content: center;
    align-items: center;
    padding: 25px;
}
.image-modal img {
    max-width: 90%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 12px;
    box-shadow:
        0 20px 60px rgba(0,0,0,0.5);
}
.close-modal {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    cursor: pointer;
    font-weight: bold;
}
/* =========================
   RESPONSIVE
========================= */
@media (max-width: 850px) {
    .portfolio-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }
}
@media (max-width: 700px) {
    .profile-card {
        flex-direction: column;
        text-align: center;
    }
    .profile-info h1 {
        font-size: 24px;
    }
    .topbar {
        padding: 15px 5%;
    }
    .container {
        width: 94%;
    }
    .section {
        padding: 22px;
    }
    .portfolio-grid {
        grid-template-columns: 1fr;
    }
    .portfolio-image {
        height: 230px;
    }
}
</style>
</head>
<body>
<!-- =========================
     TOP BAR
========================= -->
<div class="topbar">
    <div class="logo">
        KaziHub <span>🇹🇿</span>
    </div>
    <a
        href="find-services.php"
        class="back-btn"
    >
        ← Back
    </a>
</div>
<div class="container">
<!-- =========================
     PROVIDER PROFILE
========================= -->
<div class="profile-card">
    <!-- PROVIDER PHOTO / INITIAL -->
    <div class="profile-image">
        <?php if ($has_profile_image): ?>
            <img
                src="<?= htmlspecialchars($profile_image) ?>"
                alt="<?= htmlspecialchars($display_name) ?>"
            >
        <?php else: ?>
            <div class="profile-initial">
                <?= htmlspecialchars($initial) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="profile-info">
        <h1>
            <?= htmlspecialchars($display_name) ?>
            <?php if ($provider["is_verified"]): ?>
                <span class="verified">
                    ✓
                </span>
            <?php endif; ?>
        </h1>
        <?php if ($provider["is_verified"]): ?>
            <div class="verified">
                ✓ Verified Provider
            </div>
        <?php endif; ?>
        <div class="rating">
            ⭐ <?= $rating ?>
            ·
            <?= intval($provider["total_reviews"]) ?>
            reviews
        </div>
        <?php if (!empty($provider["location"])): ?>
            <div class="location">
                📍
                <?= htmlspecialchars($provider["location"]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- =========================
     ABOUT PROVIDER
========================= -->
<div class="section">
    <div class="section-header">
        <h2>
            About Provider
        </h2>
    </div>
    <div class="bio">
        <?php if (!empty($provider["bio"])): ?>
            <?= nl2br(
                htmlspecialchars(
                    $provider["bio"]
                )
            ) ?>
        <?php else: ?>
            This provider has not added
            a description yet.
        <?php endif; ?>
    </div>
    <?php if ($provider["experience_years"] > 0): ?>
        <p style="margin-top:15px;">
            🏆
            <?= intval(
                $provider["experience_years"]
            ) ?>
            years experience
        </p>
    <?php endif; ?>
</div>
<!-- =========================
     SERVICES
========================= -->
<div class="section">
    <div class="section-header">
        <h2>
            Services Offered
        </h2>
        <p>
            Services currently offered by this provider.
        </p>
    </div>
    <?php if (empty($services)): ?>
        <p>
            No services added yet.
        </p>
    <?php else: ?>
        <?php foreach ($services as $service): ?>
            <div class="service-card">
                <h3>
                    <?= htmlspecialchars(
                        $service["service_name"]
                    ) ?>
                </h3>
                <div class="category">
                    <?= htmlspecialchars(
                        $service["category"]
                    ) ?>
                </div>
                <div class="description">
                    <?= nl2br(
                        htmlspecialchars(
                            $service["service_description"]
                            ?: "No description provided."
                        )
                    ) ?>
                </div>
                <div class="service-info">
                    <?php if ($service["price"] !== null): ?>
                        <span>
                            💰
                            TSh
                            <?= number_format(
                                $service["price"]
                            ) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($service["price_type"])): ?>
                        <span>
                            📌
                            <?= htmlspecialchars(
                                $service["price_type"]
                            ) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($service["service_location"])): ?>
                        <span>
                            📍
                            <?= htmlspecialchars(
                                $service["service_location"]
                            ) ?>
                        </span>
                    <?php endif; ?>
                    <?php
                    $availability =
                        $service["availability"];
                    ?>
                    <?php if ($availability === "available"): ?>
                        <span class="available">
                            🟢 Available
                        </span>
                    <?php elseif ($availability === "busy"): ?>
                        <span class="busy">
                            🟡 Busy
                        </span>
                    <?php else: ?>
                        <span class="unavailable">
                            🔴 Unavailable
                        </span>
                    <?php endif; ?>
                </div>
                <div style="margin-top:18px;">
                    <a
                        href="book-service.php?provider_service_id=<?= (int)$service["id"] ?>"
                        class="portfolio-btn book-btn"
                        style="display:inline-block; width:100%;"
                    >
                        📅 Book This Service
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<!-- =========================
     PROVIDER PORTFOLIO
========================= -->
<div class="section">
    <div class="section-header">
        <h2>
            📸 Our Work
        </h2>
        <p>
            Take a look at some of the work
            completed by this provider.
        </p>
    </div>
    <?php if (empty($portfolio)): ?>
        <div class="empty-portfolio">
            <div class="empty-portfolio-icon">
                📸
            </div>
            <h3>
                No work uploaded yet
            </h3>
            <p style="margin-top:8px;">
                This provider has not added
                any work to their portfolio yet.
            </p>
        </div>
    <?php else: ?>
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $work): ?>
                <div class="portfolio-card">
                    <img
                        src="uploads/provider_work/<?= htmlspecialchars($work["image"]) ?>"
                        alt="<?= htmlspecialchars($work["title"]) ?>"
                        class="portfolio-image"
                        onclick="openImage(
                            'uploads/provider_work/<?= htmlspecialchars($work["image"], ENT_QUOTES) ?>'
                        )"
                    >
                    <div class="portfolio-content">
                        <h3>
                            <?= htmlspecialchars(
                                $work["title"]
                            ) ?>
                        </h3>
                        <?php if (!empty($work["description"])): ?>
                            <p>
                                <?= nl2br(
                                    htmlspecialchars(
                                        $work["description"]
                                    )
                                ) ?>
                            </p>
                        <?php endif; ?>
                        <div class="portfolio-date">
                            📅
                            <?= date(
                                "d M Y",
                                strtotime(
                                    $work["created_at"]
                                )
                            ) ?>
                        </div>
                        <div class="portfolio-actions">
                            <a
                                href="chat.php?user_id=<?= $provider_id ?>"
                                class="portfolio-btn chat-btn"
                            >
                                💬 Chat
                            </a>
                            <?php if (!empty($services)): ?>
                                <a
                                    href="book-service.php?provider_service_id=<?= (int)$services[0]["id"] ?>"
                                    class="portfolio-btn book-btn"
                                >
                                    📅 Book
                                </a>
                            <?php else: ?>
                                <span
                                    class="portfolio-btn"
                                    style="
                                        background:#f1f5f9;
                                        color:#94a3b8;
                                        cursor:not-allowed;
                                    "
                                >
                                    📅 No Service
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>
<!-- =========================
     IMAGE MODAL
========================= -->
<div
    class="image-modal"
    id="imageModal"
    onclick="closeImage()"
>
    <span
        class="close-modal"
        onclick="closeImage()"
    >
        &times;
    </span>
    <img
        id="modalImage"
        src=""
        alt="Work Preview"
        onclick="event.stopPropagation()"
    >
</div>
<script>
function openImage(image) {
    const modal =
        document.getElementById("imageModal");
    const modalImage =
        document.getElementById("modalImage");
    modalImage.src = image;
    modal.style.display = "flex";
}
function closeImage() {
    const modal =
        document.getElementById("imageModal");
    modal.style.display = "none";
    document.getElementById("modalImage").src = "";
}
document.addEventListener(
    "keydown",
    function(event) {
        if (event.key === "Escape") {
            closeImage();
        }
    }
);
</script>
</body>
</html>