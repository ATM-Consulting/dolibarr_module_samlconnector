<?php
/* Copyright (C) 2022 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

const NOLOGIN = true;
const NOCSRFCHECK = true;

// Load Dolibarr environment
$res = 0;
$main_inc = 'main.inc.php';
for($i = 0 ; $i < 5 && ! $res ; $i++) $res = @include str_repeat('../', $i).$main_inc;

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once __DIR__.'/lib/autoload.php';

$fk_idp = GETPOST('fk_idp', 'int');

$login = get_saml($fk_idp);

$newpath = DOL_MAIN_URL_ROOT.'/index.php?mainmenu=home&leftmenu=home';
$landingpage = empty($user->conf->MAIN_LANDING_PAGE) ? (empty($conf->global->MAIN_LANDING_PAGE) ? '' : $conf->global->MAIN_LANDING_PAGE) : $user->conf->MAIN_LANDING_PAGE;
if(! empty($landingpage)) $newpath = dol_buildpath($landingpage, 1);


$login->login($newpath);

die;
