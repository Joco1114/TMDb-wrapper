<?php
// e-mail me @ joco1114@gmail.com

class TMDBv3 { // let the class begin!
	const _API_URL_ = "http://api.themoviedb.org/3/";
	const VERSION = '0.1';

	private $_apikey;
	private $_lang;
	private $_imgUrl;

// ---------------------------------------------------------------------------------

    function  __construct($apikey) {
        $this->setApikey($apikey);
        $this->setLang("hu");
		$conf = $this->_call("configuration","");
		if (empty($conf)) { die("Hibás API kód, vagy nincs megadva!"); }
		$this->setImageURL($conf);
    }

	private function setApikey($apikey)
	{
		$this->_apikey = (string) $apikey;
	}

	private function getApikey() {
		return $this->_apikey;
	}

	public function setLang($lang) {
		$this->_lang = $lang;
	}

	public function getLang() {
		return $this->_lang;
	}

	public function setImageURL($config) {
		$this->_imgUrl = (string) $config['images']["base_url"];
	}

	public function getImageURL() {
		return $this->_imgUrl . "original";
	}

	private function _call($action,$text="",$intl="") {
		$lang=(empty($lang))?$this->getLang():$lang;
		$url= TMDBv3::_API_URL_.$action."?api_key=".$this->getApikey();
			if (!empty($lang) && !empty($intl)) $url .= "&language=".$lang;
			if (!empty($text)) $url .= "&".$text;

		// echo $url;
		$options = array(
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_HEADER         => false,
		        CURLOPT_FOLLOWLOCATION => true,
		        CURLOPT_USERAGENT      => "Dune Spider",
		        CURLOPT_CONNECTTIMEOUT => 120,
		        CURLOPT_TIMEOUT        => 120,
		        CURLOPT_MAXREDIRS      => 10,
		        CURLOPT_VERBOSE        => 1
	    		); 

		    $ch = curl_init($url);
		    curl_setopt_array($ch,$options);
		    $results = curl_exec($ch);

		    // $err     = curl_errno($ch);
		    // $errmsg  = curl_error($ch) ;
		    // $header  = curl_getinfo($ch);
		    curl_close($ch);

		$results = json_decode(($results),true);
		return (array) $results;
	}

// ---------------------------------------------------------------------------------

	public function searchMovies($movieTitle, $lang="", $tableFormat=false, $force=false) {
		$movieTitle="query=".urlencode($movieTitle);
		$movieList = $this->_call("search/movie", $movieTitle, $lang);
		if ($force) return $movieList['results'][0]['id'];
		if (!$tableFormat) return $movieList;
		$t = "<table>";
		$t .= "<tr><th>#</th><th>Title</th><th>Original title</th><th>Released</th><th>View</th><th>Generate</th></tr>";

		$n = 1;
		for ($i=0; $i<count($movieList['results']); $i++) {
			if ($movieList['results'][$i]['release_date'] != "")
				$t .= "<tr><td>".($n++)."</td><td>".$movieList['results'][$i]['title']."</td><td>".$movieList['results'][$i]['original_title']."</td><td>".$movieList['results'][$i]['release_date']."</td><td><a target='moviePreview' href='http://www.themoviedb.org/movie/".$movieList['results'][$i]['id']."'>view</a></td><td><a href='?tmdb_id=".$movieList['results'][$i]['id']."'>generate</a></td></tr>";
		}
		$t .= "</table>";
		return $t;
	}

	public function getMovieInfo($movieID, $lang="") {
		if (empty($movieID)) return;
		return $this->_call("movie/".$movieID, "", $lang);
	}

	public function getGenres($movieID, $lang="") {
		$genres_en = array("Action", "Animation", "Drama", "Science Fiction", "Adventure", "Comedy", "Crime", "Disaster", "Documentary", "Family", "Thriller", "Fantasy", "History", "Horror", "Musical", "Music", "Mystery", "War", "Western", "Foreign");
		$genres_hu = array("Akció", "Animáció", "Dráma", "Sci-Fi", "Kaland", "Vígjáték", "Bűnügyi", "Katasztrófa", "Dokumentum", "Családi", "Thriller", "Fantasy", "Történelmi", "Horror", "Musical", "Zenés", "Misztikus", "Háborús", "Western", "Külföldi");

		if (empty($movieID)) return;
		$temp = $this->getMovieInfo($movieID, $lang);
		$ret = "";
		if (count($temp['genres'])>0)
		foreach ($temp['genres'] as &$genre) {
			if ($lang=="hu") $ret .= str_replace($genres_en, $genres_hu, $genre['name']).", ";
			else $ret .= $genre['name'].", ";
		}
		return substr($ret, 0, -2);
	}

