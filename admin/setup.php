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
require_once __DIR__.'/../class/samlconnectoridp.class.php';

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

$fk_idp = GETPOST('fk_idp', 'int');

$form = new Form($db);
$idp = new SamlConnectorIDP($db);
$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];


// Savable conf for SP
$parameterSP = [
	'SAMLCONNECTOR_SP_CERT_PATH' => ['type' => 'string', 'enabled' => 1],
	'SAMLCONNECTOR_SP_PRIV_KEY_PATH' => ['type' => 'string', 'enabled' => 1],
	'SAMLCONNECTOR_SP_PRIV_KEY_PASSPHRASE' => ['type' => 'password', 'enabled' => 1]
];

// Savable conf for IDP
$parameterIDP = [
	'SAMLCONNECTOR_IDP_DISPLAY_BUTTON' => ['type' => 'yesno', 'enabled' => 1],
	'SAMLCONNECTOR_MANAGE_MULTIPLE_IDP' => ['type' => 'yesno', 'enabled' => 1],
	'SAMLCONNECTOR_IDP_METADATA_SOURCE' => ['type' => 'array', 'data' => ['url' => 'url', 'localFile' => 'localFile'], 'enabled' => 1],
	'SAMLCONNECTOR_IDP_METADATA_URL' => ['type' => 'string', 'enabled' => 1],
	'SAMLCONNECTOR_IDP_METADATA_XML_PATH' => ['type' => 'string', 'enabled' => 1]
];

