<?php
session_start();
// Remove all session data
session_unset();
// Destroy the session
session_destroy();
// Send user back to Home
header("Location: index.php");
exit;
?>