<?php
// Unified Gateway: Redirects to primary portal login form
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: index.php' . $qs);
exit;