	public function getBackdrops($movieID) {
		if (empty($movieID)) return;
		$temp = $this->_call("movie/" . $movieID . "/images");
		$backdrops = $temp['backdrops'];

		$ret = array();
		if (count($backdrops)>0)
		foreach ($backdrops as &$pic) {
			if ($pic['height'] == 1080 && $pic['width'] == 1920)
			{
				$ret['pic'][] = $this->getImageURL().$pic['file_path']; 
				$ret['lang'][] = $pic['iso_639_1'];
			}
		}
		return $ret;
	}

	public function getPosters($movieID, $lang="") {
		if (empty($movieID)) return;
		if (empty($lang)) $lang=$this->getLang();
		$temp = $this->_call("movie/" . $movieID . "/images");
		$posters = $temp['posters'];
		$ret = array();
		if (count($posters)>0)
		foreach ($posters as &$pic) {
			if ($pic['iso_639_1'] == $lang || $pic['iso_639_1'] == "en" || $pic['iso_639_1'] == "")
			{
				$ret['pic'][] = $this->getImageURL().$pic['file_path']; 
				$ret['lang'][] = $pic['iso_639_1'];
			}
		}
		return $ret;
	}

	public function getCrew($movieID, $dep) {
		if (empty($movieID)) return;
		$temp = $this->_call("movie/" . $movieID . "/casts");
		$dep = strtolower($dep);
		$crewList = $temp['crew'];
		$ret = "";
		if (count($crewList)>0)
		foreach ($crewList as &$crew) {
			if (strtolower($crew['job']) == $dep || strtolower($crew['department']) == $dep) $ret .= $crew['name'].", ";
		}
		return substr($ret,0,-2);
	}

	public function getCasts($movieID) {
		if (empty($movieID)) return;

		function cmpcast($a, $b)
		{
			return ($a["order"]>$b["order"]);
		}

		$temp = $this->_call("movie/" . $movieID . "/casts");
		$casts = $temp['cast'];

		$temp = array();
		if (count($casts) > 0)
		{
			usort($casts, "cmpcast"); // sort by order field (Sherk 2)
			foreach ($casts as &$actor) {
				if (!empty($actor['profile_path'])) { // only with picture
					$temp['id'][] = $actor['id'];
					$temp['name'][] = $actor['name'];
					$temp['char'][] = str_replace('(voice)', '(hang)', $actor['character']);
					$temp['pic'][] = $this->getImageURL().$actor['profile_path'];
				}
			}
		}
		return $temp;
	}

	public function getReleaseDate($movieID, $lang="") {
		if (empty($movieID)) return;
		$temp = $this->_call("movie/" . $movieID . "/releases");
		$lang = strtoupper($lang);

		$ret = 0;
		for ($i=0; $i<count($temp['countries']); $i++) 
			if ($temp['countries'][$i]['iso_3166_1']==$lang) $ret = $i;

			return ($temp['countries'][$ret]['release_date']);
	}

	public function getCertification($movieID, $lang="") {
		if (empty($movieID)) return;
		$temp = $this->_call("movie/" . $movieID . "/releases");
		$lang = strtoupper($lang);

		$ret = 0;
		for ($i=0; $i<count($temp['countries']); $i++) 
			if ($temp['countries'][$i]['iso_3166_1']==$lang) $ret = $i;

		return ($temp['countries'][$ret]['certification']);
	}

