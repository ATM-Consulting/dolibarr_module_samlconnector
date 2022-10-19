<?php
/* Copyright (C) 2022 SuperAdmin
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(! defined('NOREQUIRESOC')) define('NOREQUIRESOC', 1);
if(! defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if(! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if(! defined('NOLOGIN')) define('NOLOGIN', 1);
if(! defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if(! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);

// Load Dolibarr environment
$res = 0;
$main_inc = 'main.inc.php';
for($i = 0 ; $i < 5 && ! $res ; $i++) $res = @include str_repeat('../', $i).$main_inc;

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter(false);
// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

.samlCopyClipboard > .fa {
    opacity: 0;
    color: #666;
    -webkit-transition: opacity 0.3s;
    transition: opacity 0.3s;
}

.samlCopyClipboard .fa.fa-check {
    color: green;
}
.samlCopyClipboard > .fa.fa-exclamation-triangle {
    color: red;
}

.samlCopyClipboard {
    color: #6e6768;
    vertical-align: middle;
    max-height: 16px;
    max-width: 16px;
}
