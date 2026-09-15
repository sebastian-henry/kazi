<?php
session_start();
require_once "includes/config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$current_user_id = (int) $_SESSION["user_id"];
$current_user_name = $_SESSION["full_name"] ?? "User";
$other_user_id = (int) ($_GET["user_id"] ?? 0);
if ($other_user_id <= 0 || $other_user_id === $current_user_id) {
    header("Location: messages.php");
    exit;
}
/* =========================
   GET OTHER USER + PROFILE
========================= */
$sql = "
    SELECT
        u.id,
        u.full_name,
        u.phone,
        u.role,
        u.profile_image AS user_image,
        p.profile_image AS provider_image,
        p.business_name
    FROM users u
    LEFT JOIN provider_profiles p
        ON u.id = p.provider_id
    WHERE u.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$result = $stmt->get_result();
$other_user = $result->fetch_assoc();
$stmt->close();
if (!$other_user) {
    header("Location: messages.php");
    exit;
}
/* =========================
   CHOOSE PROFILE IMAGE
========================= */
$profile_image = "";
if (!empty($other_user["provider_image"])) {
    $profile_image = $other_user["provider_image"];
} elseif (!empty($other_user["user_image"])) {
    $profile_image = $other_user["user_image"];
}
/* Check if image really exists */
$has_profile_image = false;
if (
    !empty($profile_image) &&
    strpos($profile_image, "uploads/") === 0 &&
    file_exists(__DIR__ . "/" . $profile_image)
) {
    $has_profile_image = true;
}
/* =========================
   AVATAR LETTER
========================= */
$avatar_letter = strtoupper(
    substr(
        trim($other_user["full_name"]),
        0,
        1
    )
);
/* =========================
   SEND MESSAGE
========================= */
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = trim($_POST["message"] ?? "");
    if ($message !== "") {
        $sql = "
            INSERT INTO messages
            (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "iis",
                $current_user_id,
                $other_user_id,
                $message
            );
            if ($stmt->execute()) {
                $stmt->close();
                header(
                    "Location: chat.php?user_id=" .
                    $other_user_id
                );
                exit;
            } else {
                $error = "Ujumbe haukutumwa.";
            }
            $stmt->close();
        } else {
            $error = "Kuna tatizo kwenye mfumo.";
        }
    } else {
        $error = "Andika ujumbe kwanza.";
    }
}
/* =========================
   MARK MESSAGES AS READ
========================= */
$sql = "
    UPDATE messages
    SET is_read = TRUE
    WHERE sender_id = ?
    AND receiver_id = ?
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param(
        "ii",
        $other_user_id,
        $current_user_id
    );
    $stmt->execute();
    $stmt->close();
}
/* =========================
   GET CONVERSATION
========================= */
$sql = "
    SELECT
        m.id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.created_at
    FROM messages m
    WHERE
        (m.sender_id = ? AND m.receiver_id = ?)
        OR
        (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iiii",
    $current_user_id,
    $other_user_id,
    $other_user_id,
    $current_user_id
);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
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
    <?= htmlspecialchars($other_user["full_name"]) ?>
    - KaziHub
