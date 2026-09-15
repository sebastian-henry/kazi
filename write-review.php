<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int)$_SESSION["user_id"];
$booking_id = (int)($_GET["booking_id"] ?? 0);

if ($booking_id <= 0) {
    header("Location: my-bookings.php");
    exit;
}

/* Get completed booking */
$sql = "SELECT
            b.id,
            b.provider_id,
            b.status,
            u.full_name AS provider_name,
            pp.business_name
        FROM bookings b
        INNER JOIN users u ON b.provider_id = u.id
        LEFT JOIN provider_profiles pp ON b.provider_id = pp.provider_id
        WHERE b.id = ?
        AND b.customer_id = ?
        AND b.status = 'completed'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("This booking is not available for review.");
}

$error = "";
$success = "";

/* Check if already reviewed */
$check = $conn->prepare(
    "SELECT id FROM reviews WHERE booking_id = ? LIMIT 1"
);
$check->bind_param("i", $booking_id);
$check->execute();
$already_reviewed = $check->get_result()->num_rows > 0;
$check->close();

/* Submit review */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$already_reviewed) {

    $rating = (int)($_POST["rating"] ?? 0);
    $review = trim($_POST["review"] ?? "");

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating from 1 to 5 stars.";
    } elseif ($review === "") {
        $error = "Please write your review.";
    } else {

        $insert = $conn->prepare(
            "INSERT INTO reviews
            (customer_id, provider_id, booking_id, rating, review)
            VALUES (?, ?, ?, ?, ?)"
        );

        $insert->bind_param(
            "iiiis",
            $customer_id,
            $booking["provider_id"],
            $booking_id,
            $rating,
            $review
        );

        if ($insert->execute()) {

            /* Update provider rating */
            $rating_sql = "SELECT
                                COUNT(*) AS total_reviews,
                                AVG(rating) AS average_rating
                           FROM reviews
                           WHERE provider_id = ?";

            $rating_stmt = $conn->prepare($rating_sql);
            $rating_stmt->bind_param("i", $booking["provider_id"]);
            $rating_stmt->execute();

            $rating_data = $rating_stmt->get_result()->fetch_assoc();
            $rating_stmt->close();

            $total_reviews = (int)$rating_data["total_reviews"];
            $average_rating = round((float)$rating_data["average_rating"], 1);

            $update = $conn->prepare(
                "UPDATE provider_profiles
                 SET rating = ?, total_reviews = ?
                 WHERE provider_id = ?"
            );

            $update->bind_param(
                "dii",
                $average_rating,
                $total_reviews,
                $booking["provider_id"]
            );

            $update->execute();
            $update->close();

            /* Notify provider */
            $notification_title = "New Review ⭐";
            $notification_message =
                $_SESSION["full_name"] .
                " has left you a " .
                $rating .
                "-star review.";

            $notification_type = "review";

            $notify = $conn->prepare(
                "INSERT INTO notifications
                (user_id, title, message, type, related_id, is_read)
                VALUES (?, ?, ?, ?, ?, 0)"
            );

            $notify->bind_param(
                "isssi",
                $booking["provider_id"],
                $notification_title,
                $notification_message,
                $notification_type,
                $booking_id
            );

            $notify->execute();
            $notify->close();

            $success = "Your review has been submitted successfully!";
            $already_reviewed = true;

        } else {
            $error = "Something went wrong. Please try again.";
        }

        $insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Write a Review | KaziHub Tanzania</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e8ebf2;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #123c69;
        }

        .logo span {
            color: #f59e0b;
        }

        .back-btn {
            text-decoration: none;
            color: #123c69;
            font-weight: 700;
        }

        .container {
            max-width: 700px;
            margin: 55px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 22px;
            padding: 35px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header .icon {
            width: 70px;
            height: 70px;
            background: #fff7df;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 15px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .header p {
            color: #6b7280;
        }

        .provider-box {
            background: #f7f9fc;
            border: 1px solid #e8ebf2;
            padding: 18px;
            border-radius: 15px;
            margin-bottom: 28px;
        }

        .provider-box strong {
            display: block;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .provider-box span {
            color: #6b7280;
        }

        .label {
            display: block;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .stars {
            display: flex;
            gap: 8px;
            margin-bottom: 25px;
        }

        .stars input {
            display: none;
        }

        .stars label {
            font-size: 42px;
            color: #d1d5db;
            cursor: pointer;
            transition: 0.2s;
        }

        .stars label:hover,
        .stars label:hover ~ label {
            color: #fbbf24;
        }

        .stars input:checked ~ label {
            color: #d1d5db;
        }

        .stars label:has(~ input:checked),
        .stars input:checked + label {
            color: #fbbf24;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            resize: vertical;
            border: 1px solid #dce1ea;
            border-radius: 14px;
            padding: 16px;
            font-size: 15px;
            outline: none;
            margin-bottom: 22px;
        }

        textarea:focus {
            border-color: #2563eb;
        }

        .submit-btn {
            width: 100%;
            border: none;
            background: #123c69;
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #0d2d4f;
        }

        .message {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .already {
            text-align: center;
            padding: 25px;
            background: #f7f9fc;
            border-radius: 15px;
            color: #374151;
        }

        @media (max-width: 600px) {
            .card {
                padding: 24px 18px;
            }

            .header h1 {
                font-size: 24px;
            }

            .stars label {
                font-size: 35px;
            }
        }
    </style>
</head>

<body>

    <div class="navbar">

        <div class="logo">
            KaziHub <span>🇹🇿</span>
        </div>

        <a href="my-bookings.php" class="back-btn">
            ← My Bookings
        </a>

    </div>

    <div class="container">

        <div class="card">

            <div class="header">

                <div class="icon">⭐</div>

                <h1>Rate Your Experience</h1>

                <p>Share your experience with this service provider.</p>

            </div>

            <div class="provider-box">

                <strong>
                    <?= htmlspecialchars(
                        $booking["business_name"]
                        ?: $booking["provider_name"]
                    ) ?>
                </strong>

                <span>
                    Provider: <?= htmlspecialchars($booking["provider_name"]) ?>
                </span>

            </div>

            <?php if ($error): ?>

                <div class="message error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <?php if ($success): ?>

                <div class="message success">
                    <?= htmlspecialchars($success) ?>
                </div>

            <?php endif; ?>

            <?php if ($already_reviewed): ?>

                <div class="already">
                    ⭐ <strong>You have already reviewed this booking.</strong>
                    <br><br>
                    Thank you for helping other customers make better choices.
                </div>

            <?php else: ?>

                <form method="POST">

                    <label class="label">
                        How would you rate this provider?
                    </label>

                    <div class="stars">

                        <input type="radio" id="star5" name="rating" value="5">
                        <label for="star5">★</label>

                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4">★</label>

                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3">★</label>

                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2">★</label>

                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1">★</label>

                    </div>

                    <label class="label">
                        Write your review
                    </label>

                    <textarea
                        name="review"
                        placeholder="Tell other customers about your experience..."
                        required
                    ></textarea>

                    <button type="submit" class="submit-btn">
                        ⭐ Submit Review
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>