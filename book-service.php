<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
   GET SERVICE ID
========================= */

$provider_service_id = (int) (
    $_GET["provider_service_id"]
    ?? $_POST["provider_service_id"]
    ?? 0
);

if ($provider_service_id <= 0) {
    die("Invalid service selected.");
}

/* =========================
   GET SERVICE + PROVIDER
========================= */

$sql = "SELECT
            ps.id AS provider_service_id,
            ps.provider_id,
            ps.price,
            ps.price_type,
            ps.service_description,
            ps.location AS service_location,
            ps.availability,

            s.service_name,
            s.category,

            u.full_name,
            u.phone,
            u.email,

            pp.business_name,
            pp.location AS provider_location,
            pp.profile_image AS provider_image

        FROM provider_services ps

        INNER JOIN services s
            ON ps.service_id = s.id

        INNER JOIN users u
            ON ps.provider_id = u.id

        LEFT JOIN provider_profiles pp
            ON ps.provider_id = pp.provider_id

        WHERE ps.id = ?

        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $provider_service_id);
$stmt->execute();

$result = $stmt->get_result();

$service = $result->fetch_assoc();

$stmt->close();

if (!$service) {
    die("Service not found.");
}

/* =========================
   PREVENT PROVIDER BOOKING OWN SERVICE
========================= */

if ((int)$service["provider_id"] === $customer_id) {
    die("You cannot book your own service.");
}

/* =========================
   FORM SUBMISSION
========================= */

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_date = trim($_POST["booking_date"] ?? "");
    $booking_time = trim($_POST["booking_time"] ?? "");
    $location     = trim($_POST["location"] ?? "");
    $notes        = trim($_POST["notes"] ?? "");

    /* Validate */

    if ($booking_date === "" || $booking_time === "" || $location === "") {

        $error = "Please fill in all required fields.";

    } elseif ($booking_date < date("Y-m-d")) {

        $error = "Please select a valid future date.";

    } else {

        /* =========================
           CHECK DUPLICATE BOOKING
        ========================= */

        $check_sql = "SELECT id
                      FROM bookings
                      WHERE customer_id = ?
                      AND provider_service_id = ?
                      AND booking_date = ?
                      AND booking_time = ?
                      AND status IN ('pending','accepted')
                      LIMIT 1";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $error = "Database Error: " . $conn->error;

        } else {

            $check_stmt->bind_param(
                "iiss",
                $customer_id,
                $provider_service_id,
                $booking_date,
                $booking_time
            );

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $error = "You already have a booking for this service at this date and time.";

            } else {

                /* =========================
                   INSERT BOOKING
                ========================= */

                $insert_sql = "INSERT INTO bookings
                    (
                        customer_id,
                        provider_id,
                        provider_service_id,
                        booking_date,
                        booking_time,
                        location,
                        notes,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

                $insert_stmt = $conn->prepare($insert_sql);

                if (!$insert_stmt) {

                    $error = "Unable to prepare booking: " . $conn->error;

                } else {

                    $provider_id = (int)$service["provider_id"];

                    $insert_stmt->bind_param(
                        "iiissss",
                        $customer_id,
                        $provider_id,
                        $provider_service_id,
                        $booking_date,
                        $booking_time,
                        $location,
                        $notes
                    );

                    if ($insert_stmt->execute()) {

                        /* =========================
                           CREATE PROVIDER NOTIFICATION
                        ========================= */

                        $customer_name = $_SESSION["full_name"] ?? "A customer";
                        $service_name = $service["service_name"];

                        $notification_title = "New Booking Request";
                        $notification_message =
                            $customer_name .
                            " has requested to book your " .
                            $service_name .
                            " service.";

                        $notification_type = "booking";
                        $booking_id = (int)$conn->insert_id;

                        $notification_sql = "INSERT INTO notifications
                            (
                                user_id,
                                title,
                                message,
                                type,
                                related_id,
                                is_read
                            )
                            VALUES (?, ?, ?, ?, ?, 0)";

                        $notification_stmt = $conn->prepare($notification_sql);

                        if ($notification_stmt) {

                            $notification_stmt->bind_param(
                                "isssi",
                                $provider_id,
                                $notification_title,
                                $notification_message,
                                $notification_type,
                                $booking_id
                            );

                            $notification_stmt->execute();

                            $notification_stmt->close();
                        }

                        $success = "Booking submitted successfully!";

                    } else {

                        $error = "Booking failed: " . $insert_stmt->error;
                    }

                    $insert_stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}

/* =========================
   DISPLAY NAME
========================= */

$display_name = !empty($service["business_name"])
    ? $service["business_name"]
    : $service["full_name"];

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Book Service - KaziHub Tanzania</title>

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
    color: white;
    text-decoration: none;
    background: #2563eb;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
}

.container {
    width: 92%;
    max-width: 850px;
    margin: 40px auto;
}

.card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.08);
}