</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    background: #eef2f7;
    height: 100vh;
    overflow: hidden;
    color: #172033;
}
/* =========================
   TOP BAR
========================= */
.topbar {
    height: 72px;
    background: #0f172a;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    position: relative;
    z-index: 10;
}
.user-header {
    display: flex;
    align-items: center;
    gap: 12px;
}
/* =========================
   TOP PROFILE IMAGE
========================= */
.avatar {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 50%;
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    position: relative;
    overflow: hidden;
}
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* online dot */
.avatar::after {
    content: "";
    position: absolute;
    width: 11px;
    height: 11px;
    background: #22c55e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    right: 0;
    bottom: 0;
}
.user-name {
    font-size: 16px;
    font-weight: bold;
}
.user-role {
    font-size: 12px;
    color: #cbd5e1;
    margin-top: 3px;
}
.back-btn {
    color: white;
    text-decoration: none;
    background: #2563eb;
    padding: 9px 15px;
    border-radius: 9px;
    font-size: 14px;
    transition: 0.2s;
}
.back-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
/* =========================
   CHAT CONTAINER
========================= */
.chat-container {
    max-width: 950px;
    height: calc(100vh - 92px);
    margin: 10px auto;
    background: white;
    border-radius: 18px;
    box-shadow:
        0 10px 35px
        rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
/* =========================
   MESSAGES AREA
========================= */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
    background:
        radial-gradient(
            circle at top left,
            rgba(37,99,235,0.04),
            transparent 35%
        ),
        #f8fafc;
    scroll-behavior: smooth;
}
.chat-messages::-webkit-scrollbar {
    width: 6px;
}
.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 20px;
}
/* =========================
   MESSAGE ROW
========================= */
.message-row {
    display: flex;
    margin-bottom: 14px;
    align-items: flex-end;
    gap: 8px;
}
.message-row.mine {
    justify-content: flex-end;
}
/* =========================
   SMALL PROFILE IMAGE
========================= */
.message-avatar {
    width: 30px;
    height: 30px;
    min-width: 30px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    color: #475569;
    overflow: hidden;
}
.message-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
/* =========================
   MESSAGE BUBBLE
========================= */
.message {
    max-width: 70%;
    padding: 10px 14px 8px;
    border-radius: 18px;
    background: #e2e8f0;
    color: #172033;
    box-shadow:
        0 2px 5px
        rgba(0,0,0,0.04);
    animation: messageIn 0.2s ease;
}
.message-row.mine .message {
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );
    color: white;
    border-bottom-right-radius: 5px;
}
.message-row:not(.mine) .message {
    border-bottom-left-radius: 5px;
}
@keyframes messageIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* =========================
   MESSAGE TEXT
========================= */
.message-text {
    font-size: 15px;
    line-height: 1.45;
    word-wrap: break-word;
    white-space: normal;
}
/* =========================
   TIME
========================= */
.message-time {
    font-size: 10px;
    margin-top: 5px;
    opacity: 0.65;
    text-align: right;
}
/* =========================
   EMPTY CHAT
========================= */
.empty-chat {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    text-align: center;
}
.empty-chat-icon {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    margin-bottom: 15px;
}
.empty-chat h3 {
    color: #1e293b;
    margin-bottom: 7px;
}
.empty-chat p {
    font-size: 14px;
}
/* =========================
   ERROR
========================= */
.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 10px 15px;
    margin: 8px 20px;
    border-radius: 8px;
    font-size: 14px;
}
/* =========================
   MESSAGE FORM
========================= */
.message-form {
    padding: 14px 16px;
    border-top: 1px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    gap: 10px;
}
.message-input {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 25px;
    padding: 13px 18px;
    outline: none;
    font-size: 15px;
    background: #f8fafc;
    transition: 0.2s;
}
.message-input:focus {
    border-color: #2563eb;
    background: white;
    box-shadow:
        0 0 0 3px
        rgba(37,99,235,0.08);
}
.send-btn {
    width: 50px;
    height: 50px;
    border: none;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}
.send-btn:hover {
    background: #1d4ed8;
    transform: scale(1.05);
}
.send-btn:active {
    transform: scale(0.95);
}
/* =========================
   MOBILE
========================= */
@media (max-width: 600px) {
    body {
        background: white;
    }
    .topbar {
        height: 68px;
        padding: 0 12px;
    }
    .avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
    }
    .user-name {
        font-size: 15px;
    }
    .back-btn {
        padding: 8px 10px;
        font-size: 12px;
    }
    .chat-container {
        margin: 0;
        width: 100%;
        height: calc(100vh - 68px);
        border-radius: 0;
        box-shadow: none;
    }
    .chat-messages {
        padding: 15px 12px;
    }
    .message {
        max-width: 82%;
        padding: 9px 13px 7px;
    }
    .message-text {
        font-size: 14px;
    }
    .message-form {
        padding: 9px;
    }
    .message-input {
        padding: 12px 15px;
        font-size: 14px;
    }
    .send-btn {
        width: 46px;
        height: 46px;
    }
    .message-avatar {
        width: 27px;
        height: 27px;
        min-width: 27px;
    }
}
</style>
</head>
<body>
<!-- =========================
     HEADER
