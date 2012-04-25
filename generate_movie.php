<!DOCTYPE HTML>

<HTML lang="hu"> 
<head>
	<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Expires" CONTENT="0">

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
		body { background: black; color: white; font: normal 10pt Arial;}
		table { border-collapse:collapse; background: #202020; border: 3px solid #804040; }
		td { padding: 6px; border:1px dotted #804040; }
		th { padding: 6px; background: #804040; border:1px solid #a06060; }
		a { color: #ffffc0; text-decoration: none;}
		a:hover { color: #ff8000; text-decoration: underline; }
		h1, h2 { font-family: Georgia; }
		h1 { padding: 10px 0px; margin: 0px; color: #ff8000; }
		h2 { padding: 0px 0px; margin: 0px; font-style: italic; color: #804040; }
	</style>
</head>

<?php

include("wrapper.php");

$apikey = "API_KEY";
$tmdb = new TMDBv3($apikey);

function _d($t) {
	echo "<pre>";
	print_r($t);
	echo "</pre>";
}

if (count($_GET)==0) echo "<form>TMDb_ID: <input type='text' name='tmdb_id'><br />Film cím: <input type='text' name='search'><input type='submit' name='submit' value='Mehet'></form>";
if ($_GET['tmdb_id'] !="") 
	$movie = $tmdb->getMovieInfo($_GET['tmdb_id'], "hu"); else if ($_GET['search'] !="") { echo $tmdb->searchMovies($_GET['search'], "hu", true); exit; } 

if (!empty($_GET['tmdb_id']))
{
	$tmdb_id = $_GET['tmdb_id'];
	$movie = $tmdb->getAllInfos($tmdb_id, "hu");
	if (empty($movie['tmdb_id'])) die("Hibás MOVIE adatok, vagy nem létező film! (NOT FOUND)"); else
	
	_d($movie);
}

?>


