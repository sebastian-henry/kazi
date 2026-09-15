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
   TOTAL EARNINGS
   Completed bookings only
========================================= */
$total_earnings = 0;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(ps.price), 0) AS total
    FROM bookings b
    INNER JOIN provider_services ps
        ON b.provider_service_id = ps.id
    WHERE b.provider_id = ?
    AND b.status = 'completed'
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_earnings = (float)($row["total"] ?? 0);
$stmt->close();
/* =========================================
   PENDING EARNINGS
========================================= */
$pending_earnings = 0;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(ps.price), 0) AS total
    FROM bookings b
    INNER JOIN provider_services ps
        ON b.provider_service_id = ps.id
    WHERE b.provider_id = ?
    AND b.status IN ('pending', 'accepted')
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$pending_earnings = (float)($row["total"] ?? 0);
$stmt->close();
/* =========================================
   COMPLETED BOOKINGS
========================================= */
$completed_bookings = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE provider_id = ?
    AND status = 'completed'
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$completed_bookings = (int)($row["total"] ?? 0);
$stmt->close();
/* =========================================
   TOTAL BOOKINGS
========================================= */
$total_bookings = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE provider_id = ?
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_bookings = (int)($row["total"] ?? 0);
$stmt->close();
/* =========================================
   EARNINGS HISTORY
========================================= */
$history = [];
$stmt = $conn->prepare("
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.status,
        b.created_at,
        ps.price,
        ps.price_type,
        s.service_name,
        u.full_name AS customer_name
    FROM bookings b
    INNER JOIN provider_services ps
        ON b.provider_service_id = ps.id
    INNER JOIN services s
        ON ps.service_id = s.id
    INNER JOIN users u
        ON b.customer_id = u.id
    WHERE b.provider_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();
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
    Earnings | KaziHub Tanzania
</title>
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
    .container {
        max-width: 1150px;
        margin: auto;
        padding: 30px 20px;
    }
    /* =========================================
       HEADER
    ========================================= */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    .header h1 {
        margin: 0 0 8px;
        font-size: 30px;
    }
    .header p {
        margin: 0;
        color: #64748b;
    }
    .back-btn {
        text-decoration: none;
        background: white;
        color: #0f172a;
        padding: 12px 18px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        font-weight: bold;
        transition: 0.25s;
    }
    .back-btn:hover {
        transform: translateY(-2px);
        border-color: #2563eb;
        color: #2563eb;
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
        padding: 24px;
        border-radius: 18px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .stat-icon {
        font-size: 28px;
        margin-bottom: 12px;
    }
    .stat-card h2 {
        margin: 0;
        font-size: 25px;
    }
    .stat-card p {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 14px;
    }
    .earnings-card h2 {
        color: #16a34a;
    }
    .pending-card h2 {
        color: #d97706;
    }
    /* =========================================
       HISTORY
    ========================================= */
    .section {
        background: white;
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .section-header h2 {
        margin: 0;
    }
    .table-wrapper {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 750px;
    }
    th {
        text-align: left;
        padding: 14px;
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
    }
    td {
        padding: 16px 14px;
        border-bottom: 1px solid #eef2f7;
        font-size: 14px;
    }
    tr:last-child td {
        border-bottom: none;
    }
    .service-name {
        font-weight: bold;
        color: #0f172a;
    }
    .customer {
        color: #475569;
    }
    .amount {
        font-weight: bold;
        color: #16a34a;
    }
    /* =========================================
       STATUS
    ========================================= */
    .status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-completed {
        background: #dcfce7;
        color: #166534;
    }
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    .status-accepted {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .status-rejected,
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }
    /* =========================================
       EMPTY
    ========================================= */
    .empty {
        text-align: center;
        padding: 50px 20px;
    }
    .empty-icon {
        font-size: 50px;
        margin-bottom: 15px;
    }
    .empty h3 {
        margin: 0 0 8px;
    }
    .empty p {
        color: #64748b;
        margin: 0;
    }
    /* =========================================
       NOTE
    ========================================= */
    .note {
        margin-top: 20px;
        padding: 15px 18px;
        background: #eff6ff;
        border-radius: 12px;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.5;
    }
    /* =========================================
       RESPONSIVE
    ========================================= */
    @media (max-width: 900px) {
        .stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        .container {
            padding: 20px 15px;
        }
        .header h1 {
            font-size: 24px;
        }
        .stats {
            grid-template-columns: 1fr;
        }
        .section {
            padding: 18px;
        }
    }
</style>
</head>
<body>
<div class="container">
<!-- HEADER -->
<div class="header">
    <div>
        <h1>
            💰 My Earnings
        </h1>
        <p>
            Karibu <?= htmlspecialchars($full_name) ?>,
            hapa unaweza kuona mapato yako.
        </p>
    </div>
    <a
        href="provider-dashboard.php"
        class="back-btn"
    >
        ← Back to Dashboard
    </a>
</div>
<!-- STATS -->
<div class="stats">
    <div class="stat-card earnings-card">
        <div class="stat-icon">
            💰
        </div>
        <h2>
            TSh <?= number_format($total_earnings) ?>
        </h2>
        <p>
            Completed Earnings
        </p>
    </div>
    <div class="stat-card pending-card">
        <div class="stat-icon">
            ⏳
        </div>
        <h2>
            TSh <?= number_format($pending_earnings) ?>
        </h2>
        <p>
            Pending Earnings
        </p>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            ✅
        </div>
        <h2>
            <?= $completed_bookings ?>
        </h2>
        <p>
            Completed Bookings
        </p>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            📊
        </div>
        <h2>
            <?= $total_bookings ?>
        </h2>
        <p>
            Total Bookings
        </p>
    </div>
</div>
<!-- HISTORY -->
<div class="section">
    <div class="section-header">
        <h2>
            Earnings History
        </h2>
    </div>
    <?php if (empty($history)): ?>
        <div class="empty">
            <div class="empty-icon">
                💰
            </div>
            <h3>
                No earnings yet
            </h3>
            <p>
                Booking zakozofanywa na kukamilika
                zitaonekana hapa.
            </p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>
                            Service
                        </th>
                        <th>
                            Customer
                        </th>
                        <th>
                            Date
                        </th>
                        <th>
                            Amount
                        </th>
                        <th>
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $booking): ?>
                        <?php
                        $status = strtolower(
                            $booking["status"] ?? ""
                        );
                        ?>
                        <tr>
                            <td>
                                <div class="service-name">
                                    <?= htmlspecialchars(
                                        $booking["service_name"]
                                    ) ?>
                                </div>
                            </td>
                            <td>
                                <div class="customer">
                                    <?= htmlspecialchars(
                                        $booking["customer_name"]
                                    ) ?>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $booking["booking_date"]
                                ) ?>
                                <br>
                                <small>
                                    <?= htmlspecialchars(
                                        date(
                                            "H:i",
                                            strtotime(
                                                $booking["booking_time"]
                                            )
                                        )
                                    ) ?>
                                </small>
                            </td>
                            <td>
                                <?php if (
                                    $booking["price"] !== null
                                ): ?>
                                    <span class="amount">
                                        TSh
                                        <?= number_format(
                                            (float)$booking["price"]
                                        ) ?>
                                    </span>
                                <?php else: ?>
                                    <span>
                                        Negotiable
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span
                                    class="status status-<?= htmlspecialchars(
                                        $status
                                    ) ?>"
                                >
                                    <?php
                                    if ($status === "completed") {
                                        echo "✓ Completed";
                                    } elseif ($status === "accepted") {
                                        echo "✓ Accepted";
                                    } elseif ($status === "pending") {
                                        echo "⏳ Pending";
                                    } elseif ($status === "rejected") {
                                        echo "✕ Rejected";
                                    } elseif ($status === "cancelled") {
                                        echo "✕ Cancelled";
                                    } else {
                                        echo ucfirst($status);
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="note">
        💡 <strong>Note:</strong>
        Completed Earnings zinaonyesha jumla ya bei
        za bookings ambazo provider amemaliza.
        Pending Earnings zinaonyesha bookings ambazo
        bado zinasubiri au zimekubaliwa lakini hazijakamilika.
    </div>
</div>
</div>
</body>
</html>