========================= -->
<div class="topbar">
    <div class="user-header">
        <div class="avatar">
            <?php if ($has_profile_image): ?>
                <img
                    src="<?= htmlspecialchars($profile_image) ?>"
                    alt="<?= htmlspecialchars($other_user["full_name"]) ?>"
                >
            <?php else: ?>
                <?= htmlspecialchars($avatar_letter) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="user-name">
                <?= htmlspecialchars(
                    $other_user["full_name"]
                ) ?>
            </div>
            <div class="user-role">
                <?= ucfirst(
                    htmlspecialchars(
                        $other_user["role"]
                    )
                ) ?>
                • Online
            </div>
        </div>
    </div>
    <a
        href="messages.php"
        class="back-btn"
    >
        ← Messages
    </a>
</div>
<!-- =========================
     CHAT
========================= -->
<div class="chat-container">
    <div
        class="chat-messages"
        id="chatMessages"
    >
        <?php if (empty($messages)): ?>
            <div class="empty-chat">
                <div class="empty-chat-icon">
                    💬
                </div>
                <h3>
                    Start a conversation
                </h3>
                <p>
                    Tuma ujumbe kwa
                    <strong>
                        <?= htmlspecialchars(
                            $other_user["full_name"]
                        ) ?>
                    </strong>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                $is_mine =
                    ((int)$msg["sender_id"]
                    === $current_user_id);
                ?>
                <div
                    class="message-row
                    <?= $is_mine ? 'mine' : '' ?>"
                >
                    <?php if (!$is_mine): ?>
                        <div class="message-avatar">
                            <?php if ($has_profile_image): ?>
                                <img
                                    src="<?= htmlspecialchars($profile_image) ?>"
                                    alt="<?= htmlspecialchars($other_user["full_name"]) ?>"
                                >
                            <?php else: ?>
                                <?= htmlspecialchars(
                                    $avatar_letter
                                ) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="message">
                        <div class="message-text">
                            <?= nl2br(
                                htmlspecialchars(
                                    $msg["message"]
                                )
                            ) ?>
                        </div>
                        <div class="message-time">
                            <?= date(
                                "H:i",
                                strtotime(
                                    $msg["created_at"]
                                )
                            ) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <!-- SEND MESSAGE -->
    <form
        method="POST"
        class="message-form"
    >
        <input
            type="text"
            name="message"
            id="messageInput"
            class="message-input"
            placeholder="Andika ujumbe..."
            autocomplete="off"
            required
        >
        <button
            type="submit"
            class="send-btn"
            title="Send message"
        >
            ➤
        </button>
    </form>
</div>
<script>
/* =========================
   AUTO SCROLL
========================= */
const chatMessages =
    document.getElementById("chatMessages");
if (chatMessages) {
    chatMessages.scrollTop =
        chatMessages.scrollHeight;
}
/* =========================
   ENTER TO SEND
========================= */
const messageInput =
    document.getElementById("messageInput");
if (messageInput) {
    messageInput.addEventListener(
        "keydown",
        function(event) {
            if (
                event.key === "Enter" &&
                !event.shiftKey
            ) {
                event.preventDefault();
                const form =
                    messageInput.closest("form");
                if (
                    messageInput.value.trim() !== ""
                ) {
                    form.submit();
                }
            }
        }
    );
}
</script>
</body>
</html>