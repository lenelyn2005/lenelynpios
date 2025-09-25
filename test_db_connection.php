<?php
require 'config.php';
if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error . PHP_EOL;
} else {
    echo "Connection successful" . PHP_EOL;
}
?>
