<?php
session_start();
require_once "includes/config.php";
/* =========================================
   AUTHENTICATION
========================================= */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION["role"] ?? "") !== "provider") {
    header("Location: dashboard.php");
    exit;
}
$provider_id = (int) $_SESSION["user_id"];
$full_name = $_SESSION["full_name"] ?? "Provider";
/* =========================================
   UNREAD NOTIFICATIONS
========================================= */
$unread_notifications = 0;
$notification_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
if ($notification_stmt) {
    $notification_stmt->bind_param("i", $provider_id);
    $notification_stmt->execute();
    $notification_result = $notification_stmt->get_result();
    $notification_data = $notification_result->fetch_assoc();
    $unread_notifications = (int)($notification_data["unread_count"] ?? 0);
    $notification_stmt->close();
}
/* =========================================
   PROVIDER PROFILE
========================================= */
$profile = [];
$profile_sql = "
    SELECT
        u.full_name,
        u.phone,
        u.email,
        u.profile_image AS user_image,
        p.business_name,
        p.profile_image,
        p.location,
        p.address,
        p.bio,
        p.experience_years,
        p.whatsapp,
        p.is_verified,
        p.rating,
        p.total_reviews
    FROM users u
    LEFT JOIN provider_profiles p
        ON u.id = p.provider_id
    WHERE u.id = ?
    LIMIT 1
