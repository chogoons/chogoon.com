<?
header("Content-Type: application/json;charset=utf-8");
header("Pragma: no-cache");
header("Cache-Control: no-cache,must-revalidate");

date_default_timezone_set('Asia/Seoul');

include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simple_html_dom.php";

require "api/twitteroauth-master/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

define("CONSUMER_KEY", "xxxxxxxxxxxxxxxxxxxxxxxxx");
define("CONSUMER_SECRET", "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");

define("ACCESS_TOKEN", "xxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
define("ACCESS_TOKEN_SECRET", "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");

include_once "dbconn.php";
//
//include_once $_SERVER['DOCUMENT_ROOT'] . "/api/PHPMailer-master/vendor/autoload.php";
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;

#### function ############################################################################

// add protocol
function prefix_protocol($url, $prefix = 'http://') {
	if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
		$url = $prefix . $url;
	}

	return $url;
}

// url validation check
function validate_url($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
	curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT,1);
	$output = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	return $httpCode;
}

// random number fidex length
function random_number($length) {
    return join('', array_map(function($value) { return $value == 1 ? mt_rand(1, 9) : mt_rand(0, 9); }, range(1, $length)));
}

function getFromUrl($url, $refUrl, $method = 'GET') {
    $ch = curl_init();
	$agent = 'han-u marbling analysis College.';
 
    switch(strtoupper($method))
    {
        case 'GET':     
            curl_setopt($ch, CURLOPT_URL, $url);
            break;
 
        case 'POST':
            $info = parse_url($url);
            $url = $info['scheme'] . '://' . $info['host'] . $info['path'];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $info['query']);
            break;
 
        default:
            return false;
    }
 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_REFERER, $refUrl);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $res = curl_exec($ch);
    curl_close($ch);
 
    return $res;
}

