<?php

$memc = new Memcached();
$memc->addServer('localhost','11211');

if(empty($_POST['filmTitle'])) {
?>
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>Simple Memcache Lookup</title>
    </head>
    <body>
      <form method="post">
        <p><b>Nom du film</b>: <input type="text" size="20" name="filmTitle"></p>
        <p><b>Description du film</b>: <input type="text" size="40" name="filmDescription"></p>
        <input type="submit">
      </form>
      <hr/>
<?php

} else {
  
    $filmTitle   = htmlspecialchars($_POST['filmTitle'], ENT_QUOTES, 'UTF-8');
    $filmDescription   = htmlspecialchars($_POST['filmDescription'], ENT_QUOTES, 'UTF-8');
	$idRandom = random_int (1002 , 6000);
	
	$mfilmTitle = $memc->set('key', array('title' => $filmTitle, "description" => $filmDescription), 10);

	$mysqli = new mysqli('localhost','root','root','sakila',3306);

	if ($mysqli->connect_errno) {
		sprintf("Database error: (%d) %s", mysqli_connect_errno(), mysqli_connect_error());
		exit;
	}
	
	$sql = sprintf('INSERT INTO film_text VALUES ("%d", "%s", "%s");', $idRandom ,   $mysqli->real_escape_string($filmTitle),   $mysqli->real_escape_string($filmDescription));

	$result = $mysqli->query($sql);

	if (!$result) {
		sprintf("Database error: (%d) %s", $mysqli->errno, $mysqli->error);	
		exit;
	}
	
	printf("<p> Check if present in the memcached </p>");
	
	$mfilms = $memc->get('key');

    if ($mfilms) {

        printf("<p>Film data for %s loaded from memcache <br> Description %s</p>", $mfilms['title'], $mfilms['description']);

        foreach (array_keys($mfilms) as $key) {
            printf("<p><b>%s</b>: %s</p>", $key, $mfilms[$key]);
        }

    } else {
		printf("<p> Film not in memchached </p>");
	}
	
	printf("<p> Check if present in the database </p>");
	
	$sql = sprintf('SELECT * FROM film_text WHERE film_id="%d"', $idRandom );

	$result = $mysqli->query($sql);

	if (!$result) {
		sprintf("Database error: (%d) %s", $mysqli->errno, $mysqli->error);
		exit;
	}

	$row = $result->fetch_assoc();

	printf("<p>Loaded (%s) from MySQL <br> Description %s</p>", htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));
	
}
?>
  </body>
</html>
