<?
header("Content-Type: application/json;charset=utf-8");
header("Pragma: no-cache");
header("Cache-Control: no-cache,must-revalidate");

date_default_timezone_set('Asia/Seoul');

include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simple_html_dom.php";

#### function

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

function gmDtToDt($gmdt) {
	$time = strtotime($gmdt);
	return date("Y-m-d H:i:s", $time);
}

function getOidViaUrl($access_token, $postId) {

	$bearer = $access_token;

	$url = 'http://comment.daum.net/apis/v1/posts/' . $postId;

	$header = array();
	$header[] = 'Authorization: Bearer ' . $bearer;
	$header[] = 'Content-Type: application/json';

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// execute!
	$response = curl_exec($ch);

	// close the connection, release resources used
	curl_close($ch);

	return $response;
}

function getAuthKey($url) {

	try {
		$html = file_get_html($url);

		if((empty($html)) || (count($html) <= 0)){
			throw new Exception("웹페이지 크롤링 도중 오류가 발생했습니다.");
		}

		$clientId = $html->find("div[id='alex-area']", 0)->getAttribute('data-client-id');
		$postId = $html->find("div[id='alex-area']", 0)->getAttribute('data-post-id');

		$certiUrl = "https://comment.daum.net/auth/credential?client_id=" . $clientId;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $certiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		$json = curl_exec($ch);

		$stdObj = json_decode($json);

		$access_token = $stdObj->access_token;

		$ret->clientId = $clientId;
		$ret->postId = $postId;
		$ret->access_token = $access_token;

		return $ret;
	} catch(Exception $e) {
		$ret->errMsg = $e->getMessage();

		return $ret;
	}
}

function getFromUrl($url) {
    $ch = curl_init();
	$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
 
    curl_setopt($ch, CURLOPT_URL, $url);
 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $res = curl_exec($ch);
    curl_close($ch);
 
    return $res;
}

#### function
//Date Format: ISO8601 - Y-m-d\TH:i:sO EX)2013-04-12T15:52:01+0000
$reqUrl = trim($_GET['reqUrl']);

$current_date = gmDate("Y-m-d\TH:i:sO");

// add protocol
$reqUrl = prefix_protocol($reqUrl);

// validation check
if( validate_url($reqUrl) != 200 ) {
	$resultArr['success'] = "";
	$resultArr['redUrl'] = $reqUrl;
	$resultArr['code'] = "E001";
	$resultArr['message'] = "유효하지 않은 URL입니다.";
	$resultArr['lang'] = "ko";
	$resultArr['country'] = "UNKNOWN";
	$resultArr['result'] = array();
	$resultArr['date'] = $current_date;
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	exit;
}

$parsedUrl = parse_url($reqUrl);

$path = $parsedUrl['path'];

$pathArr = explode('/', $path);

$newsKey = $pathArr[2];

// validation check
if( empty($newsKey) || !is_numeric($newsKey) ) {
	$resultArr['success'] = "";
	$resultArr['redUrl'] = $reqUrl;
	$resultArr['code'] = "E002";
	$resultArr['message'] = "유효하지 않은 URL입니다.";
	$resultArr['lang'] = "ko";
	$resultArr['country'] = "UNKNOWN";
	$resultArr['result'] = array();
	$resultArr['date'] = $current_date;
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	exit;
}

$authInfo = getAuthKey($reqUrl);

if( !empty( $authInfo->errMsg ) ) {
	$resultArr['success'] = "";
	$resultArr['redUrl'] = $reqUrl;
	$resultArr['code'] = "E999";
	$resultArr['message'] = $authInfo->errMsg;
	$resultArr['lang'] = "ko";
	$resultArr['country'] = "UNKNOWN";
	$resultArr['result'] = array();
	$resultArr['date'] = $current_date;
	$conn->close();
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	exit;
}

$clientId = $authInfo->clientId;
$access_token = $authInfo->access_token;
$postId = $authInfo->postId;

if(empty($access_token) || empty($postId)) {
	$resultArr['success'] = "";
	$resultArr['redUrl'] = $reqUrl;
	$resultArr['code'] = "E003";
	$resultArr['message'] = "인증정보가 없습니다.";
	$resultArr['lang'] = "ko";
	$resultArr['country'] = "UNKNOWN";
	$resultArr['result'] = array();
	$resultArr['date'] = $current_date;
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	exit;
}

$result = getOidViaUrl($access_token, $postId);

$resultObj = json_decode($result);

$oid = $resultObj->id;

$commentUrl = "http://comment.daum.net/apis/v1/posts/{$oid}/comments?parentId=0&offset=0&limit=20&sort=RECOMMEND";

$commentResult = getFromUrl($commentUrl);

$resultArr['result']['commentList'] = json_decode($commentResult, true);

$resultArr['redUrl'] = $reqUrl;

?>
<?=json_encode($resultArr, JSON_UNESCAPED_UNICODE)?>