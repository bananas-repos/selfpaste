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

$_t = Summoner::b64sl_unpack_id($_short);
$_t = (string)$_t;
$_p = Summoner::forwardslashStringToPath($_t);
$_requestFile = str_ends_with(SELFPASTE_UPLOAD_DIR,'/') ? SELFPASTE_UPLOAD_DIR : SELFPASTE_UPLOAD_DIR.'/';
$_requestFile .= $_p;
$_requestFile .= $_t;
if(is_readable($_requestFile)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_requestFile);
    finfo_close($finfo);

    http_response_code(200);
    header('Content-type: '.$mime);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    readfile($_requestFile);
} else {
    http_response_code(404);
    header('Content-type: text/plain; charset=UTF-8');
    $contentBody = 'File not found.';
}
