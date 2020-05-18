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
 * This is a simple web client which can be hosted whereevery you want.
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

exit("dosmoe");
if(isset($_POST['dl']) && !empty($_POST['dl'])
    && isset($_FILES['pasty']) && !empty($_FILES['pasty'])
    && isset(SELFPASTE_UPLOAD_SECRET[$_POST['dl']])) {
    $_create = true;
}

?>
<html>
<head>
    <title>selfpaste - add a new one</title>
</head>
<body>
<form method="post" enctype="multipart/form-data" action="<?php echo THE_ENDPOINT; ?>">
    <input type="hidden" name="dl" value="<?php echo THE_SECRET; ?>">
    <p>
        <textarea name="" cols="100" rows="10"></textarea>
    </p>
    <p><input type="file" name="pasty"></p>
    <p><input type="submit" value="send" name="doSome"></p>
</form>
</body>
</html>

