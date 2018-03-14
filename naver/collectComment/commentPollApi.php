<?
header("Content-Type: application/json;charset=utf-8");
header("Pragma: no-cache");
header("Cache-Control: no-cache,must-revalidate");

date_default_timezone_set('Asia/Seoul');

//include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simple_html_dom.php";
//
//include_once $_SERVER['DOCUMENT_ROOT'] . "/api/PHPMailer-master/vendor/autoload.php";
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;

#### function

// add protocol
function prefix_protocol($url, $prefix = 'http://') {
	if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
		$url = $prefix . $url;
	}

	return $url;
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function get_redirect_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $info['redirect_url'];
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

// random number fidex length
function random_number($length) {
    return join('', array_map(function($value) { return $value == 1 ? mt_rand(1, 9) : mt_rand(0, 9); }, range(1, $length)));
}

function getFromUrl($url, $refUrl, $method = 'GET') {
    $ch = curl_init();
	$agent = 'han-u marbling analysis College';
 
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

#### function
//Date Format: ISO8601 - Y-m-d\TH:i:sO EX)2013-04-12T15:52:01+0000


### 기사 URL
$reqUrl = "http://news.naver.com/main/hotissue/read.nhn?mid=hot&sid1=100&cid=1070398&iid=49707646&oid=214&aid=0000800903&ptype=052";

$current_date = gmDate("Y-m-d\TH:i:sO");

### 기사 URL 유효성 검사 (HTTP STATUS)

// add protocol
$reqUrl = prefix_protocol($reqUrl);

// 단축 URL(naver.me)의 경우, referer url(기사 full url)을 취득함
if(startsWith($reqUrl, "http://naver.me/")) {
	$reqUrl = get_redirect_url($reqUrl);
}

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

### 기사 url의 query string을 파싱하여 get parameter를 취득

// parse Query string
$parsedUrl = parse_url($reqUrl);

$query = $parsedUrl['query'];

parse_str($query, $params);

//print_r($params);

$oid = $params['oid'];
$aid = $params['aid'];

// parameter check
if(empty($oid) || empty($aid)) {
	$resultArr['success'] = "";
	$resultArr['redUrl'] = $reqUrl;
	$resultArr['code'] = "E001";
	$resultArr['message'] = "wrong url - empty oid or aid.";
	$resultArr['lang'] = "ko";
	$resultArr['country'] = "UNKNOWN";
	$resultArr['result'] = array();
	$resultArr['date'] = $current_date;
	echo json_encode($resultArr, JSON_UNESCAPED_UNICODE);
	exit;
}

### 네이버 기사의 댓글은 jsonp 방식으로 호출되기에 댓글 호출용 URL을 생성함
### 필요한 값은 objectId, 임시 callback function name

$objectId = urlencode("news{$oid},{$aid}");
$_callback = "jQuery" . random_number(19) . "_" . random_number(13);

$articles = array();

$newsUrl = "https://apis.naver.com/commentBox/cbox/web_neo_list_jsonp.json?ticket=news&templateId=view_politics&pool=cbox5&_callback={$_callback}&lang=ko&country=&objectId={$objectId}&includeAllStatus=true";

### curl 호출을 통하여 댓글 목록을 json 형태로 취득
$commentOrg = getFromUrl($newsUrl, $reqUrl);

### callback function name을 제거하여 순수 json string을 취득
$commentOrg = str_replace($_callback . "(", "", $commentOrg);
$commentOrg = substr($commentOrg , 0, -2);

### json string을 배열로 변환
$resultArr = json_decode($commentOrg, true);

$resultArr['redUrl'] = $reqUrl;

?>
<?=json_encode($resultArr, JSON_UNESCAPED_UNICODE)?>