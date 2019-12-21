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

# this is your installation secret. Could be anything.
# Think of it as a key. Change it often to avoid any abuse.
define('SELFPASTE_UPLOAD_SECRET','PLEASE CHANGE YOUR SECRET');
# this is the default storage location. If you decide to move, then make sure
# to move the included .htaccess with it to protect the direct access
define('SELFPASTE_UPLOAD_DIR','pasties');
# those are the allowed file types.
# Make sure you read the README and documentation!
define(SELFPASTE_ALLOWED_FILETYPES,'');
