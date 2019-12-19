<?php
# This program is free software: you can redistribute it and/or modify
# it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
#
# You should have received a copy of the
# COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
# along with this program.  If not, see http://www.sun.com/cddl/cddl.html

# 2019 https://www.bananas-playground.net/projekt/selfpaste

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
    # see http://de2.php.net/manual/en/regexp.reference.unicode.php
    if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
        die('Malformed request. Make sure you know what you are doing.');
    }
}

ini_set('log_errors',true);
ini_set('error_log','./logs/error.log');
if(DEBUG === true) {
    ini_set('display_errors',true);
}
else {
    ini_set('display_errors',false);
}

# header information
header('Content-type: text/html; charset=UTF-8');