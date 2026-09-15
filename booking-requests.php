<?php
session_start();
require_once "includes/config.php";

/* Hakikisha provider ameingia */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Provider pekee */
if (($_SESSION["role"] ?? "") !== "provider") {
    header("Location: dashboard.php");
    exit;
}

$provider_id = $_SESSION["user_id"];
$message = "";
$error = "";

/* =========================
   ACCEPT / REJECT / COMPLETE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = intval($_POST["booking_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($booking_id > 0) {

        if ($action === "accept") {
            $new_status = "accepted";
        } elseif ($action === "reject") {
            $new_status = "rejected";
        } elseif ($action === "complete") {
            $new_status = "completed";
        } else {
            $new_status = "";
        }

        if ($new_status !== "") {

            /* Hakikisha booking hii ni ya provider huyu */
            $sql = "UPDATE bookings
                    SET status = ?
                    WHERE id = ?
                    AND provider_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sii",
                $new_status,
                $booking_id,
                $provider_id
            );

            if ($stmt->execute()) {
                $message = "Booking imebadilishwa kuwa " . $new_status . ".";
            } else {
                $error = "Imeshindikana kubadilisha booking.";
            }

            $stmt->close();
        }
    }
}

/* =========================
   GET PROVIDER BOOKINGS
========================= */

$sql = "SELECT
            b.id,
            b.booking_date,
            b.booking_time,
            b.location,
            b.message,
            b.price,
            b.status,
            b.created_at,

            u.full_name AS customer_name,
            u.phone AS customer_phone,

            s.service_name,

            ps.service_description

        FROM bookings b

        INNER JOIN users u
            ON b.customer_id = u.id

        INNER JOIN provider_services ps
            ON b.provider_service_id = ps.id

        INNER JOIN services s
            ON ps.service_id = s.id

        WHERE b.provider_id = ?

        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

/* Provider name */
$provider_name = $_SESSION["full_name"] ?? "Provider";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Requests - KaziHub Tanzania</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .header {
            background: #0f172a;
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            background: #2563eb;
            padding: 10px 16px;
            border-radius: 8px;
        }

        .container {
            width: 92%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h1 {
            font-size: 30px;
            margin-bottom: 7px;
        }

        .page-title p {
            color: #64748b;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .booking-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.07);
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 18px;
        }

        .customer h2 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .service {
            color: #2563eb;
            font-weight: bold;
        }

        .status {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .accepted {
            background: #dcfce7;
            color: #166534;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .cancelled {
            background: #e5e7eb;
            color: #374151;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 18px 0;
        }

        .detail {
            background: #f8fafc;
            padding: 13px;
            border-radius: 8px;
        }

        .detail strong {
            display: block;
            margin-bottom: 5px;
        }

        .message {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            padding: 10px 17px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            color: white;
        }

        .accept {
            background: #16a34a;
        }

        .reject {
            background: #dc2626;
        }

        .complete {
            background: #2563eb;
        }

        .customer-contact {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .customer-contact a {
            color: #16a34a;
            font-weight: bold;
            text-decoration: none;
        }

        .empty {
            background: white;
            padding: 50px 20px;
            text-align: center;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .empty h2 {
            margin-bottom: 10px;
        }

        .empty p {
            color: #64748b;
        }

        @media (max-width: 700px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .top {
                flex-direction: column;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 25px;
            }
        }

    </style>
</head>

<body>

<header class="header">

    <div class="logo">
        KaziHub 🇹🇿
    </div>

    <a href="provider-dashboard.php" class="back-btn">
        ← Provider Dashboard
    </a>

</header>


<div class="container">

    <div class="page-title">

        <h1>📩 Booking Requests</h1>

        <p>
            Karibu <?= htmlspecialchars($provider_name) ?>.
            Hapa unaweza kusimamia booking za wateja wako.
        </p>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if (empty($bookings)): ?>

        <div class="empty">

            <h2>📭 Hakuna Booking Requests</h2>

            <p>
                Kwa sasa hakuna customer aliyeomba huduma yako.
            </p>

        </div>

    <?php else: ?>


        <?php foreach ($bookings as $booking): ?>

            <div class="booking-card">

                <div class="top">

                    <div class="customer">

                        <h2>
                            👤 <?= htmlspecialchars($booking["customer_name"]) ?>
                        </h2>

                        <div class="service">
                            🛠️ <?= htmlspecialchars($booking["service_name"]) ?>
                        </div>

                    </div>


                    <div class="status <?= htmlspecialchars($booking["status"]) ?>">

                        <?= htmlspecialchars($booking["status"]) ?>

                    </div>

                </div>


                <div class="details">

                    <div class="detail">

                        <strong>📅 Tarehe</strong>

                        <?= htmlspecialchars($booking["booking_date"]) ?>

                    </div>


                    <div class="detail">

                        <strong>⏰ Muda</strong>

                        <?= !empty($booking["booking_time"])
                            ? htmlspecialchars($booking["booking_time"])
                            : "Haijawekwa"
                        ?>

                    </div>


                    <div class="detail">

                        <strong>📍 Location</strong>

                        <?= htmlspecialchars($booking["location"]) ?>

                    </div>


                    <div class="detail">

                        <strong>💰 Bei</strong>

                        <?= $booking["price"] !== null
                            ? "TSh " . number_format($booking["price"])
                            : "Negotiable"
                        ?>

                    </div>

                </div>


                <?php if (!empty($booking["message"])): ?>

                    <div class="message">

                        <strong>📝 Ujumbe wa Customer:</strong>

                        <br><br>

                        <?= nl2br(htmlspecialchars($booking["message"])) ?>

                    </div>

                <?php endif; ?>


                <div class="customer-contact">

                    📞 Customer Phone:

                    <a href="tel:<?= htmlspecialchars($booking["customer_phone"]) ?>">
                        <?= htmlspecialchars($booking["customer_phone"]) ?>
                    </a>

                </div>


                <br>


                <div class="actions">

                    <?php if ($booking["status"] === "pending"): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= $booking["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="accept"
                            >

                            <button
                                type="submit"
                                class="btn accept"
                            >
                                ✓ Accept
                            </button>

                        </form>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= $booking["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="reject"
                            >

                            <button
                                type="submit"
                                class="btn reject"
                            >
                                ✕ Reject
                            </button>

                        </form>

                    <?php elseif ($booking["status"] === "accepted"): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?= $booking["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="complete"
                            >

                            <button
                                type="submit"
                                class="btn complete"
                            >
                                ✓ Mark as Completed
                            </button>

                        </form>

                    <?php elseif ($booking["status"] === "completed"): ?>

                        <span>
                            ✅ Huduma imekamilika
                        </span>

                    <?php elseif ($booking["status"] === "rejected"): ?>

                        <span>
                            ❌ Booking imekataliwa
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>