h1 {
    font-size: 30px;
    margin-bottom: 8px;
}

.subtitle {
    color: #64748b;
    margin-bottom: 25px;
}

.service-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
}

.service-box h2 {
    margin-bottom: 8px;
}

.category {
    color: #2563eb;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 12px;
}

.info {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    color: #475569;
    font-size: 14px;
    margin-top: 15px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

input,
textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 15px;
    outline: none;
}

input:focus,
textarea:focus {
    border-color: #2563eb;
}

textarea {
    min-height: 110px;
    resize: vertical;
}

.submit-btn {
    width: 100%;
    border: none;
    background: #2563eb;
    color: white;
    padding: 15px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.submit-btn:hover {
    background: #1d4ed8;
}

.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 22px;
    border-radius: 14px;
    margin-bottom: 20px;
}

.success h2 {
    margin-bottom: 8px;
}

.success p {
    margin-bottom: 15px;
}

.success-btn {
    display: inline-block;
    background: #16a34a;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 8px;
    font-weight: bold;
}

@media (max-width: 600px) {

    .topbar {
        padding: 15px 5%;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

    .card {
        padding: 20px;
    }

    h1 {
        font-size: 25px;
    }
}

</style>

</head>

<body>

<div class="topbar">

    <div class="logo">
        KaziHub <span>🇹🇿</span>
    </div>

    <a href="provider-profile.php?provider_id=<?= (int)$service["provider_id"] ?>"
       class="back-btn">
        ← Back
    </a>

</div>


<div class="container">

<div class="card">

    <h1>Book a Service</h1>

    <p class="subtitle">
        Send a booking request to this service provider.
    </p>


    <?php if ($success): ?>

        <div class="success">

            <h2>🎉 Booking Submitted!</h2>

            <p>
                Your booking request has been sent successfully.
                The provider will review your request.
            </p>

            <a href="dashboard.php"
               class="success-btn">
                ← Back to Dashboard
            </a>

        </div>

    <?php else: ?>


        <?php if ($error): ?>

            <div class="error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="service-box">

            <h2>
                <?= htmlspecialchars($service["service_name"]) ?>
            </h2>

            <div class="category">
                <?= htmlspecialchars($service["category"]) ?>
            </div>

            <p>
                Provider:
                <strong>
                    <?= htmlspecialchars($display_name) ?>
                </strong>
            </p>


            <?php if (!empty($service["service_description"])): ?>

                <p style="margin-top:12px; color:#64748b; line-height:1.6;">
                    <?= nl2br(
                        htmlspecialchars($service["service_description"])
                    ) ?>
                </p>

            <?php endif; ?>


            <div class="info">

                <?php if ($service["price"] !== null): ?>

                    <span>
                        💰 TSh <?= number_format($service["price"]) ?>

                        <?php if (!empty($service["price_type"])): ?>
                            / <?= htmlspecialchars($service["price_type"]) ?>
                        <?php endif; ?>

                    </span>

                <?php endif; ?>


                <?php if (!empty($service["service_location"])): ?>

                    <span>
                        📍 <?= htmlspecialchars($service["service_location"]) ?>
                    </span>

                <?php endif; ?>


                <?php if ($service["availability"] === "available"): ?>

                    <span style="color:#16a34a;font-weight:bold;">
                        🟢 Available
                    </span>

                <?php elseif ($service["availability"] === "busy"): ?>

                    <span style="color:#f59e0b;font-weight:bold;">
                        🟡 Busy
                    </span>

                <?php else: ?>

                    <span style="color:#dc2626;font-weight:bold;">
                        🔴 Unavailable
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <form method="POST"
              action="book-service.php?provider_service_id=<?= $provider_service_id ?>">

            <input type="hidden"
                   name="provider_service_id"
                   value="<?= $provider_service_id ?>">


            <div class="form-group">

                <label>
                    Booking Date *
                </label>

                <input
                    type="date"
                    name="booking_date"
                    min="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Booking Time *
                </label>

                <input
                    type="time"
                    name="booking_time"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Service Location *
                </label>

                <input
                    type="text"
                    name="location"
                    placeholder="Example: Mwenge, Dar es Salaam"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Additional Notes
                </label>

                <textarea
                    name="notes"
                    placeholder="Tell the provider anything important about your booking..."
                ></textarea>

            </div>


            <button type="submit"
                    class="submit-btn">

                📅 Confirm Booking

            </button>

        </form>

    <?php endif; ?>

</div>

</div>

</body>

</html>