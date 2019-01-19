<?php
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);

$mysqli = new mysqli("localhost", "root", "root", "sakila", 3306);

$query = "SELECT title FROM film WHERE film_id = 3";
$querykey = "KEY" . md5($query);

$result = $mem->get($querykey);

if ($result) {
    print "<p>Data was: " . $result . "</p>";
    print "<p>Caching success!</p><p>Retrieved data from memcached!</p>";
} else {
    $result = $mysqli->query($query);
    $row = $result->fetch_assoc();
    $mem->set($querykey, $row['title'], 10);
    print "<p>Data was: " . $row['title'] . "</p>";
    print "<p>Data not found in memcached.</p><p>Data retrieved from MySQL and stored in memcached for next time.</p>";
}
?>
