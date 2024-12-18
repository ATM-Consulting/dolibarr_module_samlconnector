<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 SuperAdmin
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modSamlConnector extends DolibarrModules {
    /**
     * Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        global $conf, $langs;
        $langs->load('samlconnector@samlconnector');

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 514242; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'samlconnector';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = 'interface';

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleSamlConnectorName' not found (SamlConnector is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModuleSamlConnectorDesc' not found (SamlConnector is name of module).
        $this->description = 'SAML service provider';
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = 'SAML Service provider client';

        // Author
        $this->editor_name = 'ATM-Consulting';
        $this->editor_url = 'https://www.atm-consulting.fr/';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '2.0.7';
        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where SAMLCONNECTOR is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
        $this->picto = 'samlconnector@samlconnector';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = [
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 1,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 1,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 0,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => [
                '/samlconnector/css/samlconnector.css.php',
            ],
            // Set this to relative path of js file if module must load a js on all pages
            'js' => [
                '/samlconnector/js/samlconnector.js.php',
            ],
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => [
                'mainloginpage',
                'logout',
                'samlconnectorsetup'
            ],
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 1
        ];

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/samlconnector/temp","/samlconnector/subdir");
        $this->dirs = ['/medias/samlconnector/idp'];

        // Config pages. Put here list of php page, stored into samlconnector/admin directory, to use to setup module.
        $this->config_page_url = ['setup.php@samlconnector'];

        // Dependencies
        // A condition to hide module
        $this->hidden = false;
        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends = [];
        $this->requiredby = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        // The language file dedicated to your module
        $this->langfiles = ['samlconnector@samlconnector'];

        // Prerequisites
        $this->phpmin = [7, 1]; // Minimum version of PHP required by module
        $this->need_dolibarr_version = [10, -3]; // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        //$this->automatic_activation = array('FR'=>'SamlConnectorWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;								// If true, can't be disabled

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('SAMLCONNECTOR_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('SAMLCONNECTOR_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $this->const = [
//            1 => ['SAML_SSO', 'chaine', 'avalue', 'SSO Url', 1, 'allentities', 1],
//            2 => ['SAML_SSL', 'chaine', 'avalue', 'SLO Url', 1, 'allentities', 1]
        ];

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if(! isset($conf->samlconnector) || ! isset($conf->samlconnector->enabled)) {
            $conf->samlconnector = new stdClass();
            $conf->samlconnector->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = [];
        // Example:
        // $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@samlconnector:$user->rights->samlconnector->read:/samlconnector/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@samlconnector:$user->rights->othermodule->read:/samlconnector/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        //
        // Where objecttype can be
        // 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // 'contact'          to add a tab in contact view
        // 'contract'         to add a tab in contract view
        // 'group'            to add a tab in group view
        // 'intervention'     to add a tab in intervention view
        // 'invoice'          to add a tab in customer invoice view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'member'           to add a tab in fundation member view
        // 'opensurveypoll'	  to add a tab in opensurvey poll view
        // 'order'            to add a tab in customer order view
        // 'order_supplier'   to add a tab in supplier order view
        // 'payment'		  to add a tab in payment view
        // 'payment_supplier' to add a tab in supplier payment view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'project'          to add a tab in project view
        // 'stock'            to add a tab in stock view
        // 'thirdparty'       to add a tab in third party view
        // 'user'             to add a tab in user view
        // Dictionaries
        $this->dictionaries		= array('langs'				=> $this->name.'@'.$this->name,
                                           'tabname'			=> array(	MAIN_DB_PREFIX.'c_samlconnector_idp_type'
                                           ),
                                           'tablib'			=> array(	'SamlConnectorIdpType'
                                           ),
                                           'tabsql'			=> array(	'SELECT rowid, code,  libelle, img_path, active FROM '.MAIN_DB_PREFIX.'c_samlconnector_idp_type'
                                           ),
                                           'tabsqlsort'		=> array(	'rowid ASC'
                                           ),
                                           'tabfield'			=> array(	'code,libelle,img_path'
                                           ),
                                           'tabfieldvalue'		=> array(	'code,libelle,img_path'
                                           ),
                                           'tabfieldinsert'	=> array(	'code,libelle,img_path'
                                           ),
                                           'tabrowid'			=> array(	'rowid'
                                           ),
                                           'tabcond'			=> array(	$conf->samlconnector->enabled
                                           ),
                                           'tabhelp'			=> array(	array('img_path'	=> $langs->trans('SamlConnectorImgPathHelp'))
                                           )
        );	// Dictionaries
        /* Example:
        $this->dictionaries=array(
            'langs'=>'samlconnector@samlconnector',
            // List of tables we want to see into dictonnary editor
            'tabname'=>array("table1", "table2", "table3"),
            // Label of tables
            'tablib'=>array("Table1", "Table2", "Table3"),
            // Request to select fields
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
            // Sort order
            'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
            // List of fields (result of select to show dictionary)
            'tabfield'=>array("code,label", "code,label", "code,label"),
            // List of fields (list of fields to edit a record)
            'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
            // List of fields (list of fields for insert)
            'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid'=>array("rowid", "rowid", "rowid"),
            // Condition to show each dictionary
            'tabcond'=>array($conf->samlconnector->enabled, $conf->samlconnector->enabled, $conf->samlconnector->enabled),
            // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
            'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
        );
        */

        // Boxes/Widgets
        // Add here list of php file(s) stored in samlconnector/core/boxes that contains a class to show a widget.
        $this->boxes = [
            //  0 => array(
            //      'file' => 'samlconnectorwidget1.php@samlconnector',
            //      'note' => 'Widget provided by SamlConnector',
            //      'enabledbydefaulton' => 'Home',
            //  ),
            //  ...
        ];

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = [
            //  0 => array(
            //      'label' => 'MyJob label',
            //      'jobtype' => 'method',
            //      'class' => '/samlconnector/class/samlconnectoridp.class.php',
            //      'objectname' => 'SamlConnectorIDP',
            //      'method' => 'doScheduledJob',
            //      'parameters' => '',
            //      'comment' => 'Comment',
            //      'frequency' => 2,
            //      'unitfrequency' => 3600,
            //      'status' => 0,
            //      'test' => '$conf->samlconnector->enabled',
            //      'priority' => 50,
            //  ),
        ];
        // Example: $this->cronjobs=array(
        //    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->samlconnector->enabled', 'priority'=>50),
        //    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->samlconnector->enabled', 'priority'=>50)
        // );

        // Permissions provided by this module
        $this->rights = [];

        // Main menu entries to add
        $this->menu = [];

        // Exports profiles provided by this module
//        $r = 1;
        /* BEGIN MODULEBUILDER EXPORT SAMLCONNECTORIDP */ /*
        $langs->load("samlconnector@samlconnector");
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='SamlConnectorIDPLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]='samlconnectoridp@samlconnector';
        // Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
        $keyforclass = 'SamlConnectorIDP'; $keyforclassfile='/samlconnector/class/samlconnectoridp.class.php'; $keyforelement='samlconnectoridp@samlconnector';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        //$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
        //unset($this->export_fields_array[$r]['t.fieldtoremove']);
        //$keyforclass = 'SamlConnectorIDPLine'; $keyforclassfile='/samlconnector/class/samlconnectoridp.class.php'; $keyforelement='samlconnectoridpline@samlconnector'; $keyforalias='tl';
        //include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        $keyforselect='samlconnectoridp'; $keyforaliasextra='extra'; $keyforelement='samlconnectoridp@samlconnector';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$keyforselect='samlconnectoridpline'; $keyforaliasextra='extraline'; $keyforelement='samlconnectoridpline@samlconnector';
        //include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$this->export_dependencies_array[$r] = array('samlconnectoridpline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
        //$this->export_special_array[$r] = array('t.field'=>'...');
        //$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
        //$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'samlconnectoridp as t';
        //$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'samlconnectoridp_line as tl ON tl.fk_samlconnectoridp = t.rowid';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('samlconnectoridp').')';
        $r++; */ /* END MODULEBUILDER EXPORT SAMLCONNECTORIDP */

        // Imports profiles provided by this module
//        $r = 1;
        /* BEGIN MODULEBUILDER IMPORT SAMLCONNECTORIDP */ /*
        $langs->load("samlconnector@samlconnector");
        $this->import_code[$r]=$this->rights_class.'_'.$r;
        $this->import_label[$r]='SamlConnectorIDPLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->import_icon[$r]='samlconnectoridp@samlconnector';
        $this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'samlconnector_samlconnectoridp', 'extra' => MAIN_DB_PREFIX.'samlconnector_samlconnectoridp_extrafields');
        $this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
        $import_sample = array();
        $keyforclass = 'SamlConnectorIDP'; $keyforclassfile='/samlconnector/class/samlconnectoridp.class.php'; $keyforelement='samlconnectoridp@samlconnector';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
        $import_extrafield_sample = array();
        $keyforselect='samlconnectoridp'; $keyforaliasextra='extra'; $keyforelement='samlconnectoridp@samlconnector';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
        $this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'samlconnector_samlconnectoridp');
        $this->import_regex_array[$r] = array();
        $this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
        $this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
        $this->import_convertvalue_array[$r] = array(
            't.ref' => array(
                'rule'=>'getrefifauto',
                'class'=>(empty($conf->global->SAMLCONNECTOR_SAMLCONNECTORIDP_ADDON) ? 'mod_samlconnectoridp_standard' : $conf->global->SAMLCONNECTOR_SAMLCONNECTORIDP_ADDON),
                'path'=>"/core/modules/commande/".(empty($conf->global->SAMLCONNECTOR_SAMLCONNECTORIDP_ADDON) ? 'mod_samlconnectoridp_standard' : $conf->global->SAMLCONNECTOR_SAMLCONNECTORIDP_ADDON).'.php'
                'classobject'=>'SamlConnectorIDP',
                'pathobject'=>'/samlconnector/class/samlconnectoridp.class.php',
            ),
            't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
            't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
            't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
        );
        $r++; */
        /* END MODULEBUILDER IMPORT SAMLCONNECTORIDP */
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = ''): int {
        global $conf;
        $result = $this->_load_tables('/samlconnector/sql/');
        if($result < 0) return -1;  // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

        // Create extrafields during init
        //include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        //$extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('samlconnector_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'samlconnector@samlconnector', '$conf->samlconnector->enabled');
        //$result2=$extrafields->addExtraField('samlconnector_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'samlconnector@samlconnector', '$conf->samlconnector->enabled');
        //$result3=$extrafields->addExtraField('samlconnector_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'samlconnector@samlconnector', '$conf->samlconnector->enabled');
        //$result4=$extrafields->addExtraField('samlconnector_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'samlconnector@samlconnector', '$conf->samlconnector->enabled');
        //$result5=$extrafields->addExtraField('samlconnector_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'samlconnector@samlconnector', '$conf->samlconnector->enabled');

        $sql = [];

        if(! is_dir(DOL_DATA_ROOT.'/medias/samlconnector/idp')) {
            $this->create_dirs();
            $sourcePathDir = __DIR__.'/../../img/idp';
            $sourceDir = opendir($sourcePathDir);
            while($file = readdir($sourceDir)) {
                if(($file != '.') && ($file != '..')) copy($sourcePathDir.'/'.$file, DOL_DATA_ROOT.'/medias/samlconnector/idp/'.$file);
            }
        }



        // Document templates
//        $moduledir = dol_sanitizeFileName('samlconnector');
//        $myTmpObjects = [];
//        $myTmpObjects['SamlConnectorIDP'] = ['includerefgeneration' => 0, 'includedocgeneration' => 0];
//
//        foreach($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
//            if($myTmpObjectKey == 'SamlConnectorIDP') {
//                continue;
//            }
//            if($myTmpObjectArray['includerefgeneration']) {
//                $src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_samlconnectoridps.odt';
//                $dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
//                $dest = $dirodt.'/template_samlconnectoridps.odt';
//
//                if(file_exists($src) && ! file_exists($dest)) {
//                    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//                    dol_mkdir($dirodt);
//                    $result = dol_copy($src, $dest, 0, 0);
//                    if($result < 0) {
//                        $langs->load('errors');
//                        $this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
//                        return 0;
//                    }
//                }
//
//                $sql = array_merge($sql, [
//                    'DELETE FROM '.MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
//                    'INSERT INTO '.MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).')',
//                    'DELETE FROM '.MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
//                    'INSERT INTO '.MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).')'
//                ]);
//            }
//        }

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = ''): int {
        $sql = [];
        return $this->_remove($sql, $options);
    }
}
