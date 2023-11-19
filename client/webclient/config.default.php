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

/**
 * this is the config file for the webclient
 */

const DEBUG = false;
const TIMEZONE = 'Europe/Berlin';

/* please provide a unique username for this installation */
const FRONTEND_USERNAME = 'some';
/* please provide a unique password for this installation */
const FRONTEND_PASSWORD = 'admin';
/* please provide a unique secret for this installation and add this to the allowed of your selfpaste installation ones*/
const THE_SECRET = 'your super duper secret';
/* the selfpaste installation endpoint url. Absolute or relative */
const THE_ENDPOINT = 'http://www.some.tld/path/to/selfpaste/index.php';
