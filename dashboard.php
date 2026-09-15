<?php

session_start();

require_once "includes/config.php";

// Hakikisha user ameingia
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "User";
$role = $_SESSION["role"] ?? "customer";

$first_name = explode(" ", trim($full_name))[0];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>KaziHub Tanzania | Dashboard</title>
    <link rel="stylesheet" href="style.css">



</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        <span>KaziHub</span>
        <small>🇹🇿</small>
    </div>

    <div class="user-mini">

        <h3>Hi, <?= htmlspecialchars($first_name) ?> 👋</h3>

        <p><?= htmlspecialchars($role) ?> account</p>

    </div>

    <?php if ($role === "customer"): ?>

        <div class="menu-title">Customer</div>

        <a href="#" class="active">
            🏠 <span>Dashboard</span>
        </a>

       <a href="find-services.php" class="menu-item">
    🔎 Find Services
</a>

       <a href="providers.php">Providers</a>
       
       <a href="my-bookings.php">
    📅 My Bookings
</a>

        <a href="messages.php" class="menu-item">
    💬 <span>Messages</span>
</a>
        <a href="customer-profile.php" class="menu-item">
            👤 <span>My Profile</span>
        </a>

    <?php elseif ($role === "provider"): ?>

        <div class="menu-title">Provider</div>

        <a href="#" class="active">
            🏠 <span>Dashboard</span>
        </a>
<a href="provider-dashboard.php">
    ➕ <span>Add Service</span>
</a>

        <a href="#">
            🛠️ <span>My Services</span>
        </a>

        <a href="booking-requests.php" class="menu-item">
         📩 Booking Requests
       </a>

        <a href="my-customers.php" class="menu-item">
            👥 <span>My Customers</span>
        </a>

        <a href="messages.php" class="menu-item">
    💬 Messages
</a>

       <a href="provider-reviews.php" class="action-btn">
    ⭐ Reviews
</a>
 <a
        href="earnings.php"
        class="action-btn"
    >
        💰 Earnings
    </a>
        <a
        href="my-profile.php"
        class="action-btn"
    >
        👤 My Profile
    </a>

    <?php else: ?>

        <div class="menu-title">Admin</div>

        <a href="#" class="active">
            🏠 <span>Dashboard</span>
        </a>

        <a href="#">
            👥 <span>Users</span>
        </a>

        <a href="#">
            🛠️ <span>Services</span>
        </a>

        <a href="#">
            📊 <span>Reports</span>
        </a>

    <?php endif; ?>

   <a href="logout.php">Logout</a>

</aside>


<!-- MAIN -->

<main class="main">

    <div class="topbar">

        <div class="welcome">

            <h1>Hi, <?= htmlspecialchars($first_name) ?> 👋</h1>

            <p>
                <?= $role === "provider"
                    ? "Manage your services and grow your business."
                    : "What service do you need today?"
                ?>
            </p>

        </div>

        <div class="profile-button">

            <?= strtoupper(substr($first_name, 0, 1)) ?>

        </div>

    </div>


<?php if ($role === "customer"): ?>


    <!-- CUSTOMER SEARCH -->

    <div class="dashboard-search">

        <h3>Find a service</h3>

        <form class="search-row">

            <input
                type="text"
                placeholder="What service do you need?">

            <input
                type="text"
                placeholder="📍 Location">

            <button type="submit">
                Search
            </button>

        </form>

    </div>


    <!-- SERVICES -->

    <div class="section-title">

        <h2>Explore Services</h2>

        <a href="#">View all →</a>

    </div>


    <div class="services-grid">

        <a href="#" class="service-card">
            <div class="service-icon">🔧</div>
            <h3>Home Repairs</h3>
            <p>Electricians, plumbers & fundis</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🚗</div>
            <h3>Auto Services</h3>
            <p>Mechanics & car specialists</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">💻</div>
            <h3>IT & Technology</h3>
            <p>Developers, designers & IT experts</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🧹</div>
            <h3>Cleaning</h3>
            <p>Home & office cleaning</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">📸</div>
            <h3>Photography</h3>
            <p>Photographers & videographers</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">💄</div>
            <h3>Beauty & Wellness</h3>
            <p>Salons, makeup & beauty</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🏗️</div>
            <h3>Construction</h3>
            <p>Builders & construction experts</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">📚</div>
            <h3>Education & Tutors</h3>
            <p>Teachers & private tutors</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🚚</div>
            <h3>Moving & Transport</h3>
            <p>Transport & moving services</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🍽️</div>
            <h3>Catering & Food</h3>
            <p>Catering & food services</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">📱</div>
            <h3>Phones & Electronics</h3>
            <p>Repair & electronics experts</p>
        </a>

        <a href="#" class="service-card">
            <div class="service-icon">🎨</div>
            <h3>Design & Creative</h3>
            <p>Graphic designers & creatives</p>
        </a>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="section-title">

        <h2>Quick Actions</h2>

    </div>


    <div class="quick-grid">

        <div class="quick-card">

            <span>📅</span>

            <h3>My Bookings</h3>

            <p>View and manage your service bookings.</p>

        </div>

        <div class="quick-card">

            <span>💬</span>

            <h3>Messages</h3>

            <p>Chat with service providers.</p>

        </div>

        <div class="quick-card">

            <span>⭐</span>

            <h3>My Reviews</h3>

            <p>Manage your ratings and reviews.</p>

        </div>

    </div>


    <!-- PROVIDER CTA -->

<?php elseif ($role === "provider"): ?>


    <!-- PROVIDER STATS -->

    <div class="stats">

        <div class="stat-card">

            <span>Total Services</span>

            <h2>0</h2>

        </div>

        <div class="stat-card">

            <span>Booking Requests</span>

            <h2>0</h2>

        </div>

        <div class="stat-card">

            <span>Customers</span>

            <h2>0</h2>

        </div>

        <div class="stat-card">

            <span>Earnings</span>

            <h2>TZS 0</h2>

        </div>

    </div>


    <div class="section-title">

        <h2>Provider Actions</h2>

    </div>


    <div class="provider-actions">

        <div class="quick-card">

            <span>➕</span>

            <h3>Add a Service</h3>

            <p>Create a new service and start receiving customers.</p>

        </div>

        <div class="quick-card">

            <span>📅</span>

            <h3>Booking Requests</h3>

            <p>View customers who want to hire you.</p>

        </div>

        <div class="quick-card">

            <span>🛠️</span>

            <h3>My Services</h3>

            <p>Manage the services you offer.</p>

        </div>

        <div class="quick-card">

            <span>💬</span>

            <h3>Messages</h3>

            <p>Communicate with your customers.</p>

        </div>

        <div class="quick-card">

            <span>⭐</span>

            <h3>Reviews</h3>

            <p>See what customers say about your work.</p>

        </div>

        <div class="quick-card">

            <span>💰</span>

            <h3>Earnings</h3>

            <p>Track your income from KaziHub.</p>

        </div>

    </div>


<?php else: ?>


    <!-- ADMIN -->

    <div class="provider-banner">

        <div>

            <h2>Admin Dashboard</h2>

            <p>
                Manage KaziHub users, services and platform activity.
            </p>

        </div>

    </div>


<?php endif; ?>

</main>

</body>

</html>