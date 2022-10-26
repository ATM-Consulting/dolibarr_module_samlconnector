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

// Savable conf for SP
$parameterSP = [
    'SAMLCONNECTOR_SP_CERT_PATH' => ['type' => 'string', 'enabled' => 1],
    'SAMLCONNECTOR_SP_PRIV_KEY_PATH' => ['type' => 'string', 'enabled' => 1]
];

// Savable conf for IDP
$parameterIDP = [
    'SAMLCONNECTOR_IDP_URL' => ['type' => 'string', 'enabled' => 1],
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
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'samlconnector@samlconnector');

// Service Provider (SP) setup part
print load_fiche_titre($langs->trans('SamlConnectorAdminTitleTabSP'), '', '');

if($action == 'editSP') {
    if($useFormSetup && (float) DOL_VERSION >= 15.0) {
        $formSetup = new FormSetup($db);
        print $formSetup->generateOutput(true);
    }
    else {
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="update">';

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

        foreach($parameterSP as $constname => $val) {
            if($val['enabled'] == 1) {
                $setupnotempty++;
                print '<tr class="oddeven"><td>';
                $tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
                print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
                print '</td><td>';

                if($val['type'] == 'textarea') {
                    print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">'."\n";
                    print $conf->global->{$constname};
                    print "</textarea>\n";
                }
                else if($val['type'] == 'html') {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor = new DolEditor($constname, $conf->global->{$constname}, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
                    $doleditor->Create();
                }
                else if($val['type'] == 'yesno') {
                    print $form->selectyesno($constname, $conf->global->{$constname}, 1);
                }
                else if(preg_match('/category:/', $val['type'])) {
                    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
                    $formother = new FormOther($db);

                    $tmp = explode(':', $val['type']);
                    print img_picto('', 'category', 'class="pictofixedwidth"');
                    print $formother->select_categories($tmp[1], $conf->global->{$constname}, $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
                }
                else if(preg_match('/thirdparty_type/', $val['type'])) {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
                    $formcompany = new FormCompany($db);
                    print $formcompany->selectProspectCustomerType($conf->global->{$constname}, $constname);
                }
                else if($val['type'] == 'securekey') {
                    print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : $conf->global->{$constname}).'" size="40">';
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
                else if($val['type'] == 'product') {
                    if(! empty($conf->product->enabled) || ! empty($conf->service->enabled)) {
                        $selected = empty($conf->global->$constname) ? '' : $conf->global->$constname;
                        $form->select_produits($selected, $constname);
                    }
                }
                else {
                    print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth400' : $val['css']).'" value="'.$conf->global->{$constname}.'">';
                }
                print '</td></tr>';
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
        if(! empty($parameterSP)) {
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
                        print ajax_constantonoff($constname);
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

    if($setupnotempty) {
        print '<div class="tabsAction">';
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=editSP&token='.newToken().'">'.$langs->trans('Modify').'</a>';
        print '</div>';
    }
}

// Identity Provider (IDP) setup part
print load_fiche_titre($langs->trans('SamlConnectorAdminTitleTabIDP'), '', '');

if($action == 'editIDP') {
    if($useFormSetup && (float) DOL_VERSION >= 15.0) {
        $formSetup = new FormSetup($db);
        print $formSetup->generateOutput(true);
    }
    else {
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="update">';

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td style="width: 50%;">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';

        foreach($parameterIDP as $constname => $val) {
            if($val['enabled'] == 1) {
                $setupnotempty++;
                print '<tr class="oddeven"><td>';
                $tooltiphelp = $langs->trans($constname.'Tooltip') != $constname.'Tooltip' ? $langs->trans($constname.'Tooltip') : '';
                print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
                print '</td><td>';

                if($val['type'] == 'textarea') {
                    print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">'."\n";
                    print $conf->global->{$constname};
                    print "</textarea>\n";
                }
                else if($val['type'] == 'html') {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor = new DolEditor($constname, $conf->global->{$constname}, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
                    $doleditor->Create();
                }
                else if($val['type'] == 'yesno') {
                    print $form->selectyesno($constname, $conf->global->{$constname}, 1);
                }
                else if(preg_match('/category:/', $val['type'])) {
                    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
                    $formother = new FormOther($db);

                    $tmp = explode(':', $val['type']);
                    print img_picto('', 'category', 'class="pictofixedwidth"');
                    print $formother->select_categories($tmp[1], $conf->global->{$constname}, $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
                }
                else if(preg_match('/thirdparty_type/', $val['type'])) {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
                    $formcompany = new FormCompany($db);
                    print $formcompany->selectProspectCustomerType($conf->global->{$constname}, $constname);
                }
                else if($val['type'] == 'securekey') {
                    print '<input required="required" type="text" class="flat" id="'.$constname.'" name="'.$constname.'" value="'.(GETPOST($constname, 'alpha') ? GETPOST($constname, 'alpha') : $conf->global->{$constname}).'" size="40">';
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
                else if($val['type'] == 'product') {
                    if(! empty($conf->product->enabled) || ! empty($conf->service->enabled)) {
                        $selected = empty($conf->global->$constname) ? '' : $conf->global->$constname;
                        $form->select_produits($selected, $constname);
                    }
                }
                else {
                    print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth400' : $val['css']).'" value="'.$conf->global->{$constname}.'">';
                }
                print '</td></tr>';
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
                    print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
                    print '</td><td>';

                    if($val['type'] == 'textarea') {
                        print dol_nl2br($conf->global->{$constname});
                    }
                    else if($val['type'] == 'html') {
                        print  $conf->global->{$constname};
                    }
                    else if($val['type'] == 'yesno') {
                        print ajax_constantonoff($constname);
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
                    else {
                        print $conf->global->{$constname};
                    }
                    print '</td></tr>';
                }
            }

            print '</table>';
        }
    }

    if($setupnotempty) {
        print '<div class="tabsAction">';
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=editIDP&token='.newToken().'">'.$langs->trans('Modify').'</a>';
        print '</div>';
    }
}

if(empty($setupnotempty)) {
    print '<br>'.$langs->trans('NothingToSetup');
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
