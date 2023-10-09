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
 * 2019 - 2023 https://://www.bananas-playground.net/projekt/selfpaste
 */

# this is your installation secret. Could be anything.
# Think of it as a key. Change it often to avoid any abuse.
# The description will be used in the log files
const SELFPASTE_UPLOAD_SECRET =
	array(
		'PLEASE CHANGE YOUR SECRET' => 'Your description for this secret 1',
		'PLEASE CHANGE YOUR SECRET' => 'Your description for this secret'
	);
# creation of a paste and which secret was used into logs/create.log file
const LOG_CREATION = true;
# this is the default storage location. If you decide to move, then make sure
# to move the included .htaccess with it to protect the direct access
const SELFPASTE_UPLOAD_DIR = 'pasties';
# those are the allowed file types.
# Make sure you read the README and documentation!
const SELFPASTE_ALLOWED_FILETYPES = 'text/plain,text/comma-separated-values,text/css,text/xml,text/x-php,text/x-perl,text/x-shellscript,text/html,text/javascript,text/c-x,text/x-makefile';
# this is your domain and path on which selfpaste is accessible
# needed to respond with the correct link for your paste
# please NO / at the end
const SELFPASTE_URL = 'http://your.tld/path/selfpaste/webroot';
# time in days how long a paste will be available. Default 30 days
const SELFPASTE_PASTE_LIFETIME = 30;
# time in seconds how long the flood protection should take action. Default 30sec
const SELFPASTE_FLOOD_LIFETIME = 30;