function gmDtToDt($gmdt) {
	$time = strtotime($gmdt);
	return date("Y-m-d H:i:s", $time);
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function tweet($msg) {
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

	//$content = $connection->get("account/verify_credentials");
	//print_r($content);

	$statues = $connection->post("statuses/update", ["status" => $msg]);

	return $statues;
}

function tweetReply($msg, $id) {
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

	$parameters = [
		'status' => $msg,
		'in_reply_to_status_id' => $id,
		'include_entities' => 1,
	];
	$statues = $connection->post('statuses/update', $parameters);

	return $statues;
}

function get_client_ip() {
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else if(isset($_SERVER['HTTP_X_FORWARDED'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	} else if(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	} else if(isset($_SERVER['HTTP_FORWARDED'])) {
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	} else if(isset($_SERVER['REMOTE_ADDR'])) {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	} else {
		$ipaddress = 'UNKNOWN';
	}

	return $ipaddress;
}

function get_encoded_hurl($str) {
     
    preg_match_all('/[\x{1100}-\x{11ff}\x{3130}-\x{318f}\x{ac00}-\x{d7af}]+/u', $str, $matches);
     
    foreach($matches as $key2 => $val2) {
        $cnt = count($val2);
        if($cnt > 0) {
            foreach($val2 as $key3 => $val3) {
                $str = str_replace($val3, urlencode($val3), $str);
            }
        }
    }
    return $str;
}

function calcDatetime($orgDatetime, $term, $unit) {

	if(empty($orgDatetime)){
		return null;
	}

	$timestamp = strtotime($orgDatetime . " -{$term} {$unit}");
	return date("Y-m-d H:i:s", $timestamp);
}

#### function ############################################################################
//Date Format: ISO8601 - Y-m-d\TH:i:sO EX)2013-04-12T15:52:01+0000

$key = $_GET['key'];

$resultArr['result'] = "success";

if($key != "withcoffee") {
	$resultArr['result'] = "failure";
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	$conn->close();
	exit;
}


### 실시간 검색어 페이지 크롤링
$mNaverUrl = "https://datalab.naver.com/keyword/realtimeList.naver";

$html = file_get_html($mNaverUrl);

if(! $html ){
	$resultArr['result'] = "failure";
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	$conn->close();
	exit;
}

### 수집된 페이지 파싱


$container = $html->find("div#container", 0)->find("div#content", 0)->find("div.section_keyword", 0)->find("div.keyword_carousel", 0)->find("div.jcarousel", 0)->find("div.section_lst_area", 0)->find("div[class='keyword_rank select_date']", 0)->find("div[class='rank_inner v2']", 0);

// 검색어 순위 영역 파싱
$ulUbnList = $container->find("div[class='rank_scroll']", 0)->find("ul[class='rank_list']", 0);

// 네이버의 수집시각 영역 파싱
$ndate = gmDtToDt($container->getAttribute('data-datetime'));


### 30초 전에 수집된 데이터를 배열에  저장

$beforeNDate = calcDatetime($ndate, 30, "second");

$beforeKey = md5($beforeNDate);

$beforeSql = "SELECT * FROM naver_realtime_rank WHERE nrr_key = '{$beforeKey}';";

$beforeResult = $conn->query($beforeSql);

while($beforeRow = $beforeResult->fetch_assoc()) {
	$beforeArr[$beforeRow['nrr_rank']] = $beforeRow['nrr_keyword'];
}

### 파싱된 현재 네이버 시각의 데이터가 DB에 있는지 확인

$currentKey = md5($ndate);

$currExistsQuery = "SELECT * FROM naver_realtime_rank WHERE nrr_key = '{$currentKey}';";

$currExistsResult = $conn->query($currExistsQuery);

$currExistsCnt = $currExistsResult->num_rows;


### 파싱된 현재 순위를 배열에 저장(DB에 저장된 데이터가 없다면 저장)

$count = 0;
foreach($ulUbnList->find('li') as $li){
	$rank = $li->find("a", 0)->find("em", 0)->innertext;
	$keyword = $li->find("a", 0)->find("span", 0)->innertext;

	$currentArr[$rank] = $keyword;

	$nrr_rank = $conn->real_escape_string($rank);
	$nrr_keyword = $conn->real_escape_string($keyword);

	$nrr_ndate = $conn->real_escape_string($ndate);
	$nrr_cdate = date('Y-m-d H:i:s');

	if($currExistsCnt == 0) {
		
		$sql = "INSERT INTO naver_realtime_rank(nrr_key, nrr_rank, nrr_keyword, nrr_ndate, nrr_cdate) VALUES('{$currentKey}', '{$nrr_rank}', '{$nrr_keyword}', '{$nrr_ndate}', '{$nrr_cdate}');";

		if ($conn->query($sql)) {

		} else {

		}
	}

}

### 순위가 15등급 이상 변동된 키워드 추출
$term = 15;
$max = 20;

if(count($currentArr) > 0) {
	foreach($beforeArr as $beforeRank => $beforeKeyword) {
		if($key = array_search($beforeKeyword, $currentArr)) {
			if($key - $beforeRank >= $term) {
				$beforePostMsgArr[] = "[" . $beforeKeyword . "]의 순위 변동: " . $beforeRank . " to " . $key . PHP_EOL;
			} else {
				$beforePostDummyArr[] = "[" . $beforeKeyword . "]의 순위 변동: " . $beforeRank . " to " . $key . PHP_EOL;
			}
		} else { // 있다가 사라짐: Out
			if( ($max - $term) >= $beforeRank ) {
				$beforePostMsgArr[] = "[" . $beforeKeyword . "]의 사라지기 전 순위: " . $beforeRank . PHP_EOL;
			} else {
				$beforePostDummyArr[] = "[" . $beforeKeyword . "]의 사라지기 전 순위: " . $beforeRank . PHP_EOL;
			}
		}
	}
}

if(count($beforeArr) > 0) {
	foreach($currentArr as $currentRank => $currentKeyword) {
		if($key = array_search($currentKeyword, $beforeArr)) {
		} else { // 없다가 등장: In
			if( ($max - $term) >= $currentRank ) {
				$currentPostMsgArr[] = "[" . $currentKeyword . "]의 첫 진입 순위: " . $currentRank . PHP_EOL;
			} else {
				$currentPostDummyArr[] = "[" . $currentKeyword . "]의 첫 진입 순위: " . $currentRank . PHP_EOL;
			}
		}
	}
}

### 저장된 트윗 포스팅 데이터가 있는지 확인

$tweetExistsQuery = "SELECT * FROM naver_realtime_rank_tweet WHERE nrrt_key = '{$currentKey}';";

$tweetExistsResult = $conn->query($tweetExistsQuery);

$tweetExistsCnt = $tweetExistsResult->num_rows;


### 저장된 트윗 포스팅이 없고, 15등급 이상 변경된 키워드가 있다면 트윗 포스팅

if( ( count($beforePostMsgArr) > 0 || count($currentPostMsgArr) ) > 0 && $tweetExistsCnt == 0 ) {
	$tweetPost = tweet("[수집시각: {$nrr_cdate}]" . PHP_EOL . "{$beforeNDate} ->" . PHP_EOL . "{$ndate}");

	$tweetPostArr = json_decode(json_encode($tweetPost), true);
	$in_reply_to_status_id = $tweetPostArr['id_str'];
	$resultArr['tweet'] = $tweetPostArr;

	if( count($beforePostMsgArr) > 0 ) {
		foreach($beforePostMsgArr as $beforePostMsg) {
			tweetReply($beforePostMsg, $in_reply_to_status_id);
		}
	}

	if( count($currentPostMsgArr) > 0 ) {
		foreach($currentPostMsgArr as $currentPostMsg) {
			tweetReply($currentPostMsg, $in_reply_to_status_id);
		}
	}

	$sql = "INSERT INTO naver_realtime_rank_tweet(nrrt_key, nrrt_tweet_id, nrrt_cdate) VALUES('{$currentKey}', '{$in_reply_to_status_id}', '{$nrr_cdate}');";

	if ($conn->query($sql)) {

	} else {

	}
}

$resultArr['timeRes']['befN'] = $beforeNDate;
$resultArr['timeRes']['curN'] = $ndate;
$resultArr['timeRes']['colD'] = $nrr_cdate;

$resultArr['beforeArr'] = $beforeArr;
$resultArr['currentArr'] = $currentArr;

$resultArr['beforePostDummyArr'] = $beforePostDummyArr;
$resultArr['currentPostDummyArr'] = $currentPostDummyArr;

echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);

$conn->close();
?>