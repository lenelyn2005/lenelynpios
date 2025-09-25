<?php
// log_out.php - Simple logout redirect to main logout handler
// This file ensures compatibility with existing sidebar links

// Redirect to the proper logout handler
header("Location: logout_fixed.php");
exit;
?>
