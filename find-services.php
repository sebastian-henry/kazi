<?php

session_start();
require_once "includes/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* =========================
   SEARCH VALUES
========================= */

$search = trim($_GET["search"] ?? "");
$location = trim($_GET["location"] ?? "");
$category = trim($_GET["category"] ?? "");

/* =========================
   CATEGORIES
========================= */

$category_sql = "SELECT DISTINCT category
                 FROM services
                 WHERE category IS NOT NULL
                 AND category != ''
                 ORDER BY category ASC";

$category_result = $conn->query($category_sql);

$categories = [];

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row["category"];
    }
}

/* =========================
   MAIN SERVICES QUERY
========================= */

$sql = "
SELECT
    s.id AS service_id,
    s.service_name,
    s.description AS service_description,
    s.category,

    ps.id AS provider_service_id,
    ps.provider_id,
    ps.price,
    ps.price_type,
    ps.service_description AS provider_description,
    ps.location AS provider_location,
    ps.availability,

    u.full_name AS provider_name,
    u.phone AS provider_phone,

    pp.business_name,
    pp.profile_image,
    pp.rating,
    pp.total_reviews,
    pp.is_verified

FROM services s

LEFT JOIN provider_services ps
    ON s.id = ps.service_id

LEFT JOIN users u
    ON ps.provider_id = u.id

LEFT JOIN provider_profiles pp
    ON ps.provider_id = pp.provider_id

WHERE 1=1
";

$params = [];
$types = "";

/* =========================
   SEARCH
========================= */

if ($search !== "") {

    $sql .= "
        AND (
            s.service_name LIKE ?
            OR s.description LIKE ?
            OR s.category LIKE ?
            OR u.full_name LIKE ?
            OR pp.business_name LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}

/* =========================
   LOCATION
========================= */

if ($location !== "") {

    $sql .= "
        AND (
            ps.location LIKE ?
            OR u.location LIKE ?
            OR pp.location LIKE ?
        )
    ";

    $location_value = "%" . $location . "%";

    $params[] = $location_value;
    $params[] = $location_value;
    $params[] = $location_value;

    $types .= "sss";
}

/* =========================
   CATEGORY
========================= */

if ($category !== "") {

    $sql .= " AND s.category = ? ";

    $params[] = $category;
    $types .= "s";
}

/* =========================
   ORDER
========================= */

$sql .= "
    ORDER BY
        CASE
            WHEN ps.id IS NOT NULL THEN 0
            ELSE 1
        END,
        s.service_name ASC
";

/* =========================
   EXECUTE
========================= */

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query error: " . $conn->error);
}

