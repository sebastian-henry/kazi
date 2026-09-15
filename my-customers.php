<?php
session_start();
require_once "includes/config.php";

/* Provider only */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "provider") {
    header("Location: dashboard.php");
    exit;
}

$provider_id = (int) $_SESSION["user_id"];
$full_name = $_SESSION["full_name"] ?? "Provider";

/*
|--------------------------------------------------------------------------
| Get customers who have booked this provider
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        u.id AS customer_id,
        u.full_name,
        u.phone,
        u.email,
        u.profile_image,
        COUNT(b.id) AS total_bookings,
        MAX(b.created_at) AS last_booking
    FROM bookings b
    INNER JOIN users u ON b.customer_id = u.id
    WHERE b.provider_id = ?
    GROUP BY
        u.id,
        u.full_name,
        u.phone,
        u.email,
        u.profile_image
    ORDER BY last_booking DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

$customers = [];

while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Customers | KaziHub Tanzania</title>

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
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 23px;
            font-weight: 800;
            color: #172033;
        }

        .logo span {
            color: #2563eb;
        }

        .back-btn {
            text-decoration: none;
            color: #2563eb;
            font-weight: 700;
            background: #eff6ff;
            padding: 10px 16px;
            border-radius: 10px;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #dbeafe;
        }

        .container {
            width: 88%;
            max-width: 1200px;
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

        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #e8ebf2;
            box-shadow: 0 8px 25px rgba(0,0,0,0.04);
        }

        .stat-card .icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .stat-card h2 {
            font-size: 28px;
            margin-bottom: 4px;
        }

        .stat-card p {
            color: #6b7280;
            font-size: 14px;
        }

        .customers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .customer-card {
            background: #ffffff;
            border: 1px solid #e8ebf2;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.25s;
        }

        .customer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .customer-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            overflow: hidden;
            flex-shrink: 0;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .customer-name {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .customer-role {
            color: #6b7280;
            font-size: 13px;
        }

        .info {
            border-top: 1px solid #eef0f4;
            padding-top: 16px;
            margin-top: 8px;
        }

        .info-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-row span:first-child {
            width: 25px;
        }

        .info-row span:last-child {
            color: #4b5563;
            word-break: break-word;
        }

        .booking-count {
            background: #eff6ff;
            color: #2563eb;
            padding: 8px 12px;
            border-radius: 9px;
            display: inline-block;
            font-size: 13px;
            font-weight: 700;
            margin-top: 5px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-btn {
            flex: 1;
            text-align: center;
            text-decoration: none;
            padding: 11px 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .chat-btn {
            background: #2563eb;
            color: white;
        }

        .chat-btn:hover {
            background: #1d4ed8;
        }

        .view-btn {
            background: #f1f5f9;
            color: #334155;
        }

        .view-btn:hover {
            background: #e2e8f0;
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
            margin-bottom: 18px;
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

        @media (max-width: 900px) {
            .customers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .topbar {
                padding: 15px 5%;
            }

            .container {
                width: 92%;
                margin: 30px auto;
            }

            .heading h1 {
                font-size: 27px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .customers-grid {
                grid-template-columns: 1fr;
            }

            .back-btn {
                padding: 9px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">

    <div class="logo">
        KaziHub <span>🇹🇿</span>
    </div>

    <a href="provider-dashboard.php" class="back-btn">
        ← Dashboard
    </a>

</header>

<main class="container">

    <div class="heading">
        <h1>My Customers 👥</h1>
        <p>
            Customers who have booked your services on KaziHub Tanzania.
        </p>
    </div>

    <div class="stats">

        <div class="stat-card">
            <div class="icon">👥</div>
            <h2><?php echo count($customers); ?></h2>
            <p>Total Customers</p>
        </div>

        <div class="stat-card">
            <div class="icon">🤝</div>
            <h2>Active</h2>
            <p>Your customer relationships</p>
        </div>

    </div>

    <?php if (count($customers) > 0): ?>

        <div class="customers-grid">

            <?php foreach ($customers as $customer): ?>

                <?php
                    $name = $customer["full_name"] ?? "Customer";

                    $first_letter = strtoupper(
                        substr(trim($name), 0, 1)
                    );
                ?>

                <div class="customer-card">

                    <div class="customer-top">

                        <div class="avatar">

                            <?php if (!empty($customer["profile_image"])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($customer["profile_image"]); ?>"
                                    alt="Customer"
                                >

                            <?php else: ?>

                                <?php echo htmlspecialchars($first_letter); ?>

                            <?php endif; ?>

                        </div>

                        <div>
                            <div class="customer-name">
                                <?php echo htmlspecialchars($name); ?>
                            </div>

                            <div class="customer-role">
                                KaziHub Customer
                            </div>
                        </div>

                    </div>

                    <div class="info">

                        <?php if (!empty($customer["phone"])): ?>
                            <div class="info-row">
                                <span>📞</span>
                                <span>
                                    <?php echo htmlspecialchars($customer["phone"]); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($customer["email"])): ?>
                            <div class="info-row">
                                <span>✉️</span>
                                <span>
                                    <?php echo htmlspecialchars($customer["email"]); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="info-row">
                            <span>📅</span>
                            <span>
                                Last booking:
                                <?php
                                    echo date(
                                        "d M Y",
                                        strtotime($customer["last_booking"])
                                    );
                                ?>
                            </span>
                        </div>

                        <div class="booking-count">
                            <?php echo (int)$customer["total_bookings"]; ?>
                            booking<?php echo ((int)$customer["total_bookings"] > 1) ? "s" : ""; ?>
                        </div>

                    </div>

                    <div class="actions">

                        <a
                            href="chat.php?user_id=<?php echo (int)$customer["customer_id"]; ?>"
                            class="action-btn chat-btn"
                        >
                            💬 Chat
                        </a>

    
                    
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">👥</div>

            <h2>No Customers Yet</h2>

            <p>
                Customers who book your services will appear here.
            </p>

            <a href="provider-dashboard.php" class="dashboard-btn">
                ← Back to Dashboard
            </a>

        </div>

    <?php endif; ?>

</main>

</body>
</html>