$arrayofparameters = [
	//    'SAMLCONNECTOR_MYPARAM0' => ['type' => 'yesno', 'enabled' => 1]
	//    'SAMLCONNECTOR_MYPARAM1' => ['type' => 'string', 'css' => 'minwidth500', 'enabled' => 1],
	//    'SAMLCONNECTOR_MYPARAM2' => ['type' => 'textarea', 'enabled' => 1],
	//    'SAMLCONNECTOR_MYPARAM3' => ['type' => 'category:'.Categorie::TYPE_CUSTOMER, 'enabled' => 1],
	//    'SAMLCONNECTOR_MYPARAM6' => ['type' => 'thirdparty_type', 'enabled' => 1],
	//    'SAMLCONNECTOR_MYPARAM7' => ['type' => 'securekey', 'enabled' => 1],
	//    'SAMLCONNECTOR_MYPARAM8' => ['type' => 'product', 'enabled' => 1],
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
// For standard purpose
$arrayofparameters = array_merge($parameterSP, $parameterIDP);

if($action == 'disableIDP' || $action == 'enableIDP') {
	$res = $idp->fetch($fk_idp);
	if($res > 0) {
		if($action == 'disableIDP') {
			$statut = SamlConnectorIDP::STATUS_INACTIVE;
			$msg = $langs->trans('IDPDisabled');
		}
		else {
			$statut = SamlConnectorIDP::STATUS_ACTIVE;
			$msg = $langs->trans('IDPEnabled');
		}
		$res = $idp->setStatut($statut);
		if($res > 0) setEventMessage($msg);
		else setEventMessages($idp->error, $idp->errors, 'errors');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

if($action == 'addIDP') {
	$error = 0;
	if(is_array($idp->fields) && ! empty($idp->fields)) {
		foreach($idp->fields as $key => $field) {
			if(GETPOSTISSET($key)) $idp->{$key} = GETPOST($key, 'none');
		}
		if($idp->status < 0) $idp->status = 0;
		/**
		 * Errors
		 */
		if(empty($idp->label)) {
			$error++;
			setEventMessage('MissingIDPLabel', 'errors');
		}
		if(empty($idp->fk_idp_type) || $idp->fk_idp_type == -1) {
			$error++;
			setEventMessage('MissingIDPType', 'errors');
		}
		if($idp->metadata_source < 0) {
			$error++;
			setEventMessage('MissingIDPSource', 'errors');
		}
		if(empty($idp->metadata_url) && empty($idp->metadata_xml_path)) {
			$error++;
			setEventMessage('MissingIDPFilePathOrUrl', 'errors');
		}
		/**
		 * Fin gestion erreurs
		 */

		if(empty($error)) {
			$res = $idp->create($user);
			if($res < 0) setEventMessages($idp->error, $idp->errors, 'errors');
			else setEventMessage($langs->trans('IDPSuccessfullyAdded'));
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		}
	}
}

if($action == 'confirm_deleteIDP') {
	$res = $idp->fetch($fk_idp);
	if($res > 0) {
		$resDel = $idp->delete($user);
		if($resDel > 0) setEventMessage($langs->trans('SuccessfullyDeleted'));
		else setEventMessages($idp->error, $idp->errors, 'errors');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

//Les paramètres SP sont sur toutes les entités donc on enregistre la conf en entité 0
if($action == 'update' && is_array($parameterSP) && ! empty($parameterSP)) {
	$spIsUpdate = false;
	foreach($parameterSP as $key => $psp) {
		if(GETPOSTISSET($key)) {
			if(preg_match('/category:/', $psp['type'])) {
				if(GETPOST($key, 'int') == '-1') {
					$val_const = '';
				}
				else {
					$val_const = GETPOST($key, 'int');
				}
			}
			else {
				$val_const = GETPOST($key, 'alpha');
			}

			$result = dolibarr_set_const($db, $key, $val_const, 'chaine', 0, '', 0);
			if($result < 0) {
				$error++;
				break;
			}
			$spIsUpdate = true;
		}
	}
	if($spIsUpdate) {
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}

if(floatval(DOL_VERSION) >= 12.0) {
	$oldEntity = $conf->entity;
	$conf->entity = 0;
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
	$conf->entity = $oldEntity;
}
else if(floatval(DOL_VERSION) < 12.0 && $action === 'update') {
	$db->begin();

	$ok = true;
	if (is_array($arrayofparameters) && !empty($arrayofparameters)) {
		foreach ($arrayofparameters as $key => $val) {
			// Modify constant only if key was posted (avoid resetting key to the null value)
			if (GETPOSTISSET($key)) {
				$result = dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', 0);
				if ($result < 0) {
					$ok = false;
					break;
				}
			}
		}
	}

	if(! $error) {
		$db->commit();
		setEventMessages($langs->trans('SetupSaved'), []);
	}
	else {
		$db->rollback();
		setEventMessages($langs->trans('SetupNotSaved'), [], 'errors');
	}
}

/*
 * View
 */

$help_url = '';
$page_name = 'SamlConnectorSetup';

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = samlconnectorAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'samlconnector@samlconnector');
if($action == 'deleteIDP') {
	print $form->formconfirm($_SERVER['PHP_SELF'].'?fk_idp='.$fk_idp, $langs->trans('DeleteIDP'), $langs->trans('ConfirmDeleteIDP'), 'confirm_deleteIDP', '', '', 1);
}

// Service Provider (SP) setup part

print load_fiche_titre($langs->trans('SamlConnectorAdminTitleTabSP'), '', '');
if($action == 'editSP' && $conf->entity == 1) {
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		print $formSetup->generateOutput(true);
	}
	else {
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$newToken.'">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

		foreach($parameterSP as $constname => $val) {
			if($val['enabled'] == 1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
				print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
				print '</td>';
				printConfInput($constname, $val);
				print '</tr>';
			}
		}
		print '</table>';

		print '<br><div class="center">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans('Save').'">';
		print '</div>';

		print '</form>';
	}

	print '<br>';
}
else {
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		if(! empty($formSetup->items)) {
			print $formSetup->generateOutput();
		}
	}
	else {
		if(is_array($parameterSP) && ! empty($parameterSP)) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

			foreach($parameterSP as $constname => $val) {
				if($val['enabled'] == 1) {
					$setupnotempty++;
					print '<tr class="oddeven"><td>';
					$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
					print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
					print '</td><td>';

					if($val['type'] == 'textarea') {
						print dol_nl2br($conf->global->{$constname});
					}
					else if($val['type'] == 'html') {
						print  $conf->global->{$constname};
					}
					else if($val['type'] == 'yesno') {
						print ajax_constantonoff($constname, array(), 0);
					}
					else if(preg_match('/emailtemplate:/', $val['type'])) {
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);

						$tmp = explode(':', $val['type']);

						$template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, $conf->global->{$constname});
						if($template < 0) {
							setEventMessages(null, $formmail->errors, 'errors');
						}
						print $langs->trans($template->label);
					}
					else if(preg_match('/category:/', $val['type'])) {
						$c = new Categorie($db);
						$result = $c->fetch($conf->global->{$constname});
						if($result < 0) {
							setEventMessages(null, $c->errors, 'errors');
						}
						else if($result > 0) {
							$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
							$toprint = [];
							if(is_array($ways)) {
								foreach ($ways as $way) {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
								}
							}
							print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
						}
					}
					else if(preg_match('/thirdparty_type/', $val['type'])) {
						if($conf->global->{$constname} == 2) {
							print $langs->trans('Prospect');
						}
						else if($conf->global->{$constname} == 3) {
							print $langs->trans('ProspectCustomer');
						}
						else if($conf->global->{$constname} == 1) {
							print $langs->trans('Customer');
						}
						else if($conf->global->{$constname} == 0) {
							print $langs->trans('NorProspectNorCustomer');
						}
					}
                    elseif ($val['type'] == 'password') {
                        print preg_replace('/./i', '*', $conf->global->{$constname});
                    }
					else {
						print $conf->global->{$constname};
					}
					print '</td></tr>';
				}
			}

			// Service Provider URLs
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans('SAMLCONNECTOR_SP_METADATA_PATH'), '');
			print '</td><td class="samlCopyClipboard">';
			print '<span>'.dol_buildpath('/samlconnector/metadata.php', 2).'</span>';
			print '&nbsp;<i class="fa fa-clipboard"></i></tr>';

			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans('SAMLCONNECTOR_SP_ACS_URL'), '');
			print '</td><td class="samlCopyClipboard">';
			print '<span>'.dol_buildpath('/samlconnector/acs.php', 2).'</span>';
			print '&nbsp;<i class="fa fa-clipboard"></i></tr>';

			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans('SAMLCONNECTOR_SP_SLS_URL'), '');
			print '</td><td class="samlCopyClipboard">';
			print '<span>'.dol_buildpath('/samlconnector/sls.php', 2).'</span>';
			print '&nbsp;<i class="fa fa-clipboard"></i></tr>';

			print '</table>';
		}
	}

	if($setupnotempty && $conf->entity == 1) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=editSP&token='.$newToken.'">'.$langs->trans('Modify').'</a>';
		print '</div>';
	}
}