";
$profile_stmt = $conn->prepare($profile_sql);
if ($profile_stmt) {
    $profile_stmt->bind_param("i", $provider_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    $profile = $profile_result->fetch_assoc() ?? [];
    $profile_stmt->close();
}
/* =========================================
   PROVIDER SERVICES
========================================= */
$services = [];
$services_sql = "
    SELECT
        ps.id,
        ps.price,
        ps.price_type,
        ps.service_description,
        ps.availability,
        ps.location AS provider_location,
        s.service_name,
        s.category,
        s.location AS service_location
    FROM provider_services ps
    INNER JOIN services s
        ON ps.service_id = s.id
    WHERE ps.provider_id = ?
    ORDER BY ps.created_at DESC
";
$services_stmt = $conn->prepare($services_sql);
if ($services_stmt) {
    $services_stmt->bind_param("i", $provider_id);
    $services_stmt->execute();
    $services_result = $services_stmt->get_result();
    while ($service = $services_result->fetch_assoc()) {
        $services[] = $service;
    }
    $services_stmt->close();
}
/* =========================================
   SERVICE COUNTS
========================================= */
$services_count = count($services);
$available_count = 0;
$busy_count = 0;
$unavailable_count = 0;
foreach ($services as $service) {
    $availability = strtolower(
        trim($service["availability"] ?? "")
    );
    if ($availability === "available") {
        $available_count++;
    } elseif ($availability === "busy") {
        $busy_count++;
    } elseif ($availability === "unavailable") {
        $unavailable_count++;
    }
}
/* =========================================
   PROFILE DATA
========================================= */
$business_name = $profile["business_name"] ?? "";
$location = $profile["location"] ?? "";
if ($location === "") {
    $location = "Location not set";
}
$rating = $profile["rating"] ?? "0.00";
$total_reviews = (int)($profile["total_reviews"] ?? 0);
$is_verified = (int)($profile["is_verified"] ?? 0);
/* =========================================
   AVATAR
========================================= */
$avatar_letter = strtoupper(
    substr(trim($full_name), 0, 1)
);
if ($avatar_letter === "") {
    $avatar_letter = "P";
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
    Provider Dashboard | KaziHub Tanzania
</title>
<link
    rel="stylesheet"
    href="css/style.css"
>
<style>
    * {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f5f7fb;
        color: #1f2937;
    }
    .provider-container {
        max-width: 1200px;
        margin: auto;
        padding: 30px 20px;
    }
    /* =========================================
       TOP
    ========================================= */
    .dashboard-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    .welcome h1 {
        margin: 0 0 8px;
        font-size: 30px;
    }
    .welcome p {
        margin: 0;
        color: #64748b;
    }
    .top-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    /* =========================================
       BUTTONS
    ========================================= */
    .btn {
        text-decoration: none;
        border: none;
        cursor: pointer;
        padding: 12px 18px;
        border-radius: 10px;
        font-weight: bold;
        display: inline-block;
        transition: 0.25s;
    }
    .btn:hover {
        transform: translateY(-2px);
    }
    .btn-primary {
        background: #2563eb;
        color: white;
    }
    .btn-dark {
        background: #0f172a;
        color: white;
    }
    .btn-light {
        background: white;
        color: #1e293b;
        border: 1px solid #e2e8f0;
    }
    /* =========================================
       QUICK ACTIONS
    ========================================= */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }
    .action-btn {
        text-decoration: none;
        background: white;
        color: #0f172a;
        padding: 17px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        font-weight: bold;
        text-align: center;
        box-shadow: 0 5px 18px rgba(0,0,0,0.05);
        transition: 0.25s;
    }
    .action-btn:hover {
        transform: translateY(-3px);
        border-color: #2563eb;
        color: #2563eb;
    }
    /* =========================================
       NOTIFICATION
    ========================================= */
    .notification-btn {
        position: relative;
        width: 46px;
        height: 46px;
        background: white;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 21px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: 0.25s;
    }
    .notification-btn:hover {
        transform: translateY(-2px);
    }
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        background: #ef4444;
        color: white;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        border: 2px solid #f5f7fb;
    }
    /* =========================================
       PROFILE
    ========================================= */
    .profile-card {
        background: linear-gradient(
            135deg,
            #0f172a,
            #2563eb
        );
        color: white;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 25px;
        box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15);
    }
    .profile-main {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 30px;
        font-weight: bold;
        border: 2px solid rgba(255,255,255,0.25);
    }
    .profile-info h2 {
        margin: 0 0 7px;
    }
    .profile-info p {
        margin: 5px 0;
        opacity: 0.9;
    }
    .verified {
        display: inline-block;
        margin-top: 8px;
        padding: 5px 10px;
        background: rgba(255,255,255,0.18);
        border-radius: 20px;
        font-size: 13px;
    }
    /* =========================================
       STATS
    ========================================= */
    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 22px;
        border-radius: 15px;
        box-shadow: 0 5px 18px rgba(0,0,0,0.06);
    }
    .stat-card h3 {
        margin: 0;
        font-size: 28px;
    }
    .stat-card p {
        margin: 8px 0 0;
        color: #64748b;
    }
    /* =========================================
       SERVICES
    ========================================= */
    .section-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .section-title h2 {
        margin: 0;
    }
    .section-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .service-card {
        background: white;
        padding: 22px;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(0,0,0,0.06);
        transition: 0.3s;
    }
    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.10);
    }
    .service-card h3 {
        margin-top: 8px;
        margin-bottom: 8px;
    }
    .category {
        color: #2563eb;
        font-size: 14px;
        font-weight: bold;
    }
    .service-description {
        color: #64748b;
        line-height: 1.5;
        min-height: 45px;
    }
    .service-info {
        margin: 12px 0;
        line-height: 1.7;
    }
    .price {
        font-size: 18px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 8px;
    }
    .availability {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        margin-top: 8px;
    }
    .available {
        background: #dcfce7;
        color: #166534;
    }
    .busy {
        background: #fef3c7;
        color: #92400e;
    }
    .unavailable {
        background: #fee2e2;
        color: #991b1b;
    }
    /* =========================================
       EMPTY
    ========================================= */
    .empty-state {
        background: white;
        text-align: center;
        padding: 50px 20px;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(0,0,0,0.06);
    }
    .empty-state h3 {
        margin-bottom: 10px;
    }
    .empty-state p {
        color: #64748b;
        margin-bottom: 20px;
    }
    /* =========================================
       RESPONSIVE
    ========================================= */
    @media (max-width: 900px) {
        .stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        .provider-container {
            padding: 20px 15px;
        }
        .stats {
            grid-template-columns: 1fr;
        }
        .services-grid {
            grid-template-columns: 1fr;
        }
        .quick-actions {
            grid-template-columns: 1fr;
        }
        .welcome h1 {
            font-size: 24px;
        }
        .profile-card {
            padding: 22px;
        }
        .top-actions {
            width: 100%;
        }
        .notification-btn {
            width: 44px;
            height: 44px;
        }
    }
</style>
</head>
<body>
<div class="provider-container">
<!-- =========================================
     TOP
========================================= -->
<div class="dashboard-top">
    <div class="welcome">
        <h1>
            Provider Dashboard 👋
        </h1>
        <p>
            Karibu,
            <?= htmlspecialchars($full_name) ?>
        </p>
    </div>
    <div class="top-actions">
        <a
            href="notifications.php"
            class="notification-btn"
            title="Notifications"
        >
            🔔
            <?php if ($unread_notifications > 0): ?>
                <span class="notification-badge">
                    <?= $unread_notifications > 99
                        ? "99+"
                        : $unread_notifications ?>
                </span>
            <?php endif; ?>
        </a>
        <a
            href="dashboard.php"
            class="btn btn-light"
        >
            Dashboard
        </a>
        <a
            href="logout.php"
            class="btn btn-dark"
        >
            Logout
        </a>
    </div>
