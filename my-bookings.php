<?php

session_start();

require_once "includes/config.php";

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION["user_id"];


/* =========================
   GET CUSTOMER BOOKINGS
========================= */

$sql = "SELECT
            b.id,
            b.booking_date,
            b.booking_time,
            b.location,
            b.notes,
            b.status,
            b.created_at,

            ps.price,
            ps.price_type,
            ps.service_description,

            s.service_name,
            s.category,

            u.id AS provider_id,
            u.full_name AS provider_name,
            u.phone AS provider_phone,

            pp.business_name,
            pp.profile_image AS provider_image,
            pp.location AS provider_location

        FROM bookings b

        INNER JOIN provider_services ps
            ON b.provider_service_id = ps.id

        INNER JOIN services s
            ON ps.service_id = s.id

        INNER JOIN users u
            ON b.provider_id = u.id

        LEFT JOIN provider_profiles pp
            ON u.id = pp.provider_id

        WHERE b.customer_id = ?

        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $customer_id);

$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Bookings - KaziHub Tanzania</title>

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
   TOPBAR
========================= */

.topbar {
    background: #0f172a;
    color: white;
    padding: 18px 7%;

    display: flex;
    justify-content: space-between;
    align-items: center;
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

    padding: 10px 16px;

    border-radius: 8px;

    font-size: 14px;
}

/* =========================
   CONTAINER
========================= */

.container {
    width: 92%;
    max-width: 1100px;

    margin: 40px auto;
}

/* =========================
   HEADER
========================= */

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.page-header p {
    color: #64748b;
}

/* =========================
   BOOKING CARD
========================= */

.booking-card {
    background: white;

    border-radius: 18px;

    padding: 25px;

    margin-bottom: 20px;

    box-shadow: 0 8px 28px rgba(0,0,0,0.07);

    border: 1px solid #eef2f7;
}

/* =========================
   TOP
========================= */

.booking-top {
    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 20px;
}

.service-title h2 {
    font-size: 21px;

    margin-bottom: 7px;
}

.category {
    color: #2563eb;

    font-size: 13px;

    font-weight: bold;
}

/* =========================
   STATUS
========================= */

