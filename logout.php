<?php
require_once 'session.php';

// Clear all session data
clearUserSession();

// Redirect to login page
header("Location: index.php?msg=logged_out");
exit();
?> 