</div>
<!-- =========================================
     PROFILE
========================================= -->
<div class="profile-card">
    <div class="profile-main">
        <div class="profile-avatar">
            <?= htmlspecialchars($avatar_letter) ?>
        </div>
        <div class="profile-info">
            <h2>
                <?= htmlspecialchars(
                    $business_name !== ""
                        ? $business_name
                        : $full_name
                ) ?>
            </h2>
            <p>
                👤 <?= htmlspecialchars($full_name) ?>
            </p>
            <p>
                📍 <?= htmlspecialchars($location) ?>
            </p>
            <p>
                ⭐ <?= htmlspecialchars($rating) ?>
                (<?= $total_reviews ?> reviews)
            </p>
            <?php if ($is_verified): ?>
                <span class="verified">
                    ✓ Verified Provider
                </span>
            <?php else: ?>
                <span class="verified">
                    ⏳ Verification pending
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- =========================================
     QUICK ACTIONS
========================================= -->
<div class="quick-actions">

    <a
        href="provider-portfolio.php"
        class="action-btn"
    >
        📸 My Work
    </a>
    <a
        href="add-services.php"
        class="action-btn"
    >
        ➕ Add Service
    </a>
</div>
<!-- =========================================
     STATS
========================================= -->
<div class="stats">
    <div class="stat-card">
        <h3>
            <?= $services_count ?>
        </h3>
        <p>
            My Services
        </p>
    </div>
    <div class="stat-card">
        <h3>
            <?= $available_count ?>
        </h3>
        <p>
            Available
        </p>
    </div>
    <div class="stat-card">
        <h3>
            <?= $busy_count ?>
        </h3>
        <p>
            Busy
        </p>
    </div>
    <div class="stat-card">
        <h3>
            <?= $total_reviews ?>
        </h3>
        <p>
            Reviews
        </p>
    </div>
</div>
<!-- =========================================
     SERVICES
========================================= -->
<div class="section-title">
    <h2>
        My Services
    </h2>
    <div class="section-actions">
        <a
            href="add-services.php"
            class="btn btn-primary"
        >
            + Add New Service
        </a>
    </div>
</div>
<?php if (empty($services)): ?>
    <div class="empty-state">
        <h3>
            Hujaongeza huduma bado
        </h3>
        <p>
            Anza kwa kuongeza huduma unayotoa
            ili wateja waweze kukuona kwenye
            KaziHub Tanzania.
        </p>
        <a
            href="add-services.php"
            class="btn btn-primary"
        >
            + Add Your First Service
        </a>
    </div>
<?php else: ?>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
            <?php
            $availability = strtolower(
                trim($service["availability"] ?? "")
            );
            $service_location =
                $service["provider_location"]
                ?: $service["service_location"]
                ?: "Location not set";
            ?>
            <div class="service-card">
                <span class="category">
                    <?= htmlspecialchars(
                        $service["category"] ?? "Service"
                    ) ?>
                </span>
                <h3>
                    <?= htmlspecialchars(
                        $service["service_name"]
                    ) ?>
                </h3>
                <p class="service-description">
                    <?= htmlspecialchars(
                        $service["service_description"]
                        ?: "Huduma hii inapatikana kupitia KaziHub Tanzania."
                    ) ?>
                </p>
                <div class="service-info">
                    <div>
                        📍
                        <?= htmlspecialchars(
                            $service_location
                        ) ?>
                    </div>
                    <div class="price">
                        <?php if ($service["price"] !== null): ?>
                            TSh
                            <?= number_format(
                                (float)$service["price"]
                            ) ?>
                            <?php if (
                                $service["price_type"]
                                !== "negotiable"
                            ): ?>
                                /
                                <?= htmlspecialchars(
                                    $service["price_type"]
                                ) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            Bei: Negotiable
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php
                        if ($availability === "available") {
                            $availability_text =
                                "✓ Available";
                        } elseif ($availability === "busy") {
                            $availability_text =
                                "⏳ Busy";
                        } else {
                            $availability_text =
                                "✕ Unavailable";
                        }
                        ?>
                        <span
                            class="availability <?= htmlspecialchars(
                                $availability
                            ) ?>"
                        >
                            <?= $availability_text ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>