	public function getAllInfos($movieID,  $lang) {
		if (empty($movieID)) return;
		$imdb = new IMDb; 
		$movie = array();
		$tmdb = $this->getMovieInfo($movieID, $lang);

		$movie['tmdb_id'] = $tmdb['id'];
		$movie['imdb_id'] = $tmdb['imdb_id'];

		$movie['imdb_datas'] = $imdb->find_by_id($movie['imdb_id']); // read IMDB datas

		$movie['title'] = $tmdb['title'];
		$movie['original_title'] = $movie['imdb_datas']['original_title']; // IMDB
		
		$movie['genres'] = $this->getGenres($movieID, "hu");
		$movie['director'] = $this->getCrew($movieID, "Director");
		$movie['producer'] = $this->getCrew($movieID, "Producer");
		$movie['writer'] = $this->getCrew($movieID, "Screenplay");
		$movie['editor'] = $this->getCrew($movieID, "Editor");
		$movie['camera'] = $this->getCrew($movieID, "Director of Photography");
		$movie['music'] = $this->getCrew($movieID, "Sound");

		$movie['score'] = number_format($movie['imdb_datas']['rating'],1); // IMDB
		$movie['budget'] = '$'.number_format($tmdb['budget']);
		$movie['revenue'] = '$'.number_format($tmdb['revenue']);
		$movie['length'] = $tmdb['runtime']==""?$movie['imdb_datas']['runtime']:$tmdb['runtime']; // IMDB
		$movie['release_date'] = $this->getReleaseDate($movieID, $lang);
		$movie['certification'] = $this->getCertification($movieID, $lang)==""?$movie['imdb_datas']['certification']:$this->getCertification($movieID, $lang); // IMDB
		$movie['plot'] = $tmdb['overview'];
		$movie['casts'] = $this->getCasts($movieID);

		$movie['posters'] = $this->getPosters($movieID, "hu");
		if (empty($movie['posters']['pic']) && !empty($movie['imdb_datas']['poster'])) $movie['posters']['pic'][] = $movie['imdb_datas']['poster']; else $movie['posters']['pic'][] = "poster.jpg";

		$movie['backdrops'] = $this->getBackdrops($movieID);
		if (empty($movie['backdrops']['pic'])) { $movie['backdrops']['pic'][] = "backdrop.jpg"; }

		return($movie);
	}

} // EOC

// ---------------------------------------------------------------------------------

class IMDb
{
	// ported from url: https://github.com/chrisjp/IMDb-PHP-API
	
	private $baseurl = 'http://app.imdb.com/';
	private $params = array(
						'api'		=> 'v1',
						'appid'		=> 'iphone1_1',
						'apiPolicy'	=> 'app1_1',
						'apiKey'	=> '2wex6aeu6a8q9e49k7sfvufd6rhh0n',
						'locale'	=> 'en_US'
					  );

	function __construct() {
	}
	
	function build_url($method, $query="", $parameter=""){
		$url = $this->baseurl.$method.'?';
		foreach($this->params as $key => $value){
			$url .= $key.'='.$value.'&';
		}
		$url .= 'timestamp='.$_SERVER['REQUEST_TIME'].'&';
		if(!empty($parameter) AND !empty($query)) $url .= $parameter.'='.urlencode($query);
		return $url;
	}
	
	function find_by_id($id) {
		if(strpos($id, "tt")!==0) $id = "tt".$id;
		$temp = array();
		$requestURL = $this->build_url('title/maindetails', $id, 'tconst');
		$obj = $this->fetchJSON($requestURL); $obj=$obj['data'];

			$temp['imdb_id'] = $obj['tconst'];
			$temp['original_title'] = $obj['title'];
			$temp['year'] = $obj['year'];
			$temp['plot'] = $obj['plot']['outline'];
			$temp['tagline'] = $obj['tagline'];
			$temp['rating'] = $obj['rating'];
			$temp['votes'] = $obj['num_votes'];
			$temp['release_date'] = $obj['release_date']['normal'];
			$temp['runtime'] = round($obj['runtime']['time']/60);
			$temp['certification'] = $obj['certificate']['certificate'];
			$temp['poster'] = $obj['image']['url'];

		return $temp;
	}

	function fetchJSON($url) {

		$options = array(
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_HEADER         => false,
		        CURLOPT_FOLLOWLOCATION => true,
		        CURLOPT_USERAGENT      => "TMDB Spider",
		        CURLOPT_CONNECTTIMEOUT => 120,
		        CURLOPT_TIMEOUT        => 120,
		        CURLOPT_MAXREDIRS      => 10,
		        CURLOPT_VERBOSE        => 1
	    		); 

		    $ch = curl_init($url);
		    curl_setopt_array($ch,$options);
		    $results = curl_exec($ch);

		    // $err     = curl_errno($ch);
		    // $errmsg  = curl_error($ch) ;
		    // $header  = curl_getinfo($ch);
		    curl_close($ch);

		$results = json_decode(($results),true);
		return (array) $results;
	}
} // EOC

?>