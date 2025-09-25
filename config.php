<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'cpaneluser_dbuser');
define('DB_PASSWORD', 'StrongPasswordHere');
define('DB_NAME', 'cpaneluser_college_scheduling');

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}
?>