// Identity Provider (IDP) setup part
print load_fiche_titre($langs->trans('SamlConnectorAdminTitleTabIDP'), '', '');

if($action == 'editIDP' && $conf->entity == 1) {
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		print $formSetup->generateOutput(true);
	}
	else {
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$newToken.'">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

		foreach($parameterIDP as $constname => $val) {
			if($val['enabled'] == 1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
				print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
				print '</td>';
				printConfInput($constname, $val);
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';

		print '<br><div class="center">';
		print '<input class="button button-save" type="submit" value="'.$langs->trans('Save').'">';
		print '</div>';

		print '</form>';
	}

	print '<br>';
}
else {
	if($useFormSetup && (float) DOL_VERSION >= 15.0) {
		$formSetup = new FormSetup($db);
		if(! empty($formSetup->items)) {
			print $formSetup->generateOutput();
		}
	}
	else {
		if(! empty($parameterIDP)) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

			foreach($parameterIDP as $constname => $val) {
				if($val['enabled'] == 1) {
					$setupnotempty++;
					print '<tr class="oddeven"><td>';
					$tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
					print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp).'</span>';
					print '</td><td>';

					if($val['type'] == 'textarea') {
						print dol_nl2br($conf->global->{$constname});
					}
					else if($val['type'] == 'html') {
						print  $conf->global->{$constname};
					}
					else if($val['type'] == 'yesno') {
						print ajax_constantonoff($constname, array(), 0);
					}
					else if(preg_match('/emailtemplate:/', $val['type'])) {
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);

						$tmp = explode(':', $val['type']);

						$template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, $conf->global->{$constname});
						if($template < 0) {
							setEventMessages(null, $formmail->errors, 'errors');
						}
						print $langs->trans($template->label);
					}
					else if(preg_match('/category:/', $val['type'])) {
						$c = new Categorie($db);
						$result = $c->fetch($conf->global->{$constname});
						if($result < 0) {
							setEventMessages(null, $c->errors, 'errors');
						}
						else if($result > 0) {
							$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
							$toprint = [];
							foreach($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.($c->color ? ' style="background: #'.$c->color.';"' : ' style="background: #bbb"').'>'.$way.'</li>';
							}
							print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
						}
					}
					else if(preg_match('/thirdparty_type/', $val['type'])) {
						if($conf->global->{$constname} == 2) {
							print $langs->trans('Prospect');
						}
						else if($conf->global->{$constname} == 3) {
							print $langs->trans('ProspectCustomer');
						}
						else if($conf->global->{$constname} == 1) {
							print $langs->trans('Customer');
						}
						else if($conf->global->{$constname} == 0) {
							print $langs->trans('NorProspectNorCustomer');
						}
					}
					else if($val['type'] == 'product') {
						$product = new Product($db);
						$resprod = $product->fetch($conf->global->{$constname});
						if($resprod > 0) {
							print $product->ref;
						}
						else if($resprod < 0) {
							setEventMessages(null, $product->errors, 'errors');
						}
					}
					else if($val['type'] == 'array') {
						if(!empty($conf->global->{$constname})) print $langs->trans($conf->global->{$constname});
					}
					else {
						print $conf->global->{$constname};
					}
					print '</td></tr>';
				}
			}

			print '</table>';
		}
	}

	if($setupnotempty && $conf->entity == 1) {
		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=editIDP&token='.$newToken.'">'.$langs->trans('Modify').'</a>';
		print '</div>';
	}
}

