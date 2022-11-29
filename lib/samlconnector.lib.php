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

/**
 * Prepare admin pages header
 *
 * @return array
 */
function samlconnectorAdminPrepareHead(): array {
    global $langs, $conf;

    $langs->load('samlconnector@samlconnector');

    $h = 0;
    $head = [];

    $head[$h][0] = dol_buildpath('/samlconnector/admin/setup.php', 1);
    $head[$h][1] = $langs->trans('Settings');
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath('/samlconnector/admin/samlSyncUsers.php', 1);
    $head[$h][1] = $langs->trans('samlSetupSyncUsers');
    $head[$h][2] = 'samlSyncUsers';
    $h++;

    $head[$h][0] = dol_buildpath('/samlconnector/admin/about.php', 1);
    $head[$h][1] = $langs->trans('About');
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@samlconnector:/samlconnector/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@samlconnector:/samlconnector/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'samlconnector@samlconnector');
    complete_head_from_modules($conf, $langs, null, $head, $h, 'samlconnector@samlconnector', 'remove');

    return $head;
}

/**
 * @param string $constname
 * @param array $val
 * @return void
 */
function printConfInput($constname, $val) {
	global $conf, $db, $form, $langs;
	print '<td>';
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
                else if($val['type'] == 'array') {
                    $data = $val['data'];

                    print Form::selectarray($constname, $data, $conf->global->$constname, 1, 0, 0, '', 1);
                }
                else {
                    print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth400' : $val['css']).'" value="'.$conf->global->{$constname}.'">';
                }
}
