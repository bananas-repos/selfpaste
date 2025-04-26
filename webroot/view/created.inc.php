<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.
 *
 * 2019 - 2025 https://://www.bananas-playground.net/projekt/selfpaste
 */
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
        if(defined('LOG_CREATION') && LOG_CREATION === true) {
            Summoner::createLog($_message." ".SELFPASTE_UPLOAD_SECRET[$_POST['dl']]);
        }
    }
}

http_response_code($httpResponseCode);
header('Content-type:application/json;charset=utf-8');
echo json_encode(array(
        'message' => $_message,
        'status' => $httpResponseCode))."\n";
