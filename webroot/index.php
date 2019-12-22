<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 *
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 https://www.bananas-playground.net/projekt/selfpaste
 */

# global debug setting
define('DEBUG',true);

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

# error reporting
ini_set('log_errors',true);
ini_set('error_log','./logs/error.log');
if(DEBUG === true) {
    ini_set('display_errors',true);
}
else {
    ini_set('display_errors',false);
}

# static helper class
require_once 'lib/summoner.class.php';
# config file
require_once 'config.php';
# upload / file handling
require_once 'lib/mancubus.class.php';

$_short = false;
if(isset($_GET['s']) && !empty($_GET['s'])) {
    $_short = trim($_GET['s']);
    $_short = Summoner::validate($_short,'nospace') ? $_short : false;
}

$_create = false;
if(isset($_POST['dl']) && !empty($_POST['dl'])
    && isset($_FILES['pasty']) && !empty($_FILES['pasty'])
    && $_POST['dl'] === SELFPASTE_UPLOAD_SECRET) {
    $_create = true;
}

$contentType = 'Content-type: text/html; charset=UTF-8';
$contentView = 'welcome';
$httpResponseCode = 200;

if(!empty($_short)) {
    $contentType = 'Content-type: text/plain; charset=UTF-8';
    $contentView = 'view';
    $httpResponseCode = 404;
    $contentBody = 'File not found.';

    $_t = Summoner::b64sl_unpack_id($_short);
    $_t = (string)$_t;
    $_p = Summoner::forwardslashStringToPath($_t);
    $_requestFile = Summoner::endsWith(SELFPASTE_UPLOAD_DIR,'/') ? SELFPASTE_UPLOAD_DIR : SELFPASTE_UPLOAD_DIR.'/';
    $_requestFile .= $_p;
    $_requestFile .= $_t;
    if(is_readable($_requestFile)) {
        $contentBody = $_requestFile;
        $httpResponseCode = 200;
    }
}
elseif ($_create === true) {
    $contentView = 'created';
    $contentType = 'Content-type:application/json;charset=utf-8';
    $httpResponseCode = 400;
    $_message = 'Something went wrong.';

    $_file = $_FILES['pasty'];

    $_fileObj = new Mancubus();
    if($_fileObj->load($_FILES['pasty']) === true) {
        $_fileObj->setSaveFilename();
        $_fileObj->setShort();
        $_fileObj->setStoragePath();
        $_fileObj->setShortURL();

        $_do = $_fileObj->process();
        $_message = $_do['message'];
        if($_do['status'] === true) {
            $httpResponseCode = 200;
        }
    }

    $contentBody = array(
        'message' => $_message,
        'status' => $httpResponseCode
    );
}

header($contentType);
http_response_code($httpResponseCode);
if(file_exists('view/'.$contentView.'.inc.php')) {
    require_once 'view/'.$contentView.'.inc.php';
}
else {
    error_log('Content body file missing. '.var_export($_SERVER,true),3,'./logs/error.log');
    http_response_code(400);
    die('Well, something went wrong...');
}