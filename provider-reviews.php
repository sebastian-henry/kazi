<?php
session_start();
require_once "includes/config.php";

/* Provider only */
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

/*
|--------------------------------------------------------------------------
| Get provider reviews
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        r.id,
        r.rating,
        r.review,
        r.created_at,
        u.full_name,
        u.profile_image
    FROM reviews r
    INNER JOIN users u ON r.customer_id = u.id
    WHERE r.provider_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];

while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Rating statistics
|--------------------------------------------------------------------------
*/
$total_reviews = count($reviews);
$total_rating = 0;

$rating_counts = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

foreach ($reviews as $review) {
    $rating = (int) $review["rating"];

    if ($rating >= 1 && $rating <= 5) {
        $total_rating += $rating;
        $rating_counts[$rating]++;
    }
}

$average_rating = $total_reviews > 0
    ? round($total_rating / $total_reviews, 1)
    : 0;

$rating_percentages = [];

foreach ($rating_counts as $star => $count) {
    $rating_percentages[$star] = $total_reviews > 0
        ? round(($count / $total_reviews) * 100)
        : 0;
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

    <title>Reviews | KaziHub Tanzania</title>

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

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e8ebf2;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 23px;
            font-weight: 800;
        }

        .logo span {
            color: #2563eb;
        }

        .back-btn {
            text-decoration: none;
            color: #2563eb;
            background: #eff6ff;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700;
        }

        .container {
            width: 88%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .heading {
            margin-bottom: 28px;
        }

        .heading h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .heading p {
            color: #6b7280;
            font-size: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | Rating Summary
        |--------------------------------------------------------------------------
        */

        .rating-summary {
            background: white;
            border: 1px solid #e8ebf2;
            border-radius: 20px;
            padding: 28px;
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }

        .overall {
            text-align: center;
            border-right: 1px solid #eef0f4;
            padding-right: 30px;
        }

        .overall-number {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stars {
            color: #f59e0b;
            font-size: 23px;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .total-text {
            color: #6b7280;
            font-size: 14px;
        }

        .rating-bars {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
        }

        .rating-row {
            display: grid;
            grid-template-columns: 45px 1fr 45px;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .bar {
            height: 9px;
            background: #edf0f5;
            border-radius: 20px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: #f59e0b;
            border-radius: 20px;
        }

        .percentage {
            color: #6b7280;
            text-align: right;
        }

        /*
        |--------------------------------------------------------------------------
        | Reviews
        |--------------------------------------------------------------------------
        */

        .reviews-title {
            font-size: 22px;
            margin-bottom: 18px;
        }

        .review-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .review-card {
            background: white;
            border: 1px solid #e8ebf2;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 7px 24px rgba(0,0,0,0.04);
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .customer {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .customer-name {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .review-date {
            color: #8a94a6;
            font-size: 12px;
        }

        .review-rating {
            color: #f59e0b;
            font-size: 18px;
            letter-spacing: 1px;
        }

        .review-text {
            margin-top: 17px;
            color: #4b5563;
            line-height: 1.7;
            font-size: 14px;
        }

        .empty {
            background: white;
            border: 1px solid #e8ebf2;
            border-radius: 20px;
            text-align: center;
            padding: 70px 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.04);
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .dashboard-btn {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
        }

        @media (max-width: 700px) {

            .container {
                width: 92%;
                margin: 30px auto;
            }

            .heading h1 {
                font-size: 27px;
            }

            .rating-summary {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .overall {
                border-right: none;
                border-bottom: 1px solid #eef0f4;
                padding-right: 0;
                padding-bottom: 20px;
            }

            .review-top {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<header class="topbar">

    <div class="logo">
        KaziHub <span>🇹🇿</span>
    </div>

    <a
        href="dashboard.php"
        class="back-btn"
    >
        ← Dashboard
    </a>

</header>

<main class="container">

    <div class="heading">

        <h1>My Reviews ⭐</h1>

        <p>
            See what your customers are saying about your services.
        </p>

    </div>


    <?php if ($total_reviews > 0): ?>

        <!-- Rating Summary -->

        <section class="rating-summary">

            <div class="overall">

                <div class="overall-number">
                    <?php echo number_format($average_rating, 1); ?>
                </div>

                <div class="stars">

                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= round($average_rating) ? "★" : "☆";
                    }
                    ?>

                </div>

                <div class="total-text">
                    <?php echo $total_reviews; ?>
                    <?php echo $total_reviews == 1 ? "Review" : "Reviews"; ?>
                </div>

            </div>


            <div class="rating-bars">

                <?php for ($star = 5; $star >= 1; $star++): ?>

                    <div class="rating-row">

                        <span>
                            <?php echo $star; ?> ⭐
                        </span>

                        <div class="bar">

                            <div
                                class="bar-fill"
                                style="width: <?php echo $rating_percentages[$star]; ?>%;"
                            ></div>

                        </div>

                        <span class="percentage">
                            <?php echo $rating_percentages[$star]; ?>%
                        </span>

                    </div>

                <?php endfor; ?>

            </div>

        </section>


        <!-- Reviews -->

        <h2 class="reviews-title">
            Customer Reviews
        </h2>

        <div class="review-list">

            <?php foreach ($reviews as $review): ?>

                <?php
                    $customer_name = $review["full_name"] ?? "Customer";

                    $first_letter = strtoupper(
                        substr(trim($customer_name), 0, 1)
                    );
                ?>

                <div class="review-card">

                    <div class="review-top">

                        <div class="customer">

                            <div class="avatar">

                                <?php if (!empty($review["profile_image"])): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($review["profile_image"]); ?>"
                                        alt="Customer"
                                    >

                                <?php else: ?>

                                    <?php echo htmlspecialchars($first_letter); ?>

                                <?php endif; ?>

                            </div>

                            <div>

                                <div class="customer-name">
                                    <?php echo htmlspecialchars($customer_name); ?>
                                </div>

                                <div class="review-date">

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($review["created_at"])
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>


                        <div class="review-rating">

                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= (int)$review["rating"]
                                    ? "★"
                                    : "☆";
                            }
                            ?>

                        </div>

                    </div>


                    <?php if (!empty($review["review"])): ?>

                        <div class="review-text">

                            <?php
                            echo nl2br(
                                htmlspecialchars($review["review"])
                            );
                            ?>

                        </div>

                    <?php else: ?>

                        <div class="review-text">
                            Customer left a rating without a comment.
                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>


    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                ⭐
            </div>

            <h2>No Reviews Yet</h2>

            <p>
                Customer reviews will appear here after customers
                review your services.
            </p>

        </div>

    <?php endif; ?>

</main>

</body>
</html>