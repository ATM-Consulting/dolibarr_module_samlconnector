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

global $db, $conf, $langs, $hookmanager;

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once __DIR__.'/lib/autoload.php';

$fk_idp = intval(GETPOST('fk_idp', 'int'));

$login = get_saml($fk_idp);

$login->processResponse();

if($login->isAuthenticated()) {
    $user = new User($db);
    $admin = new User($db);
    $admin->fetch('', 'admin');

    $res = $user->fetch('', $login->getNameId());

    if(! empty($conf->global->SAMLCONNECTOR_CREATE_UNEXISTING_USER) || ! empty($conf->global->SAMLCONNECTOR_UPDATE_USER_EVERYTIME)) {
        $user->firstname = $login->getAttribute($conf->global->SAMLCONNECTOR_MAPPING_USER_FIRSTNAME)[0];
        $user->lastname = $login->getAttribute($conf->global->SAMLCONNECTOR_MAPPING_USER_LASTNAME)[0];
//        $user->admin = in_array('ADMINISTRATOR', $login->getAttribute('type')) ? 1 : 0;
        $user->email = $login->getAttribute($conf->global->SAMLCONNECTOR_MAPPING_USER_EMAIL)[0];
    }


    if($res <= 0 && ! empty($conf->global->SAMLCONNECTOR_CREATE_UNEXISTING_USER)) {
        $user->login = $login->getNameId();
        $user->create($admin);
    }
    elseif($res > 0 && ! empty($conf->global->SAMLCONNECTOR_UPDATE_USER_EVERYTIME)) {
        $user->update($admin);
    }

    if(! empty($user->login)) {
        $_SESSION['dol_login'] = $user->login;
        $_SESSION['dol_authmode'] = 'saml';
        $_SESSION['dol_tz'] = $dol_tz ?? '';
        $_SESSION['dol_tz_string'] = $dol_tz_string ?? '';
        $_SESSION['dol_dst'] = $dol_dst ?? '';
        $_SESSION['dol_dst_observed'] = $dol_dst_observed ?? '';
        $_SESSION['dol_dst_first'] = $dol_dst_first ?? '';
        $_SESSION['dol_dst_second'] = $dol_dst_second ?? '';
        $_SESSION['dol_screenwidth'] = $dol_screenwidth ?? '';
        $_SESSION['dol_screenheight'] = $dol_screenheight ?? '';
        $_SESSION['dol_company'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
        $_SESSION['dol_samlconnector_fk_idp'] = $fk_idp;
		if(GETPOSTISSET('entity')) $_SESSION['dol_entity'] = GETPOST('entity','int');
        else $_SESSION['dol_entity'] = $conf->entity;

        // Store value into session (values stored only if defined)
        if(! empty($dol_hide_topmenu)) $_SESSION['dol_hide_topmenu'] = $dol_hide_topmenu;
        if(! empty($dol_hide_leftmenu)) $_SESSION['dol_hide_leftmenu'] = $dol_hide_leftmenu;
        if(! empty($dol_optimize_smallscreen)) $_SESSION['dol_optimize_smallscreen'] = $dol_optimize_smallscreen;
        if(! empty($dol_no_mouse_hover)) $_SESSION['dol_no_mouse_hover'] = $dol_no_mouse_hover;
        if(! empty($dol_use_jmobile)) $_SESSION['dol_use_jmobile'] = $dol_use_jmobile;

        dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION['dol_login'].' Session id='.session_id());

        $db->begin();
        $user->update_last_login_date();

        $loginfo = 'TZ='.$_SESSION['dol_tz'].';TZString='.$_SESSION['dol_tz_string'].';Screen='.$_SESSION['dol_screenwidth'].'x'.$_SESSION['dol_screenheight'];

        // Call triggers for the "security events" log
        $user->trigger_mesg = $loginfo;
        // Call triggers
        include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
        $interface = new Interfaces($db);
        $result = $interface->run_triggers('USER_LOGIN', $user, $user, $langs, $conf);

        $hookmanager->initHooks(['login']);
        $parameters = ['dol_authmode' => 'saml', 'dol_loginfo' => $loginfo];
        $reshook = $hookmanager->executeHooks('afterLogin', $parameters, $user, $action);    // Note that $action and $object may have been modified by some hooks

        $db->commit();
    }
}

if(isset($_REQUEST['RelayState'])) {
    $login->redirectTo($_REQUEST['RelayState']);
} else {
	header('Location: '.DOL_URL_ROOT);
	exit;
}
?>
