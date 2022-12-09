<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *      \file       htdocs/core/class/ccountry.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_samlconnector_idp_type dictionary
 */

// Put here all includes required by your class file
//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 *    Class to manage dictionary Countries (used by imports)
 */
class CSamlConnectorIdpType {
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	public $element = 'csamlconnectoridptype'; //!< Id that identify managed objects
	public $table_element = 'c_samlconnector_idp_type'; //!< Name of table without prefix where object is stored

	/**
	 * @var int ID
	 */
	public $id;

	public $code;
	public $img_path;

	/**
	 * @var string Countries libelle
	 */
	public $libelle;

	public $active;

	public $fields = array(
		'libelle' => array('type' => 'varchar(250)', 'libelle' => 'libelle', 'enabled' => 1, 'visible' => 1, 'position' => 15, 'notnull' => -1, 'showoncombobox' => '1')
	);

	/**
	 *  Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	/**
	 *  Create object into database
	 *
	 * @param User $user      User that create
	 * @param int  $notrigger 0=launch triggers after, 1=disable triggers
	 * @return     int                 <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if(isset($this->code)) {
			$this->code = trim($this->code);
		}
		if(isset($this->img_path)) {
			$this->img_path = trim($this->img_path);
		}
		if(isset($this->libelle)) {
			$this->libelle = trim($this->libelle);
		}
		if(isset($this->active)) {
			$this->active = trim($this->active);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'c_samlconnector_idp_type(';
		$sql .= 'rowid,';
		$sql .= 'code,';
		$sql .= 'img_path,';
		$sql .= 'libelle,';
		$sql .= 'active';
		$sql .= ') VALUES (';
		$sql .= ' '.(! isset($this->rowid) ? 'NULL' : "'".$this->db->escape($this->rowid)."'").',';
		$sql .= ' '.(! isset($this->code) ? 'NULL' : "'".$this->db->escape($this->code)."'").',';
		$sql .= ' '.(! isset($this->img_path) ? 'NULL' : "'".$this->db->escape($this->img_path)."'").',';
		$sql .= ' '.(! isset($this->libelle) ? 'NULL' : "'".$this->db->escape($this->libelle)."'").',';
		$sql .= ' '.(! isset($this->active) ? 'NULL' : "'".$this->db->escape($this->active)."'").'';
		$sql .= ')';

		$this->db->begin();

		dol_syslog(get_class($this).'::create', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if(! $resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
		}

		if(! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'c_samlconnector_idp_type');
		}

		// Commit or rollback
		if($error) {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this).'::create '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();

			return -1 * $error;
		}
		else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 * @param int    $id       Id object
	 * @param string $code     Code
	 * @param string $img_path Code ISO
	 * @return     int            >0 if OK, 0 if not found, <0 if KO
	 */
	public function fetch($id, $code = '', $img_path = '') {
		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= ' t.code,';
		$sql .= ' t.img_path,';
		$sql .= ' t.libelle,';
		$sql .= ' t.active';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_samlconnector_idp_type as t';
		if($id) {
			$sql .= ' WHERE t.rowid = '.((int) $id);
		}
		else if($code) {
			$sql .= " WHERE t.code = '".$this->db->escape(strtoupper($code))."'";
		}
		else if($img_path) {
			$sql .= " WHERE t.img_path = '".$this->db->escape(strtoupper($img_path))."'";
		}

		dol_syslog(get_class($this).'::fetch', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if($resql) {
			if($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				if($obj) {
					$this->id = $obj->rowid;
					$this->code = $obj->code;
					$this->img_path = $obj->img_path;
					$this->libelle = $obj->libelle;
					$this->active = $obj->active;
				}

				$this->db->free($resql);

				return 1;
			}
			else {
				return 0;
			}
		}
		else {
			$this->error = 'Error '.$this->db->lasterror();

			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 * @param User $user      User that modify
	 * @param int  $notrigger 0=launch triggers after, 1=disable triggers
	 * @return     int                 <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if(isset($this->code)) {
			$this->code = trim($this->code);
		}
		if(isset($this->img_path)) {
			$this->img_path = trim($this->img_path);
		}
		if(isset($this->libelle)) {
			$this->libelle = trim($this->libelle);
		}
		if(isset($this->active)) {
			$this->active = trim($this->active);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'c_samlconnector_idp_type SET';
		$sql .= ' code='.(isset($this->code) ? "'".$this->db->escape($this->code)."'" : 'null').',';
		$sql .= ' img_path='.(isset($this->img_path) ? "'".$this->db->escape($this->img_path)."'" : 'null').',';
		$sql .= ' libelle='.(isset($this->libelle) ? "'".$this->db->escape($this->libelle)."'" : 'null').',';
		$sql .= ' active='.(isset($this->active) ? $this->active : 'null').'';
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this).'::update', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if(! $resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
		}

		// Commit or rollback
		if($error) {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this).'::update '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();

			return -1 * $error;
		}
		else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 *  Delete object in database
	 *
	 * @param User $user      User that delete
	 * @param int  $notrigger 0=launch triggers after, 1=disable triggers
	 * @return    int                     <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'c_samlconnector_idp_type';
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this).'::delete', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if(! $resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
		}

		// Commit or rollback
		if($error) {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this).'::delete '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();

			return -1 * $error;
		}
		else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 * @param int    $withpicto             Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param string $option                On what the link point to ('nolink', ...)
	 * @param int    $notooltip             1=Disable tooltip
	 * @param string $morecss               Add more css on link
	 * @param int    $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return    string                                String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1) {
		global $langs, $conf;
		$img = '';
		if(!empty($this->img_path)) {
			$imgPath = DOL_DATA_ROOT.'/medias/'.ltrim($this->img_path, '/');//TODO
			if(is_file($imgPath)) $img = '<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&amp;file='.urlencode($this->img_path).'&entity=1"  height="21"/>&nbsp;&nbsp;&nbsp;';
		}
		if($withpicto === 2 ) return $img;
		return $img.$langs->trans($this->libelle);
	}
}
