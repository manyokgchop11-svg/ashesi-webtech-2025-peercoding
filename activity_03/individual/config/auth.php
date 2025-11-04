
<?php
// include this at top of pages that need authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    // not logged in
    header("Location: /individual/index.html");
    exit;
}
?>
