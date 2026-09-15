<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

/* Mark notification as read */
if (isset($_GET["read"])) {
    $notification_id = (int) $_GET["read"];

    $update = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ? AND user_id = ?
    ");

    $update->bind_param("ii", $notification_id, $user_id);
    $update->execute();
    $update->close();

    header("Location: notifications.php");
    exit;
}

/* Get notifications */
$sql = "
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

/* Unread count */
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");

$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();

$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

$unread_count = (int) $count_data["unread_count"];

$count_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | KaziHub Tanzania</title>

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

        .container {
            width: 100%;
            max-width: 900px;
            margin: auto;
            padding: 25px 18px 50px;
        }

        /* Header */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            text-decoration: none;
            background: white;
            color: #172033;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }

        .header h1 {
            font-size: 26px;
            font-weight: 700;
        }

        .notification-count {
            background: #0d6efd;
            color: white;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        /* Notification card */

        .notification-card {
            background: white;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 14px;

            display: flex;
            align-items: center;
            gap: 15px;

            text-decoration: none;
            color: inherit;

            box-shadow: 0 5px 25px rgba(0,0,0,0.05);

            transition: 0.25s;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .notification-card.unread {
            border-left: 4px solid #0d6efd;
            background: #f8fbff;
        }

        .icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #eaf2ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
            flex-shrink: 0;
        }

        .content {
            flex: 1;
        }

        .content h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .content p {
            font-size: 14px;
            color: #667085;
            line-height: 1.5;
            margin-bottom: 7px;
        }

        .time {
            font-size: 12px;
            color: #98a2b3;
        }

        .right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .unread-dot {
            width: 10px;
            height: 10px;
            background: #0d6efd;
            border-radius: 50%;
        }

        .read-btn {
            font-size: 12px;
            color: #0d6efd;
            text-decoration: none;
            white-space: nowrap;
        }

        .read-btn:hover {
            text-decoration: underline;
        }

        /* Empty */

        .empty {
            background: white;
            border-radius: 20px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            font-size: 21px;
            margin-bottom: 8px;
        }

        .empty p {
            color: #667085;
            font-size: 14px;
        }

        /* Mobile */

        @media (max-width: 600px) {

            .container {
                padding: 18px 14px 40px;
            }

            .header h1 {
                font-size: 22px;
            }

            .notification-card {
                padding: 15px;
            }

            .icon {
                width: 43px;
                height: 43px;
            }

            .content h3 {
                font-size: 15px;
            }

            .content p {
                font-size: 13px;
            }

        }

    </style>
</head>

<body>

<div class="container">

    <div class="header">

        <div class="header-left">

            <a href="javascript:history.back()" class="back-btn">
                ←
            </a>

            <h1>🔔 Notifications</h1>

        </div>

        <?php if ($unread_count > 0): ?>

            <div class="notification-count">
                <?= $unread_count ?> New
            </div>

        <?php endif; ?>

    </div>


    <?php if ($result->num_rows > 0): ?>

        <?php while ($notification = $result->fetch_assoc()): ?>

            <?php

            $is_unread = (int)$notification["is_read"] === 0;

            $type = $notification["type"];

            if ($type === "booking") {
                $icon = "📅";
            } elseif ($type === "message") {
                $icon = "💬";
            } else {
                $icon = "🔔";
            }

            $time = date(
                "d M Y • h:i A",
                strtotime($notification["created_at"])
            );

            ?>

            <div class="notification-card <?= $is_unread ? 'unread' : '' ?>">

                <div class="icon">
                    <?= $icon ?>
                </div>

                <div class="content">

                    <h3>
                        <?= htmlspecialchars($notification["title"]) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($notification["message"]) ?>
                    </p>

                    <div class="time">
                        <?= $time ?>
                    </div>

                </div>

                <div class="right">

                    <?php if ($is_unread): ?>

                        <span class="unread-dot"></span>

                        <a
                            href="notifications.php?read=<?= (int)$notification["id"] ?>"
                            class="read-btn"
                        >
                            Mark as Read
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                🔔
            </div>

            <h2>No notifications yet</h2>

            <p>
                You will see booking requests and other updates here.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>