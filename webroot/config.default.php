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

# this is your installation secret. Could be anything.
# Think of it as a key. Change it often to avoid any abuse.
define('SELFPASTE_UPLOAD_SECRET','PLEASE CHANGE YOUR SECRET');
# this is the default storage location. If you decide to move, then make sure
# to move the included .htaccess with it to protect the direct access
define('SELFPASTE_UPLOAD_DIR','pasties');
# those are the allowed file types.
# Make sure you read the README and documentation!
define('SELFPASTE_ALLOWED_FILETYPES','text/plain,text/comma-separated-values,text/css,text/xml,text/x-php,text/x-perl,text/x-shellscript,text/html,text/javascript');
# this is your domain and path on which selfpaste is accessible
# needed to respond with the correct link for your paste
# please NO / at the end
define('SELFPASTE_URL','http://your.tld/path/selfpaste/webroot');
# time in days how long a paste will be available. Default 30 days
define('SELFPASTE_PASTE_LIFETIME',30);
# time in seconds how long the flood protection should take action. Default 30sec
define('SELFPASTE_FLOOD_LIFETIME',30);
