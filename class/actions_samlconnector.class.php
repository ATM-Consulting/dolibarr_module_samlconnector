<?php
/* Copyright (C) 2018 SuperAdmin
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

class ActionsSamlConnector {
    /** @var DoliDB Database handler. */
    public $db;

    /** @var string Error */
    public $error = '';

    /** @var array Errors */
    public $errors = [];

    /** @var array Hook results. Propagated to $hookmanager->resArray for later reuse */
    public $results = [];

    /** @var string String displayed by executeHook() immediately after return */
    public $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        global $langs;

        $this->db = $db;
        $langs->load('saml@saml');
    }

    /**
     * Execute action
     *
     * @param array        $parameters Array of parameters
     * @param CommonObject $object     The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string       $action     'add', 'update', 'view'
     * @return int                            <0 if KO, =0 if OK but we want to process standard actions too, >0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action) {
        global $db, $langs, $conf, $user;

        $this->resprints = '';
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters  Hook metadatas (context, etc...)
     * @param CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string       $action      Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager) {
        global $conf, $user, $langs;

//        $error = 0; // Error counter
//
//        /* print_r($parameters); print_r($object); echo "action: " . $action; */
//        if(in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2']))        // do something only for the context 'somecontext1' or 'somecontext2'
//        {
//            // Do what you want here...
//            // You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
//        }
//
//        if(! $error) {
//            $this->results = ['myreturn' => 999];
//            $this->resprints = 'A text to show';
//            return 0; // or return 1 to replace standard code
//        }
//        else {
//            $this->errors[] = 'Error message';
//            return -1;
//        }
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters  Hook metadatas (context, etc...)
     * @param CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string       $action      Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doMassActions($parameters, &$object, &$action, $hookmanager) {
        global $conf, $user, $langs;

//        $error = 0; // Error counter
//
//        /* print_r($parameters); print_r($object); echo "action: " . $action; */
//        if(in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2']))        // do something only for the context 'somecontext1' or 'somecontext2'
//        {
//            foreach($parameters['toselect'] as $objectid) {
//                // Do action on each object id
//
//            }
//        }
//
//        if(! $error) {
//            $this->results = ['myreturn' => 999];
//            $this->resprints = 'A text to show';
//            return 0; // or return 1 to replace standard code
//        }
//        else {
//            $this->errors[] = 'Error message';
//            return -1;
//        }
        return 0;
    }

    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters  Hook metadatas (context, etc...)
     * @param CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string       $action      Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreMassActions($parameters, &$object, &$action, $hookmanager) {
        global $conf, $user, $langs;

//        $error = 0; // Error counter
//
//        if(in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2']))        // do something only for the context 'somecontext1' or 'somecontext2'
//        {
//            $this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("SAMLMassAction").'</option>';
//        }
//
//        if(! $error) {
//            return 0; // or return 1 to replace standard code
//        }
//        else {
//            $this->errors[] = 'Error message';
//            return -1;
//        }
        return 0;
    }

    /**
     * Execute action
     *
     * @param array  $parameters      Array of parameters
     * @param Object $object          Object output on PDF
     * @param string $action          'add', 'update', 'view'
     * @return  int                    <0 if KO,
     *                                =0 if OK but we want to process standard actions too,
     *                                >0 if OK and we want to replace standard actions.
     */
    public function beforePDFCreation($parameters, &$object, &$action) {
        global $conf, $user, $langs;
//        global $hookmanager;
//
//        $outputlangs = $langs;
//
//        $ret = 0;
//        $deltemp = [];
//        dol_syslog(get_class($this).'::executeHooks action='.$action);
//
//        /* print_r($parameters); print_r($object); echo "action: " . $action; */
//        if(in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2']))        // do something only for the context 'somecontext1' or 'somecontext2'
//        {
//
//        }
//
//        return $ret;
        return 0;
    }

    /**
     * Execute action
     *
     * @param array  $parameters      Array of parameters
     * @param Object $pdfhandler      PDF builder handler
     * @param string $action          'add', 'update', 'view'
     * @return int                    <0 if KO,
     *                                =0 if OK but we want to process standard actions too,
     *                                >0 if OK and we want to replace standard actions.
     */
    public function afterPDFCreation($parameters, &$pdfhandler, &$action) {
        global $conf, $user, $langs;
//        global $hookmanager;
//
//        $outputlangs = $langs;
//
//        $ret = 0;
//        $deltemp = [];
//        dol_syslog(get_class($this).'::executeHooks action='.$action);
//
//        /* print_r($parameters); print_r($object); echo "action: " . $action; */
//        if(in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2']))        // do something only for the context 'somecontext1' or 'somecontext2'
//        {
//
//        }
//
//        return $ret;
        return 0;
    }

    /* Add here any other hooked methods... */
    /**
     * @param array $parameters
     * @throws \OneLogin\Saml2\Error
     */
	public function getLoginPageOptions($parameters) {
		global $langs, $conf;

		if (!empty($conf->global->SAMLCONNECTOR_IDP_DISPLAY_BUTTON)) {
			if (!empty($conf->global->SAMLCONNECTOR_MANAGE_MULTIPLE_IDP)) {
				dol_include_once('samlconnector/class/samlconnectoridp.class.php');
				$idp = new SamlConnectorIDP($this->db);
				$idp->ismultientitymanaged = 0;
				$TIdps = $idp->fetchAll();
				if (is_array($TIdps) && !empty($TIdps)) {
					$this->resprints = '<div class="samlConnectorLoginButtonBlock">';
					foreach ($TIdps as $idp) {
						$moreclass = '';
						if ($idp->status == SamlConnectorIDP::STATUS_INACTIVE) continue;
						if (!empty($conf->multicompany->enabled) && $conf->entity != $idp->entity) $moreclass .= 'hidden';
						$this->resprints .= '<div class="samlConnectorLoginButtonElement ' . $moreclass . '" data-entity="' . $idp->entity . '">';
						$this->resprints .= $idp->getLoginButton();
						$this->resprints .= '</div>';
					}
					$this->resprints .= '</div>
				<script type="text/javascript">
				$(document).ready(function(){
					$(".samlConnectorLoginButtonBlock").appendTo("#login-submit-wrapper");
                    $("#entity").on("change", function (){
                        let entity = $(this).val();
                        $(\'div[data-entity="\'+entity+\'"]\').removeClass("hidden");
                        $(\'div[data-entity][data-entity!="\'+entity+\'"]\').addClass("hidden");
                    })
				});
				</script>';
				}
			} else {
				$url = dol_buildpath('/samlconnector', 1).'/login.php';
				$this->resprints = '<p><a href="'.$url.'">'.$langs->trans('SamlConnectorConnectBySaml').'</a></p>';
			}
		}
		else {
			//Force login
			include dirname(__FILE__).'/../lib/autoload.php';
			$login = get_saml();

			$newpath = DOL_MAIN_URL_ROOT.'/index.php?mainmenu=home&leftmenu=home';
			$login->login($newpath);
		}
	}

    /**
     * @param array $parameters
     * @throws \OneLogin\Saml2\Error
     */
    public function afterLogout($parameters) {
        global $conf;

        if($_SESSION['dol_authmode'] != 'saml') return;

        $urlfrom = empty($_SESSION['urlfrom']) ? '' : $_SESSION['urlfrom'];

        $url = DOL_URL_ROOT.'/index.php';        // By default, go to login page
        if($urlfrom) $url = DOL_URL_ROOT.$urlfrom;
        if(! empty($conf->global->MAIN_LOGOUT_GOTO_URL)) $url = $conf->global->MAIN_LOGOUT_GOTO_URL;
		$fk_idp = intval($_SESSION['dol_samlconnector_fk_idp']);

        foreach(array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }

        include dirname(__FILE__).'/../lib/autoload.php';
        $login = get_saml($fk_idp);
        $login->logout($url);
    }

	public function selectForFormsListWhere($parameters, &$object, &$action, $hookmanager) {
		if($parameters['currentcontext'] == 'samlconnectorsetup') {
			$this->resprints = ' WHERE t.active = 1';
		}
	}
}
