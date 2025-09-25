<?php
require 'config.php';
$result = $mysqli->query('DESCRIBE subjects');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . PHP_EOL;
    }
} else {
    echo 'Query failed: ' . $mysqli->error . PHP_EOL;
}
?>
