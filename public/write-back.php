<?php
session_start();
$memc = new Memcached();
$memc->addServer('localhost','11211');

if(empty($_POST['filmTitle'])) {
	$_SESSION['idRandomArray'] = array();
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
	$idRandom = random_int (4000 , 5000);
	
	array_push($_SESSION['idRandomArray'], $idRandom);
	
	$mfilmTitle = $memc->set($idRandom, array('film_id'=> $idRandom, 'title' => $filmTitle, "description" => $filmDescription), 300);

	$mysqli = new mysqli('localhost','root','root','sakila',3306);

	if ($mysqli->connect_errno) {
		sprintf("Database error: (%d) %s", mysqli_connect_errno(), mysqli_connect_error());
		exit;
	}
	
	printf("<p> Check if present in the memcached </p>");
	
	$mfilms = $memc->get($idRandom);

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

	if ($result) {
		$row = $result->fetch_assoc();
		if(empty($row['title'])){
			printf("<p> Film not in database </p>");
		}
		else {
			printf("<p>Loaded (%s) from MySQL <br> Description %s</p>", htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));	
		}
	}
	else {
		sprintf("Database error: (%d) %s", $mysqli->errno, $mysqli->error);	
		exit;
	}
?>
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>Simple Memcache Lookup add another film</title>
    </head>
    <body>
      <form method="post">
        <p><b>Nom du film</b>: <input type="text" size="20" name="filmTitle"></p>
        <p><b>Description du film</b>: <input type="text" size="40" name="filmDescription"></p>
        <input type="submit">
      </form>
      <hr/>
	  
<?php
	
	if(count($_SESSION['idRandomArray']) >= 3) {
		printf("Write in database from cache");
		foreach ($_SESSION['idRandomArray'] as &$value) {
			$mfilms = $memc->get($value);
				
			$sql = sprintf('INSERT INTO film_text VALUES ("%d", "%s", "%s");', $mfilms['film_id'], $mysqli->real_escape_string($mfilms['title']), $mysqli->real_escape_string($mfilms['description']));

			$result = $mysqli->query($sql);

			if (!$result) {
				sprintf("Database error: (%d) %s", $mysqli->errno, $mysqli->error);	
				exit;
			}	
			
			printf("<p> Check if now present in the database </p>");
			
			$sql = sprintf('SELECT * FROM film_text WHERE film_id="%d"', $idRandom );

			$result = $mysqli->query($sql);

			if ($result) {
				$row = $result->fetch_assoc();
				if(empty($row['title'])){
					printf("<p> Film not in database </p>");
				}
				else {
					printf("<p>Loaded (%s) from MySQL <br> Description %s</p>", htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'));	
				}
			}
			else {
				sprintf("Database error: (%d) %s", $mysqli->errno, $mysqli->error);	
				exit;
			}
			
			printf("<p> Check if still present in the memcached </p>");
			if ($mfilms) {

				printf("<p>Film data for %s loaded from memcache <br> Description %s</p>", $mfilms['title'], $mfilms['description']);

				foreach (array_keys($mfilms) as $key) {
					printf("<p><b>%s</b>: %s</p>", $key, $mfilms[$key]);
				}

			} else {
				printf("<p> Film not in memchached </p>");
			}
		}
		unset($_SESSION['idRandomArray']);
	}
}
?>
  </body>
</html>