//IDP ADD FORM
if(! in_array($action, array('editIDP', 'editSP'))) {
	print '<div id="multiple_idp">';
	$idp->printSetupAddForm();

	$TIdps = $idp->fetchAll();
	if(is_array($TIdps) && ! empty($TIdps)) {
		foreach($TIdps as $idp) {
			$idp->printSetupBloc();
		}
	}
	print '</div>';
}

if(empty($setupnotempty)) {
	print '<br>'.$langs->trans('NothingToSetup');
}

// Page end
print dol_get_fiche_end();

?>
    <script type="text/javascript">
        $(document).ready(function () {
            //Pour décocher l'input à la volée
            $('#del_SAMLCONNECTOR_IDP_DISPLAY_BUTTON').on('click', function () {
                if ($('#SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').length == 0 && $('#del_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').is(':visible')) {
                    $('#del_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').trigger('click');
                }
            });
            $('#SAMLCONNECTOR_IDP_DISPLAY_BUTTON').on('change', function () {
                if ($('#SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').val() == 1 && $('#SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').length > 0 && $('#SAMLCONNECTOR_IDP_DISPLAY_BUTTON').val() == 0) {
                    $('#SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').val('0').trigger('change');
                }
            });

            if ($('#del_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').is(':visible')) {
                $('#multiple_idp').show();
            } else {
                $('#multiple_idp').hide();
            }

            $('#set_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').on('click', function () {
                $('#multiple_idp').show();
            });
            $('#del_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').on('click', function () {
                $('#multiple_idp').hide();
            });

            showHideLineBySelector('SAMLCONNECTOR_MANAGE_MULTIPLE_IDP', 'SAMLCONNECTOR_IDP_METADATA_SOURCE', true);
            showHideLineBySelector('SAMLCONNECTOR_MANAGE_MULTIPLE_IDP', 'SAMLCONNECTOR_IDP_METADATA_URL', true);
            showHideLineBySelector('SAMLCONNECTOR_MANAGE_MULTIPLE_IDP', 'SAMLCONNECTOR_IDP_METADATA_XML_PATH', true);
            showHideLineBySelector('SAMLCONNECTOR_IDP_DISPLAY_BUTTON', 'SAMLCONNECTOR_MANAGE_MULTIPLE_IDP');

        });

        function showHideConfLine(selector, target, forceShow = false, forceHide = false, hideIfActive = false) {
            //Si on est en mode edit ou si on est en mode vu et que le bouton est en mode enable ou si nous cliquons sur le bouton pour enable la conf
            if (!forceHide && (($('#'+selector).val() == 1 && $('#'+selector).length > 0)
                || ($('#'+selector).length == 0 && $('#del_'+selector).is(':visible'))
                || forceShow)) {
                if (hideIfActive) $('#helplink'+target).closest('tr').hide();
                else $('#helplink'+target).closest('tr').show();
            } else {
                if (hideIfActive) $('#helplink'+target).closest('tr').show();
                else $('#helplink'+target).closest('tr').hide();
            }
        }

        function showHideLineBySelector(selectorTriggered, targetSelector, hideIfActive = false) {
            $('#set_'+selectorTriggered).on('click', function () {
                showHideConfLine(selectorTriggered, targetSelector, true, false, hideIfActive);
            });
            $('#del_'+selectorTriggered).on('click', function () {
                showHideConfLine(selectorTriggered, targetSelector, false, true, hideIfActive);
            });
            $('#'+selectorTriggered).on('change', function () {
                showHideConfLine(selectorTriggered, targetSelector, false, false, hideIfActive);
            });
            showHideConfLine(selectorTriggered, targetSelector, false, false, hideIfActive);
        }

        <?php if($conf->entity != 1) { //Pour disable les ajax on off?>
            $('#set_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').css('pointer-events','none').addClass('opacitymedium');
            $('#del_SAMLCONNECTOR_MANAGE_MULTIPLE_IDP').css('pointer-events','none').addClass('opacitymedium');
            $('#del_SAMLCONNECTOR_IDP_DISPLAY_BUTTON').css('pointer-events','none').addClass('opacitymedium');
            $('#set_SAMLCONNECTOR_IDP_DISPLAY_BUTTON').css('pointer-events','none').addClass('opacitymedium');
        <?php } ?>
    </script>
<?php

llxFooter();
$db->close();
