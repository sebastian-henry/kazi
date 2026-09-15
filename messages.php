<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$full_name = $_SESSION["full_name"] ?? "User";
$role = $_SESSION["role"] ?? "customer";

/*
|--------------------------------------------------------------------------
| GET ALL PEOPLE WITH CONVERSATIONS
| AND SHOW THE LATEST MESSAGE
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    u.id,
    u.full_name,
    u.phone,
    u.role,

    m.message AS last_message,
    m.created_at AS last_message_time,
    m.sender_id AS last_sender_id

FROM users u

INNER JOIN messages m
    ON m.id = (
        SELECT MAX(m2.id)
        FROM messages m2
        WHERE
            (m2.sender_id = ? AND m2.receiver_id = u.id)
            OR
            (m2.receiver_id = ? AND m2.sender_id = u.id)
    )

WHERE u.id != ?

ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "iii",
    $user_id,
    $user_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();


// Format time
function formatMessageTime($time)
{
    if (!$time) {
        return "";
    }

    $timestamp = strtotime($time);

    if (date("Y-m-d") === date("Y-m-d", $timestamp)) {
        return date("H:i", $timestamp);
    }

    if (date("Y-m-d", strtotime("-1 day")) === date("Y-m-d", $timestamp)) {
        return "Yesterday";
    }

    return date("d/m/Y", $timestamp);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Messages - KaziHub Tanzania</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {

    font-family: Arial, sans-serif;

    background: #f1f5f9;

    color: #172033;

}


/* TOP BAR */

.topbar {

    background: #0f172a;

    color: white;

    padding: 16px 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

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


/* CONTAINER */

.container {

    max-width: 850px;

    margin: 35px auto;

    padding: 0 15px;

}


/* HEADING */

.heading {

    margin-bottom: 20px;

}

.heading h1 {

    font-size: 28px;

    margin-bottom: 6px;

}

.heading p {

    color: #64748b;

}


/* MESSAGE BOX */

.messages-box {

    background: white;

    border-radius: 16px;

    overflow: hidden;

    box-shadow: 0 8px 30px rgba(0,0,0,0.08);

}


/* HEADER */

.messages-header {

    padding: 18px 20px;

    border-bottom: 1px solid #e5e7eb;

    font-size: 18px;

    font-weight: bold;

}


/* USER CARD */

.user-card {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 15px 18px;

    border-bottom: 1px solid #eef2f7;

    text-decoration: none;

    color: inherit;

    transition: 0.2s;

}

.user-card:hover {

    background: #f8fafc;

}


/* AVATAR */

.avatar {

    width: 52px;

    height: 52px;

    min-width: 52px;

    border-radius: 50%;

    background: linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    font-weight: bold;

}


/* USER CONTENT */

.user-content {

    flex: 1;

    min-width: 0;

}


/* NAME ROW */

.name-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 10px;

}

.user-name {

    font-size: 16px;

    font-weight: bold;

    color: #111827;

}

.message-time {

    font-size: 12px;

    color: #94a3b8;

    white-space: nowrap;

}


/* ROLE */

.user-role {

    color: #64748b;

    font-size: 12px;

    margin-top: 3px;

}


/* MESSAGE PREVIEW */

.message-preview {

    margin-top: 6px;

    font-size: 14px;

    color: #64748b;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

    max-width: 500px;

}


/* MY MESSAGE */

.my-message {

    color: #94a3b8;

}


/* OTHER MESSAGE */

.other-message {

    color: #334155;

    font-weight: 600;

}


/* CHAT BUTTON */

.chat-btn {

    background: #2563eb;

    color: white;

    border: none;

    padding: 9px 14px;

    border-radius: 8px;

    font-size: 13px;

    cursor: pointer;

}

.chat-btn:hover {

    background: #1d4ed8;

}


/* EMPTY */

.empty {

    padding: 70px 20px;

    text-align: center;

    color: #64748b;

}

.empty-icon {

    font-size: 55px;

    margin-bottom: 15px;

}

.empty h3 {

    color: #334155;

    margin-bottom: 8px;

}


/* MOBILE */

@media (max-width: 600px) {

    .topbar {

        padding: 14px 15px;

    }

    .logo {

        font-size: 19px;

    }

    .back-btn {

        padding: 8px 12px;

        font-size: 13px;

    }

    .container {

        margin-top: 20px;

        padding: 0 10px;

    }

    .heading h1 {

        font-size: 24px;

    }

    .user-card {

        padding: 14px 12px;

        gap: 12px;

    }

    .avatar {

        width: 46px;

        height: 46px;

        min-width: 46px;

    }

    .chat-btn {

        display: none;

    }

    .message-preview {

        max-width: 210px;

    }

}

</style>

</head>


<body>


<!-- TOP BAR -->

<div class="topbar">

    <div class="logo">
        KaziHub 🇹🇿
    </div>

    <a href="dashboard.php" class="back-btn">
        ← Dashboard
    </a>

</div>


<!-- MAIN -->

<div class="container">


    <div class="heading">

        <h1>
            💬 Messages
        </h1>

        <p>
            Wasiliana na customers na providers kupitia KaziHub.
        </p>

    </div>


    <div class="messages-box">


        <div class="messages-header">

            Conversations

        </div>


        <?php if (empty($users)): ?>


            <div class="empty">

                <div class="empty-icon">
                    💬
                </div>

                <h3>
                    No messages yet
                </h3>

                <p>
                    Ukianza kuwasiliana na mtu,
                    conversation itaonekana hapa.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($users as $user): ?>


                <?php

                $initial = strtoupper(
                    substr(
                        trim($user["full_name"]),
                        0,
                        1
                    )
                );

                $lastMessage = trim(
                    $user["last_message"] ?? ""
                );

                if (strlen($lastMessage) > 60) {

                    $lastMessage =
                        substr($lastMessage, 0, 60)
                        . "...";

                }

                $time = formatMessageTime(
                    $user["last_message_time"]
                );

                ?>


                <a
                    href="chat.php?user_id=<?= (int)$user["id"] ?>"
                    class="user-card"
                >


                    <!-- AVATAR -->

                    <div class="avatar">

                        <?= htmlspecialchars($initial) ?>

                    </div>


                    <!-- MESSAGE CONTENT -->

                    <div class="user-content">


                        <div class="name-row">


                            <div>

                                <div class="user-name">

                                    <?= htmlspecialchars(
                                        $user["full_name"]
                                    ) ?>

                                </div>

                                <div class="user-role">

                                    <?= ucfirst(
                                        htmlspecialchars(
                                            $user["role"]
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="message-time">

                                <?= htmlspecialchars($time) ?>

                            </div>


                        </div>


                        <?php if ($lastMessage !== ""): ?>


                            <div
                                class="message-preview
                                <?= $user["last_sender_id"] == $user_id
                                    ? "my-message"
                                    : "other-message"
                                ?>"
                            >

                                <?php if (
                                    $user["last_sender_id"] == $user_id
                                ): ?>

                                    You:
                                    
                                <?php endif; ?>


                                <?= htmlspecialchars(
                                    $lastMessage
                                ) ?>

                            </div>


                        <?php else: ?>


                            <div class="message-preview">

                                Start conversation...

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- CHAT BUTTON -->

                    <button
                        type="button"
                        class="chat-btn"
                    >

                        Chat →

                    </button>


                </a>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</div>


</body>

</html>