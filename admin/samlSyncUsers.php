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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// Load Dolibarr environment
$res = 0;
$main_inc = 'main.inc.php';
for($i = 0 ; $i < 5 && ! $res ; $i++) $res = @include str_repeat('../', $i).$main_inc;

global $langs, $user, $hookmanager, $db, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../lib/samlconnector.lib.php';

// Translations
$langs->loadLangs(['admin', 'samlconnector@samlconnector']);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(['samlconnectorsetup', 'globalsetup']);

// Access control
if(! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

$mappingFields = [
	'SAMLCONNECTOR_MAPPING_USER_LASTNAME' => ['type' => 'string', 'enabled' => 1],
	'SAMLCONNECTOR_MAPPING_USER_FIRSTNAME' => ['type' => 'string', 'enabled' => 1],
	'SAMLCONNECTOR_MAPPING_USER_EMAIL' => ['type' => 'string', 'enabled' => 1]
];

$globalFields = [
	'SAMLCONNECTOR_USER_DEFAULT_GROUP' => ['type' => 'category:group', 'enabled' => 1],
	'SAMLCONNECTOR_USER_DEFAULT_ENTITY' => ['type' => 'category:entity', 'enabled' => 1]
];

$arrayofparameters_search_key = [
	'SAMLCONNECTOR_MAPPING_USER_SEARCH_KEY' => ['type' => 'array', 'data' => $mappingFields, 'enabled' => 1]
];

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;

if(! class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if(floatval(DOL_VERSION) < 16.0 && ! class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	}
	else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}

$dirmodels = array_merge(['/'], $conf->modules_parts['models']);

/*
 * Actions
 */
$arrayofparameters = array_merge($arrayofparameters_search_key, $mappingFields, $globalFields);
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = 'SamlConnectorSetup';

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = samlconnectorAdminPrepareHead();
print dol_get_fiche_head($head, 'samlSyncUsers', $langs->trans($page_name), -1, 'samlconnector@samlconnector');

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.(empty($_SESSION['newtoken']) ? '' : $_SESSION['newtoken']).'">';
	print '<input type="hidden" name="action" value="update">';
}