if (!empty($params)) {

    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$services = [];

while ($row = $result->fetch_assoc()) {

    $services[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Find Services | KaziHub Tanzania</title>

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
            background: #ffffff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            text-decoration: none;
            color: #172033;
        }

        .logo span {
            color: #2563eb;
        }

        .dashboard-btn {
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: 600;
        }

        .container {
            width: 86%;
            max-width: 1250px;
            margin: 35px auto;
        }

        .heading {
            margin-bottom: 25px;
        }

        .heading h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .heading p {
            color: #667085;
        }

        .search-box {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1.5fr auto;
            gap: 12px;
        }

        .search-form input,
        .search-form select {
            width: 100%;
            padding: 14px;
            border: 1px solid #d9dee8;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #2563eb;
        }

        .search-btn {
            border: none;
            background: #2563eb;
            color: white;
            padding: 0 25px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .results-header h2 {
            font-size: 22px;
        }

        .results-count {
            color: #667085;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .service-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            transition: 0.25s;
            border: 1px solid #edf0f5;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.10);
        }

        .service-icon {
            width: 50px;
            height: 50px;
            background: #eff6ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 15px;
        }

        .service-card h3 {
            font-size: 20px;
            margin-bottom: 7px;
        }

        .category {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .description {
            color: #667085;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .provider {
            background: #f8fafc;
            padding: 13px;
            border-radius: 9px;
            margin-top: 12px;
        }

        .provider-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .verified {
            color: #2563eb;
            font-size: 13px;
        }

        .rating {
            color: #f59e0b;
            font-size: 14px;
        }

        .info {
            margin-top: 10px;
            color: #475569;
            font-size: 14px;
            line-height: 1.8;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #16a34a;
            margin-top: 12px;
        }

        .not-available {
            color: #64748b;
            font-size: 14px;
            margin-top: 10px;
        }

        .available {
            color: #16a34a;
            font-weight: 600;
        }

        .busy {
            color: #f59e0b;
            font-weight: 600;
        }

        .unavailable {
            color: #dc2626;
            font-weight: 600;
        }

        .buttons {
            display: flex;
            gap: 8px;
            margin-top: 18px;
        }

        .btn {
            flex: 1;
            text-align: center;
            text-decoration: none;
            padding: 11px 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-profile {
            background: #eef2ff;
            color: #3730a3;
        }

        .btn-chat {
            background: #ecfdf5;
            color: #047857;
        }

        .btn-book {
            background: #2563eb;
            color: white;
        }

        .no-results {
            background: white;
            padding: 50px 20px;
            text-align: center;
            border-radius: 14px;
        }

        .no-results h2 {
            margin-bottom: 10px;
        }

        .no-results p {
            color: #667085;
        }

        @media (max-width: 950px) {

            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .search-form {
                grid-template-columns: 1fr 1fr;
            }

            .search-btn {
                padding: 14px;
            }
        }

        @media (max-width: 650px) {

            .topbar {
                padding: 15px 5%;
            }

            .container {
                width: 92%;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .heading h1 {
                font-size: 26px;
            }

            .buttons {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<!-- TOP BAR -->

<header class="topbar">

    <div class="logo">
        <span>KaziHub</span>
        <small>🇹🇿</small>
    </div>

    <a href="dashboard.php" class="dashboard-btn">
        Dashboard
    </a>

</header>


<div class="container">

    <!-- HEADING -->

    <div class="heading">

        <h1>Find Services 🔎</h1>

        <p>
            Search for services and professionals across Tanzania.
        </p>

    </div>


    <!-- SEARCH -->

    <div class="search-box">

        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search service... e.g. Electrician"
            >

            <input
                type="text"
                name="location"
                value="<?= htmlspecialchars($location) ?>"
                placeholder="📍 Location e.g. Dar es Salaam"
            >

            <select name="category">

                <option value="">
                    All Categories
                </option>

                <?php foreach ($categories as $cat): ?>

                    <option
                        value="<?= htmlspecialchars($cat) ?>"
                        <?= $category === $cat ? "selected" : "" ?>
                    >
                        <?= htmlspecialchars($cat) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit" class="search-btn">
                Search
            </button>

        </form>

    </div>


    <!-- RESULTS -->

    <div class="results-header">

        <h2>Available Services</h2>

        <span class="results-count">
            <?= count($services) ?> result(s)
        </span>

    </div>


    <?php if (count($services) > 0): ?>

        <div class="services-grid">

            <?php foreach ($services as $service): ?>

                <div class="service-card">

                    <div class="service-icon">
                        🛠️
                    </div>

                    <span class="category">
                        <?= htmlspecialchars($service["category"]) ?>
                    </span>

                    <h3>
                        <?= htmlspecialchars($service["service_name"]) ?>
                    </h3>

                    <p class="description">

                        <?php

                        $description =
                            $service["provider_description"]
                            ?: $service["service_description"];

                        if (!$description) {
                            $description =
                                "Professional service available through KaziHub Tanzania.";
                        }

                        echo htmlspecialchars($description);

                        ?>

                    </p>


                    <?php if (!empty($service["provider_id"])): ?>

                        <!-- PROVIDER EXISTS -->

                        <div class="provider">

                            <div class="provider-name">

                                👨‍🔧

                                <?= htmlspecialchars(
                                    $service["business_name"]
                                    ?: $service["provider_name"]
                                ) ?>

                                <?php if ($service["is_verified"]): ?>

                                    <span class="verified">
                                        ✓ Verified
                                    </span>

                                <?php endif; ?>

                            </div>


                            <?php if ((float)$service["rating"] > 0): ?>

                                <div class="rating">

                                    ⭐ <?= number_format(
                                        (float)$service["rating"],
                                        1
                                    ) ?>

                                    (<?= (int)$service["total_reviews"] ?> reviews)

                                </div>

                            <?php else: ?>

                                <div class="rating">
                                    ⭐ New provider
                                </div>

                            <?php endif; ?>


                            <div class="info">

                                💰

                                <?php if ($service["price"] !== null): ?>

                                    TSh <?= number_format(
                                        (float)$service["price"]
                                    ) ?>

                                    <?php if ($service["price_type"]): ?>

                                        /
                                        <?= htmlspecialchars(
                                            $service["price_type"]
                                        ) ?>

                                    <?php endif; ?>

                                <?php else: ?>

                                    Price negotiable

                                <?php endif; ?>


                                <br>


                                📍

                                <?= htmlspecialchars(
                                    $service["provider_location"]
                                    ?: "Location not set"
                                ) ?>


                                <br>


                                <?php

                                $availability =
                                    $service["availability"];

                                if ($availability === "available"):

                                ?>

                                    <span class="available">
                                        🟢 Available
                                    </span>

                                <?php elseif ($availability === "busy"): ?>

                                    <span class="busy">
                                        🟠 Busy
                                    </span>

                                <?php else: ?>

                                    <span class="unavailable">
                                        🔴 Unavailable
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- BUTTONS -->

                        <div class="buttons">

                            <a
                                href="provider-profile.php?provider_id=<?= (int)$service["provider_id"] ?>"
                                class="btn btn-profile"
                            >
                                View Provider
                            </a>

                            <a
                                href="chat.php?user_id=<?= (int)$service["provider_id"] ?>"
                                class="btn btn-chat"
                            >
                                💬 Chat
                            </a>

                            <?php if (!empty($service["provider_service_id"])): ?>

                                <a
                                    href="book-service.php?provider_service_id=<?= (int)$service["provider_service_id"] ?>"
                                    class="btn btn-book"
                                >
                                    📅 Book
                                </a>

                            <?php endif; ?>

                        </div>


                    <?php else: ?>

                        <!-- NO PROVIDER YET -->

                        <div class="not-available">

                            👨‍🔧 No provider has added this service yet.

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>


    <?php else: ?>

        <div class="no-results">

            <h2>No services found 😔</h2>

            <p>
                Try another service name, category or location.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>

</html>