.status {
    padding: 8px 13px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

    white-space: nowrap;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-accepted {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.status-completed {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-cancelled {
    background: #e2e8f0;
    color: #475569;
}

/* =========================
   PROVIDER
========================= */

.provider-box {
    display: flex;

    align-items: center;

    gap: 13px;

    background: #f8fafc;

    padding: 15px;

    border-radius: 12px;

    margin-bottom: 20px;
}

.provider-avatar {
    width: 48px;
    height: 48px;

    border-radius: 50%;

    background: #2563eb;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 19px;

    font-weight: bold;

    overflow: hidden;

    flex-shrink: 0;
}

.provider-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}

.provider-name {
    font-weight: bold;

    margin-bottom: 4px;
}

.provider-phone {
    color: #64748b;

    font-size: 13px;
}

/* =========================
   DETAILS
========================= */

.details {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(190px, 1fr));

    gap: 12px;

    margin-bottom: 20px;
}

.detail {
    background: #f8fafc;

    padding: 13px;

    border-radius: 10px;
}

.detail-label {
    font-size: 12px;

    color: #64748b;

    margin-bottom: 5px;
}

.detail-value {
    font-size: 14px;

    font-weight: bold;
}

/* =========================
   NOTES
========================= */

.notes {
    background: #f8fafc;

    border-left: 4px solid #2563eb;

    padding: 13px;

    margin-bottom: 20px;

    color: #475569;

    line-height: 1.5;
}

.notes strong {
    color: #172033;
}

/* =========================
   ACTIONS
========================= */

.actions {
    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    border-top: 1px solid #e2e8f0;

    padding-top: 18px;
}

.action-btn {
    text-decoration: none;

    padding: 10px 16px;

    border-radius: 8px;

    font-size: 14px;

    font-weight: bold;

    display: inline-block;
}

.view-provider {
    background: #e8eefc;
    color: #2563eb;
}

.chat-btn {
    background: #16a34a;
    color: white;
}

.review-btn {
    background: #f59e0b;
    color: white;
}

.review-btn:hover {
    background: #d97706;
}

/* =========================
   EMPTY
========================= */

.empty {
    background: white;

    border-radius: 18px;

    padding: 60px 20px;

    text-align: center;

    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
}

.empty-icon {
    font-size: 50px;

    margin-bottom: 15px;
}

.empty h2 {
    margin-bottom: 8px;
}

.empty p {
    color: #64748b;

    margin-bottom: 20px;
}

.find-btn {
    display: inline-block;

    text-decoration: none;

    background: #2563eb;

    color: white;

    padding: 12px 20px;

    border-radius: 9px;

    font-weight: bold;
}

/* =========================
   MOBILE
========================= */

@media (max-width: 650px) {

    .topbar {
        padding: 15px 5%;
    }

    .container {
        width: 94%;

        margin-top: 25px;
    }

    .page-header h1 {
        font-size: 27px;
    }

    .booking-top {
        flex-direction: column;
    }

    .status {
        align-self: flex-start;
    }

    .actions {
        flex-direction: column;
    }

    .action-btn {
        text-align: center;
        width: 100%;
    }

}

</style>

</head>

<body>


<!-- =========================
     TOPBAR
========================= -->

<div class="topbar">

    <div class="logo">
        KaziHub <span>🇹🇿</span>
    </div>

    <a href="dashboard.php"
       class="back-btn">
        ← Dashboard
    </a>

</div>


<div class="container">


    <!-- =========================
         HEADER
    ========================= -->

    <div class="page-header">

        <h1>
            My Bookings
        </h1>

        <p>
            Track your service bookings and their current status.
        </p>

    </div>


    <?php if (empty($bookings)): ?>


        <!-- =========================
             EMPTY
        ========================= -->

        <div class="empty">

            <div class="empty-icon">
                📅
            </div>

            <h2>
                No Bookings Yet
            </h2>

            <p>
                You haven't booked any services yet.
            </p>

            <a href="find-services.php"
               class="find-btn">

                Find a Service

            </a>

        </div>


    <?php else: ?>


        <?php foreach ($bookings as $booking): ?>


            <?php

            $status = strtolower($booking["status"]);

            $status_class = "status-" . $status;

            $provider_display_name =
                !empty($booking["business_name"])
                ? $booking["business_name"]
                : $booking["provider_name"];

            $initial =
                strtoupper(
                    substr($provider_display_name, 0, 1)
                );

            ?>


            <!-- =========================
                 BOOKING CARD
            ========================= -->

            <div class="booking-card">


                <div class="booking-top">


                    <div class="service-title">

                        <h2>
                            <?= htmlspecialchars(
                                $booking["service_name"]
                            ) ?>
                        </h2>

                        <div class="category">

                            <?= htmlspecialchars(
                                $booking["category"]
                            ) ?>

                        </div>

                    </div>


                    <div class="status <?= $status_class ?>">

                        <?php if ($status === "pending"): ?>

                            ⏳ Pending

                        <?php elseif ($status === "accepted"): ?>

                            ✅ Accepted

                        <?php elseif ($status === "rejected"): ?>

                            ❌ Rejected

                        <?php elseif ($status === "completed"): ?>

                            🎉 Completed

                        <?php elseif ($status === "cancelled"): ?>

                            🚫 Cancelled

                        <?php else: ?>

                            <?= htmlspecialchars(
                                ucfirst($status)
                            ) ?>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- =========================
                     PROVIDER
                ========================= -->

                <div class="provider-box">


                    <div class="provider-avatar">

                        <?php if (!empty($booking["provider_image"])): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $booking["provider_image"]
                                ) ?>"
                                alt="Provider"
                            >

                        <?php else: ?>

                            <?= htmlspecialchars($initial) ?>

                        <?php endif; ?>

                    </div>


                    <div>

                        <div class="provider-name">

                            <?= htmlspecialchars(
                                $provider_display_name
                            ) ?>

                        </div>


                        <?php if (!empty($booking["provider_phone"])): ?>

                            <div class="provider-phone">

                                📞
                                <?= htmlspecialchars(
                                    $booking["provider_phone"]
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- =========================
                     DETAILS
                ========================= -->

                <div class="details">


                    <div class="detail">

                        <div class="detail-label">
                            📅 Booking Date
                        </div>

                        <div class="detail-value">

                            <?= date(
                                "d M Y",
                                strtotime(
                                    $booking["booking_date"]
                                )
                            ) ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            🕐 Booking Time
                        </div>

                        <div class="detail-value">

                            <?= date(
                                "h:i A",
                                strtotime(
                                    $booking["booking_time"]
                                )
                            ) ?>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-label">
                            📍 Location
                        </div>

                        <div class="detail-value">

                            <?= htmlspecialchars(
                                $booking["location"]
                            ) ?>

                        </div>

                    </div>


                    <?php if ($booking["price"] !== null): ?>

                        <div class="detail">

                            <div class="detail-label">
                                💰 Price
                            </div>

                            <div class="detail-value">

                                TSh
                                <?= number_format(
                                    $booking["price"]
                                ) ?>

                                <?php if (!empty($booking["price_type"])): ?>

                                    /
                                    <?= htmlspecialchars(
                                        $booking["price_type"]
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <!-- =========================
                     NOTES
                ========================= -->

                <?php if (!empty($booking["notes"])): ?>

                    <div class="notes">

                        <strong>
                            📝 Your Notes
                        </strong>

                        <br>

                        <?= nl2br(
                            htmlspecialchars(
                                $booking["notes"]
                            )
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =========================
                     ACTIONS
                ========================= -->

                <div class="actions">


                    <a
                        href="provider-profile.php?provider_id=<?= (int)$booking["provider_id"] ?>"
                        class="action-btn view-provider"
                    >
                        👤 View Provider
                    </a>


                    <a
                        href="chat.php?user_id=<?= (int)$booking["provider_id"] ?>"
                        class="action-btn chat-btn"
                    >
                        💬 Chat with Provider
                    </a>


                    <?php if ($status === "completed"): ?>

                        <a
                            href="write-review.php?booking_id=<?= (int)$booking["id"] ?>"
                            class="action-btn review-btn"
                        >
                            ⭐ Write Review
                        </a>

                    <?php endif; ?>


                </div>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>

</body>

</html>