// Global synchronization options
print load_fiche_titre($langs->trans('SamlConnectorSyncUserAdminGlobalTitleTab'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

$tooltiphelp = $langs->trans('SAMLCONNECTOR_CREATE_UNEXISTING_USERTooltip') != 'SAMLCONNECTOR_CREATE_UNEXISTING_USERTooltip' ? $langs->trans('SAMLCONNECTOR_CREATE_UNEXISTING_USERTooltip') : '';
print '<tr class="oddeven" id="row_toggle_create_user">';
print '<td>'.$form->textwithpicto($langs->trans('SAMLCONNECTOR_CREATE_UNEXISTING_USER'), $tooltiphelp).'</td>';
print '<td>'.ajax_constantonoff('SAMLCONNECTOR_CREATE_UNEXISTING_USER').'</td>';
print '</tr>';

$createUnexistingUser = getDolGlobalString('SAMLCONNECTOR_CREATE_UNEXISTING_USER');
$styleHidden = !$createUnexistingUser ? 'style="display: none;"' : '';
// ... champ 'groupe par défaut'
print '<tr class="oddeven" id="row_default_group" '.$styleHidden.'>';
print '<td>'.$form->textwithpicto($langs->trans('SAMLCONNECTOR_USER_DEFAULT_GROUP'), $langs->trans('SAMLCONNECTOR_USER_DEFAULT_GROUPTooltip')).'</td>';
print '<td>';
if ($action == 'edit') {
	print $form->select_dolgroups(getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_GROUP'), 'SAMLCONNECTOR_USER_DEFAULT_GROUP', 1);
} else {
	if (getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_GROUP')) {
		require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
		$group_static = new UserGroup($db);
		if ($group_static->fetch(getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_GROUP')) > 0) { print $group_static->getNomUrl(1); }
		else { print $langs->trans("ErrorUnknown"); }
	}
}
print '</td>';
print '</tr>';

// ... champ 'entité par défaut'
if (isModEnabled('multicompany')) {
	global $mc;
	print '<tr class="oddeven" id="row_default_entity" '.$styleHidden.'>';
	print '<td>'.$form->textwithpicto($langs->trans('SAMLCONNECTOR_USER_DEFAULT_ENTITY'), $langs->trans('SAMLCONNECTOR_USER_DEFAULT_ENTITYTooltip')).'</td>';
	print '<td>';
	if ($action == 'edit') {
		print $mc->select_entities(getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_ENTITY'), 'SAMLCONNECTOR_USER_DEFAULT_ENTITY');
	} else {
		if (! empty(getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_ENTITY'))) {
			dol_buildpath('multicompany/class/dao_multicompany.class.php.');
			$mcDao = new DaoMulticompany($db);
			if ($mcDao->fetch(getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_ENTITY')) > 0) {
				print $mcDao->label;
			}
			else { print $langs->trans("ErrorUnknown"); }
		}
	}
	print '</td>';
	print '</tr>';
}

$tooltiphelp = $langs->trans('SAMLCONNECTOR_UPDATE_USER_EVERYTIMETooltip') != 'SAMLCONNECTOR_UPDATE_USER_EVERYTIMETooltip' ? $langs->trans('SAMLCONNECTOR_UPDATE_USER_EVERYTIMETooltip') : '';
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans('SAMLCONNECTOR_UPDATE_USER_EVERYTIME'), $tooltiphelp).'</td>';
print '<td>'.ajax_constantonoff('SAMLCONNECTOR_UPDATE_USER_EVERYTIME').'</td>';
print '</tr>';

print '</table>';


// Mapping
print load_fiche_titre($langs->trans('SamlConnectorSyncUserAdminMappingTitleTab'), '', '');

// On prépare le tableau de paramètres pour les boucles d'affichage du mapping
$mapping_display_params = array_merge($arrayofparameters_search_key, $mappingFields);


if($action == 'edit') {
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		print $formSetup->generateOutput(true);
	}
	else {
		// SUPPRESSION : Le formulaire est déjà ouvert plus haut
		// print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		// print '<input type="hidden" name="token" value="'.(empty($_SESSION['newtoken']) ? '' : $_SESSION['newtoken']).'">';
		// print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

		// MODIFICATION : La boucle ne parcourt QUE les champs du mapping pour éviter les conflits
		foreach($mapping_display_params as $constname => $val) {
			if($val['enabled'] == 1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
				print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
				print '</td><td>';

				if($val['type'] == 'textarea') {
					print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">'."\n";
					print getDolGlobalString($constname);
					print "</textarea>\n";
				}
				else if($val['type'] == 'securekey') {
					print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : getDolGlobalString($constname)).'" size="40">';
					if(! empty($conf->use_javascript_ajax)) {
						print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token'.$constname.'" class="linkobject"');
					}
					if(! empty($conf->use_javascript_ajax)) {
						print "\n".'<script type="text/javascript">';
						print '$(document).ready(function () {
                        $("#generate_token'.$constname.'").click(function() {
                            $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                             action: \'getrandompassword\',
                             generic: true
                         },
                         function(token) {
                           $("#'.$constname.'").val(token);
                         });
                         });
                    });';
						print '</script>';
					}
				}
				else if($val['type'] == 'array') {
					$data = [];
					foreach($val['data'] as $k => $v) $data[$k] = $langs->trans($k);

					print Form::selectarray($constname, $data, getDolGlobalString($constname), 1);
				}
				else {
					print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth400' : $val['css']).'" value="'.getDolGlobalString($constname).'">';
				}
				print '</td></tr>';
			}
		}
		print '</table>';

		print '<br><div class="center">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans('Save').'">';
		print '</div>';

		// SUPPRESSION : Le formulaire sera fermé à la fin de la page
		// print '</form>';
	}

	print '<br>';
}
else { // Mode lecture
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		if(! empty($formSetup->items)) {
			print $formSetup->generateOutput();
		}
	}
	else {
		// En mode lecture, on affiche uniquement les paramètres du mapping car les globaux sont déjà affichés plus haut
		if(! empty($mapping_display_params)) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

			// MODIFICATION : La boucle ne parcourt QUE les champs du mapping
			foreach($mapping_display_params as $constname => $val) {
				if($val['enabled'] == 1) {
					$setupnotempty++;
					print '<tr class="oddeven"><td>';
					$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
					print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
					print '</td><td>';

					if($val['type'] == 'textarea') {
						print dol_nl2br(getDolGlobalString($constname));
					}
					else if($val['type'] == 'array') {
						if(getDolGlobalString($constname)) print $langs->trans(getDolGlobalString($constname));
					}
					else {
						print getDolGlobalString($constname);
					}
					print '</td></tr>';
				}
			}

			print '</table>';
		}
	}

	if($setupnotempty) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.(empty($_SESSION['newtoken']) ? '' : $_SESSION['newtoken']).'">'.$langs->trans('Modify').'</a>';
		print '</div>';
	}
}

// AJOUT : On ferme le formulaire ici si on est en mode édition
if ($action == 'edit') {
	print '</form>';
}


if(empty($setupnotempty)) {
	print '<br>'.$langs->trans('NothingToSetup');
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
