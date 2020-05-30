<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 *
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste
 */

/**
 * This is a simple web client which can be hosted where you want.
 * copy the config.default.php file to config.php and update its settings
 */

define('DEBUG',false);
require_once 'config.php';

# Encoding and error reporting setting
mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
ini_set('error_reporting',-1); // E_ALL & E_STRICT

# default time setting
date_default_timezone_set('Europe/Berlin');

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
    if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
        die('Malformed request. Make sure you know what you are doing.');
    }
}
ini_set('display_errors',false);
if(DEBUG === true) {
    ini_set('display_errors',true);
}

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])
    || $_SERVER['PHP_AUTH_USER'] !== FRONTEND_USERNAME || $_SERVER['PHP_AUTH_PW'] !== FRONTEND_PASSWORD
) {
    header('WWW-Authenticate: Basic realm="Skynet"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'No Access...';
    exit;
}

$statusMessage = "";
if(isset($_POST['doSome'])) {
    $_text = trim($_POST['asText']);
    $_file = $_FILES['uploadFile'];

    if(!empty($_text) && !empty($_file['tmp_name'])) {
        $statusMessage = "One option. Not both at the same time.";
    }
    elseif (!empty($_text)) {
        $_tmpfile = tmpfile();
        fwrite($_tmpfile, $_text);
        $data['pasty'] = curl_file_create(stream_get_meta_data($_tmpfile)['uri']);
    }
    elseif(!empty($_file['tmp_name'])) {

        if($_file['error'] === UPLOAD_ERR_OK) {
            $data['pasty'] = curl_file_create($_file['tmp_name']);
        }
        else {
            $statusMessage = "Upload of selected file failed.";
        }
    }

    if(empty($statusMessage)) {
        $data['dl'] = THE_SECRET;
        $call = curlPostUploadCall(THE_ENDPOINT,$data);

        $statusMessage = "Something went wrong. ".var_export($call,true);
        $json = json_decode($call,true);
        if(!empty($call) && $json != NULL) {
            if (isset($json['message']) && $json['status'] == "200") {
                $statusMessage = $json['message'];
            }
        }
    }
}

?>
<html>
<head>
    <title>selfpaste - add a new one</title>
</head>
<body>
<?php if(!empty($statusMessage)) { ?>
<p><?php echo $statusMessage; ?></p>
<?php } ?>
<form method="post" enctype="multipart/form-data" action="">
    <p>
        <textarea name="asText" cols="100" rows="20"></textarea>
    </p>
    <p><input type="file" name="uploadFile"></p>
    <p><input type="submit" value="send" name="doSome"></p>
</form>
</body>
</html>
<?php
/**
 * functions start here
 */

/**
 * execute a curl call to the given $url
 * @param string $url The request url
 * @param bool $port
 * @return bool|mixed
 */
function curlPostUploadCall($url,$data,$port=false) {
    $ret = false;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);

    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    if(!empty($port)) {
        curl_setopt($ch, CURLOPT_PORT, $port);
    }

    $do = curl_exec($ch);

    if(is_string($do) === true) {
        $ret = $do;
    }
    else {
        error_log(var_export(curl_error($ch),true));
    }

    curl_close($ch);

    return $ret;
}
