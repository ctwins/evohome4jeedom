<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class evohome extends eqLogic {
	const CFG_USER_NAME = 'evoUserName';
	const CFG_PASSWORD = 'evoPassword';
	const CFG_LOCATION_ID = 'evoLocationId';
	const CFG_ACCURACY  = 'evoDecimalsNumber';
	const CONF_SCHEDULE_ID = 'scheduleFileId';
	const CONF_ZONE_ID = 'zoneId';
	const CMD_STATE = "etat";
	const CMD_SET_MODE = "setmode";
	const CMD_SAVE = 'save';
	const CMD_RESTORE = "restore";
	const CMD_DELETE = "delete";
	const CMD_TEMPERATURE_ID = 'temperature';
	const CMD_CONSIGNE_ID = 'consigne';
	const CMD_CONSIGNE_TYPE_ID = 'consigneType';
	const ID_NO_ZONE = -2;
	const ID_CONSOLE = -1;
	const MODE_AUTO = 'Auto';
	const MODE_ECO = 'AutoWithEco';
	const MODE_AWAY = 'Away';
	const MODE_DAYOFF = 'DayOff';
	const MODE_CUSTOM = 'Custom';
	const MODE_OFF = 'HeatingOff';
	const MODE_PERMANENT_ON = '1';
	const MODE_PERMANENT_OFF = '0';
	const ARG_CODE_MODE = 'codeMode';
	const ARG_FILE_NAME = 'fileName';
	const ARG_FILE_ID = 'fileId';
	// Codes selon WebAPI/emea/api/v1/temperatureControlSystem/%s/mode
	const CODE_MODE_AUTO = 0;
	const CODE_MODE_OFF = 1;
	const CODE_MODE_ECO = 2;
	const CODE_MODE_AWAY = 3;
	const CODE_MODE_DAYOFF = 4;
	const CODE_MODE_CUSTOM = 6;	// ?
	const LOG_INFO_ZONES = false;

	static function logDebug($msg) {
		log::add('evohome', 'debug', $msg);
	}
	static function logError($msg) {
		log::add('evohome', 'error', $msg);
	}

	static function gmtToLocalDateTime($gmtDateTime) {
		$dt = new DateTime($gmtDateTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone(config::byKey('timezone')));
		return $dt;
	}

	static function gmtToLocalHM($gmtDateTime) {
		$ts = self::gmtToLocalDateTime($gmtDateTime)->getTimestamp();
		return /*self::isAmPmMode()*/false ? date("g:i a", $ts) : date("G:i", $ts);
	}

	static function gmtToLocalDate($gmtDateTime) {
		return strftime('%x', self::gmtToLocalDateTime($gmtDateTime)->getTimestamp());
	}

	static function i18n($txt, $arg=null) {
		//self::logDebug("i18n $txt with " . json_encode($arg,true));
		if ( $arg == null ) {
			$txt = __($txt,__FILE__);
		} else {
			$txt = __($txt, __FILE__, $arg);
 			if ( !is_array($arg) ) {
				//self::logDebug("i18n cas 1");
				$txt = str_replace('{0}', $arg, $txt);
			} else {
				//self::logDebug("i18n cas 2");
				for ( $i=0 ; $i<count($arg) ; $i++ ) {
					//self::logDebug("i18n txt = $txt ,  a = [$_a]");
					$txt = str_replace("{".$i."}", $arg[$i], $txt);
					//self::logDebug("i18n txt = $txt");
				}
			}
		}
		return $txt;
	}

	public static function getBackColorForConsigne($consigne) {
		if ($consigne >= 25) $bg = "#f21f1f";
		else if ($consigne >= 22) $bg = "#ff5b1a";
		else if ($consigne >= 19) $bg = "#fa9e2d";
		else if ($consigne >= 16) $bg = "#2e9985";
		else $bg = "#247eb2";
		return $bg;
	}

	/*	* ************************* Attributs ****************************** */

	/*public static $_widgetPossibility = array('custom' => array(
			'visibility' => true,
			'displayName' => true,
			'displayObjectName' => true,
			'optionalParameters' => false,
			'background-color' => true,
			'text-color' => true,
			'border' => true,
			'border-radius' => true,
			'background-opacity' => true,
	));*/

	/************************ Static methods *****************************/

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('evohome') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'evohome_update';
		$return['state'] = 'ok';
		$return['progress_file'] = jeedom::getTmpFolder('evohome') . '/dependance';
		if (exec('which avconv | wc -l') == 0) {
			$return['state'] = 'nok';
		}
		if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "python\-imaging|python\-pil" | wc -l') == 0) {
			$return['state'] = 'nok';
		}
		if (exec(system::getCmdSudo() . system::get('cmd_check') . ' gd | grep php | wc -l') == 0) {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function cron() {
		self::logDebug('IN>> - cron');
		$infosZones = self::getInformationsAllZonesE2();
		if ( $infosZones != null && $infosZones['cached'] ) {
			self::logDebug('got cached data, nothing to do');
		//} else {
			//foreach (eqLogic::byType('evohome') as $evohome) {
			//	$evohome->refreshComponent($infosZones);
			//}
		}
		self::logDebug('<<OUT - cron');
	}

	/*
	 * Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {
	}
	*/

	/*
	 * Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {
	}
	*/

	/*
	 * Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily() {
	}
	*/

	public static function getParam($_name) {
		return config::byKey($_name, 'evohome');
	}

	static function cacheData($_name, $_content, $_duration) {
		$cache = new cache();
		$cache->setKey($_name);
		$cache->setValue($_content);
		$cache->setLifetime($_duration == null ? 9*60 : $_duration);
		$cache->save();
	}

	static function runPython($prgName, $parameters=null) {
		// TODO: système anti appel simultanté
		return shell_exec(
			'python '
			. dirname(__FILE__) . '/../../resources/' . $prgName
			. ' ' . self::getParam(self::CFG_USER_NAME) . ' ' . self::getParam(self::CFG_PASSWORD)
			. ($parameters == null ? '' : ' ' . $parameters)
			);
	}

	/* not used (yet) */
	static function setHistoryRetention($duration = '') {
		foreach (eqLogic::byType('evohome') as $evohome) {
//			self::logDebug('-- adjust history on ' . $evohome->getName());
			foreach ($evohome->getCmd('info') as $cmd) {
/* 				<select class="form-control cmdAttr" data-l1key="configuration" data-l2key="historyPurge">
					<option value="" selected="selected">Jamais</option>
					<option value="-1 day">1 jour</option>
					<option value="-7 days">7 jours</option>
					<option value="-1 month">1 mois</option>
					<option value="-3 month">3 mois</option>
					<option value="-6 month">6 mois</option>
					<option value="-1 year">1 an</option>
				*/
				if ( $cmd->getIsHistorized() ) {
					$cmd->setConfiguration('historyPurge','-1 day');
					//self::logDebug('-- cmd id=' . $cmd->getId() . ' - name=' . $cmd->getName() . ' h=' . $cmd->getConfiguration('historyPurge') . ']');
					$cmd->save();
				}
			}
		}
	}

	/*
	 * Read all Locations attached to the account
	 * Use Python library "EvoHome2"
	 */
	public static function listLocations() {
		self::logDebug('INT>> - listLocations');
		$cachedContent = cache::byKey('evohomeLocations');
		$locations = $cachedContent->getValue('');
		if ( $locations == '') {
			$td = time();
			$locations = self::runPython('LocationsInfosE2.py');
			$delay = time() - $td;
			self::cacheData('evohomeLocations', $locations);
			self::logDebug('<<OUT - listLocations from python in ' . $delay . ' sec.');
		} else {
			self::logDebug('<<OUT - listLocations from cache');
		}
		return json_decode($locations, true);
	}

	public static function getInformationsAllZonesE2($forceRefresh=false) {
		$execUnitId = rand(0,10000);
		self::logDebug('IN>> getInformationsAllZonesE2[' . $execUnitId . ']');
		$isRunning = cache::byKey('getInformationsAllZonesE2Running');
		while ( $isRunning->getValue('') != '' ) {
			self::logDebug('Python reading in progress, wait 5sec. for ending...');
			sleep(5);
			$isRunning = cache::byKey('getInformationsAllZonesE2Running');
		}
		$cachedContent = cache::byKey('evohomegetInformationsAllZonesE2');
		$zones = $cachedContent->getValue('');
		if ( $zones == '' or $forceRefresh ) {
			self::cacheData('getInformationsAllZonesE2Running', "true", 120);
			$td = time();
			$zones = self::runPython('InfosZonesE2.py', self::getParam(self::CFG_LOCATION_ID));
			$delay = time() - $td;
			self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from python in ' . $delay . ' sec.');
			$infosZones = json_decode($zones, true);
			$isRunningCache = cache::byKey('getInformationsAllZonesE2Running');
			if ( $isRunningCache != null ) $isRunningCache->remove();
			if ( !$infosZones['success'] ) {
				self::logError('Error while getInformationsAllZonesE2 : ' . $infosZones['error']);
				$infosZones = null;
			} else {
				self::cacheData('evohomegetInformationsAllZonesE2', $zones, 10*60 - $delay - 5);
				self::refreshAll($infosZones);
				$infosZones['cached'] = false;
			}
		} else {
			self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from cache');
			$infosZones = json_decode($zones, true);
			$infosZones['cached'] = true;
		}
		if ( self::LOG_INFO_ZONES ) {
			self::logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . '] : ' . $zones);
		} else {
			self::logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . ']');
		}
		return $infosZones;
	}

	public static function getConsole() {
		self::logDebug('IN>> getConsole');
		foreach (eqLogic::byType('evohome') as $evohome) {
			if ( $evohome->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE ) {
				self::logDebug('<<OUT getConsole : done !');
				return $evohome;
			}
		}
		self::logDebug('<<OUT getConsole : not found !');
		return null;
	}

	/*********************** Méthodes d'instance **************************/

	function refreshComponent($infosZones) {
		if ( $infosZones == null ) {
			return;
		}
		$this->injectInformationsFromZone($infosZones);
		$mc = cache::byKey('evohomeWidgetmobile' . $this->getId());
		$mc->remove();
		$mc = cache::byKey('evohomeWidgetdashboard' . $this->getId());
		$mc->remove();
		$this->toHtml('mobile');
		$this->toHtml('dashboard');
		$this->refreshWidget();
	}

	function refreshAll($infosZones) {
		if ( $infosZones == null ) {
			return;
		}
		foreach (eqLogic::byType('evohome') as $equipment) {
			$equipment->refreshComponent($infosZones);
		}
	}

	function createOrUpdateCmd($_order, $_logicalId, $_name, $_type, $_subType, $_isVisible, $_isHistorized) {
		$cmd = $this->getCmd(null, $_logicalId);
		$created = false;
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName(__($_name, __FILE__));
			$cmd->setLogicalId($_logicalId);
			$created = true;
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setOrder($_order);
		$cmd->setType($_type);
		$cmd->setSubType($_subType);
		$cmd->setIsVisible($_isVisible);
		if ( $_logicalId == self::CMD_TEMPERATURE_ID ) {
			self::logDebug('createOrUpdateCmd set THERMOSTAT_TEMPERATURE');
			$cmd->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
		} else if ( $_logicalId == self::CMD_CONSIGNE_ID ) {
			self::logDebug('createOrUpdateCmd set THERMOSTAT_SETPOINT');
			$cmd->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
		}
		if ( $_isHistorized == 1 ) {
			$cmd->setIsHistorized(1);
			$cmd->setConfiguration('historizeMode', 'none');
			$cmd->setConfiguration('historyPurge', '');
			$cmd->setConfiguration('repeatEventManagement', 'always');
		}
		$cmd->save();

		return $created;
	}

	function deleteCmd($c1, $c2, $c3) {
		$cmd = $this->getCmd(null, $c1);
		if (is_object($cmd)) $cmd->remove();

		$cmd = $this->getCmd(null, $c2);
		if (is_object($cmd)) $cmd->remove();

		$cmd = $this->getCmd(null, $c3);
		if (is_object($cmd)) $cmd->remove();
	}

	public function preInsert() {
	}

	public function postInsert() {
	}

	public function preSave() {
	}

	public function postSave() {
		self::logDebug('IN>> - postSave'); 
		$created = false;
		if ($this->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE) {
			self::deleteCmd(self::CMD_TEMPERATURE_ID, self::CMD_CONSIGNE_ID, self::CMD_CONSIGNE_TYPE_ID);
			$created = self::createOrUpdateCmd(0, self::CMD_STATE, '_state', 'info', 'string', 1, 0);
			self::createOrUpdateCmd(1, self::CMD_SET_MODE, '_setmode', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(2, self::CMD_SAVE, '_save', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(3, self::CMD_RESTORE, '_restore', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_DELETE, '_delete', 'action', 'other', 1, 0);
		}
		else if ($this->getConfiguration(self::CONF_ZONE_ID) > 0) {
			self::deleteCmd(self::CMD_STATE, self::CMD_SET_MODE, self::CMD_SAVE, self::CMD_RESTORE, self::CMD_DELETE);
			$created = self::createOrUpdateCmd(0, self::CMD_TEMPERATURE_ID, '_temperature', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(1, self::CMD_CONSIGNE_ID, '_setting', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(2, self::CMD_CONSIGNE_TYPE_ID, '_settingType', 'info', 'string', 1, 0);
		}

		if ( $created ) {
			$infosZones = self::getInformationsAllZonesE2();
			$this->injectInformationsFromZone($infosZones);
		}
		self::logDebug('<<OUT - postSave'); 
	}

	public function preUpdate() {
	}

	public function postUpdate() {
	}

	public function preRemove() {
	}

	public function postRemove() {
	}

	public function injectInformationsFromZone($infosZones) {
		if ( $infosZones == null ) {
			return;
		}
		$idZone = $this->getConfiguration(self::CONF_ZONE_ID);
		self::logDebug("IN>> - injectInformationsFromZone on zone " . $idZone);
		if ( $idZone == self::ID_NO_ZONE ) {
			return;
		}
		if ( $idZone == self::ID_CONSOLE) {
			// ...
			$tmp = $this->getCmd(null,self::CMD_STATE);
			if(is_object($tmp)){
				$etat = $infosZones['currentMode']
					. ";" . ($infosZones['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
					. ";" . $infosZones['untilMode'];
				$tmp->event($etat);
			}

		} else if ( $idZone > 0) {
			$infosZone = null;
			foreach ( $infosZones['zones'] as $tmp ) {
				//self::logDebug("injectInformationsFromZone - for infosZones, look in " . $tmp);
				if ( $tmp['id'] == $idZone ) {
					$infosZone = $tmp;
					break;
				}
			}
			if ( $infosZone == null ) {
				self::logError("<<OUT - injectInformationsFromZone - no data found on zone " . $idZone);
				return;
			}
			$tmp = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			if(is_object($tmp)){
				$tmp->event($infosZone['temperature']);
			}
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			if(is_object($tmp)){
				$tmp->event($infosZone['setPoint']);
			}
			$consigneType = $infosZone['status'] . ";" . $infosZone['until'];
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			if(is_object($tmp)){
				$tmp->event($consigneType);
			}

			self::logDebug('zone ' . $infosZone['name'] . ' : temp = ' . $infosZone['temperature'] . ', consigne = ' . $infosZone['setPoint'] . ', type = ' . $consigneType);
		}
		self::logDebug("<<OUT - injectInformationsFromZone");
	}

	private function getEtat() {
 		$console = self::getConsole();
		if ( $console != null ) {
			$cmdEtat = $console->getCmd(null,self::CMD_STATE);
			if ( $cmdEtat != null && is_object($cmdEtat) ) {
				return $cmdEtat->execCmd();
			}
		}
		return null;
	}

	private function applyRounding($temperatureNative) {
		$valRound = round($temperatureNative*100)/100;
		list($entier, $decRound) = split('[.]', number_format($valRound,2));
		switch ( self::getParam(self::CFG_ACCURACY) ) {
			case 1:
			// ceil to 0.5 (EvoHome native computation)
			if ( $decRound >= 50 ) $dec50 = 0.5; else $dec50 = 0;
			return number_format($entier + $dec50, 1);

			case 2:
			// classical round to 0.5
			$dec50 = round($decRound / 50) * 50 / 100;
			return number_format($entier + $dec50, 1);

			case 3:
			// classical round to 0.05
			$dec5 = round($decRound / 5) * 5 / 100;
			return number_format($entier + $dec5, 2);
			
			//case 4:
		}
		return number_format($valRound,2);
	}

 	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			//self::logDebug('** toHtml(version=' . $_version . ') ret1 = ' . $replace);
			return $replace;
		}
		//self::logDebug("IN>> - toHtml(" . $_version . ")");

		$version = jeedom::versionAlias($_version);
		$replace['#background-color#'] = '#F6F6FF';

		// CONSOLE
		$cmdEtat = $this->getCmd(null,self::CMD_STATE);
		$replace['#etatDisplay#'] = (is_object($cmdEtat) && $cmdEtat->getIsVisible()) ? "visible" : "none";
		if ( is_object($cmdEtat) ) {
			$replace_action = $this->preToHtml($_version);
			$replace_action['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

			$_etat = is_object($cmdEtat) ? $cmdEtat->execCmd() : '';
			// "Auto";1 / "AutoWithEco";1/0;H / Away;1/0;D / DayOff;1/0;D / Custom;1/0;D / HeatingOff;1
			// with 1=True ; 0=False ; is the permanentMonde flag
			// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
			$etat = explode(";",$_etat);
			$etatImg = 'ok_16.gif';
			if ( $etat[0] == self::MODE_AUTO ) {
				$etatImg = 'i_calendar.svg';
				$etatCode = self::CODE_MODE_AUTO;
			} else if ( $etat[0] == self::MODE_ECO ) {
				$etatImg = 'i_economy.svg';
				$etatCode = self::CODE_MODE_ECO;
			} else if ( $etat[0] == self::MODE_AWAY ) {
				$etatImg = 'i_away.svg';
				$etatCode = self::CODE_MODE_AWAY;
			} else if ( $etat[0] == self::MODE_DAYOFF ) {
				$etatImg = 'i_dayoff.svg';
				$etatCode = self::CODE_MODE_DAYOFF;
			} else if ( $etat[0] == self::MODE_CUSTOM ) {
				$etatImg = 'i_custom.svg';
				$etatCode = self::CODE_MODE_CUSTOM;
			} else if ( $etat[0] == self::MODE_OFF ) {
				$etatImg = 'i_off.svg';
				$etatCode = self::CODE_MODE_OFF;
			}
			$replace_action['#etatImg#'] = $etatImg;
			$replace_action['#etatCode#'] = $etatCode;
			# permanent
			//self::logDebug($etat[0] . " ; " . $etat[1] . " ; " . $etat[2]);
			if ( $etat[1] == self::MODE_PERMANENT_ON && $etat[0] != self::MODE_AUTO ) {
				//self::logDebug("CAS 1");
				$replace_action['#etatUntilImg#'] = 'override-active.png';
				$replace_action['#etatUntilDisplay#'] = 'none';
			}
			# delay
			else if ( $etat[1] == self::MODE_PERMANENT_OFF ) {
				//self::logDebug("CAS 2");
				$replace_action['#etatUntilImg#'] = 'temp-override-black.svg';
				// example : $ct[1] = "2018-01-28T23:00:00Z"
				$replace_action['#etatUntil#'] = $etat[0] == self::MODE_ECO ? self::gmtToLocalHM($etat[2]) : self::gmtToLocalDate($etat[2]);
				$replace_action['#etatUntilFull#'] = $etat[2];
				$replace_action['#etatUntilDisplay#'] = 'visible';
			}
			else {
				//self::logDebug("CAS 3");
				$replace_action['#etatUntilImg#'] = 'ok_16.gif';	// dummy
				$replace_action['#etatUntilDisplay#'] = 'none';
			}

			$options = '';
			$scheduleFileId = $this->getConfiguration(self::CONF_SCHEDULE_ID);
			$replace_action['#scheduleFileId#'] = $scheduleFileId;
			foreach ( self::getHebdoNames() as $hn) {
				$options .= '<option value="' . $hn['id'] . '"';
				if ( $hn['id'] == 0 or $hn['id'] == $scheduleFileId ) $options .= ' selected style="background-color:green;color:white;"';
				$options .= '>' . $hn['name'] . '</option>';
			}
			$replace_action['#options#'] = $options;

			// indicateur schedule modifié
			$saveColor = 'white';
			$saveTitle = self::i18n("_title.save");
			if ( $scheduleFileId != null ) {
				$scheduleSaved = self::getSchedule($scheduleFileId);
				if ( $scheduleSaved != null ) {
					$scheduleCurrent = self::getSchedule(0);
					if ( $scheduleCurrent != null ) {
						$_scheduleSaved = json_encode($scheduleSaved['zones']);
						$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
						if ( $_scheduleSaved != $_scheduleCurrent ) {
							$saveColor = 'orange';
							$saveTitle .= self::i18n("_title.save2");
						}
					}
				}
			}
			$replace_action['#title.save#'] = $saveTitle;
			$replace_action['#save.color#'] = $saveColor;

			foreach ($this->getCmd('action') as $cmd) {
				$replace_action['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			}

			// arguments names
			$replace_action['#argCodeMode#'] = self::ARG_CODE_MODE;
			$replace_action['#argFileName#'] = self::ARG_FILE_NAME;
			$replace_action['#argFileId#'] = self::ARG_FILE_ID;
			// codes mode
			$replace_action['#codeAuto#'] = self::CODE_MODE_AUTO;
			$replace_action['#codeEco#'] = self::CODE_MODE_ECO;
			$replace_action['#codeAway#'] = self::CODE_MODE_AWAY;
			$replace_action['#codeDayOff#'] = self::CODE_MODE_DAYOFF;
			$replace_action['#codeCustom#'] = self::CODE_MODE_CUSTOM;
			$replace_action['#codeOff#'] = self::CODE_MODE_OFF;
			// i18n
			$msg = array('scheduleTitle',
				'title.setMode', 'modeAuto', 'modeEco', 'modeAway', 'modeDayOff', 'modeCustom', 'modeOff', 'setModeInfoList',
				'title.showCurrent', 'title.showSelected', 'title.restore', 'title.delete',
				'saveAs', 'saveReplace', 'saveInfoList', 'restoreConfirm', 'restoreInfoList', 'deleteConfirm', 'deleteInfoList');
			foreach ( $msg as $m ) $replace_action["#$m#"] = self::i18n("_$m");

			$consoleContent = template_replace($replace_action, getTemplate('core', $version, 'console_content', 'evohome'));
			//self::logDebug('consoleContent='.$consoleContent);
			$replace['#consoleContent#'] = $consoleContent;
			$replace['#temperatureContent#'] = '';
		}
		else {
			$replace_temp = $this->preToHtml($_version);
			// *** TEMPERATURE
			$replace_temp['#etatImg#'] = 'ok_16.gif';	// dummy
			$replace_temp['#etatUntilImg#'] = 'ok_16.gif';	// dummy

			$cmdTemperature = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			$replace_temp['#temperatureId#'] = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
			$replace_temp['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "visible" : "none";
			$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
			if ( $temperatureNative == null ) {
				$replace_temp['#temperature#'] = '';
				$replace_temp['#temperatureImgDisplay#'] = 'visible;height:36px;width:36px;margin-top:8px;margin-bottom:8px;';
				$replace_temp['#temperatureDisplay2#'] = 'none';
			} else {
				$temperature = self::applyRounding($temperatureNative);
				$replace_temp['#temperature#'] = $temperature . '°';
				$replace_temp['#temperatureImgDisplay#'] = 'visible;height:15px;width:15px;';
				$replace_temp['#temperatureDisplay2#'] = 'visible';
			}

			// *** CONSIGNE
			$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			$replace_temp['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
			$replace_temp['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "visible" : "none";
			$consigne = is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0;
			$_etat = self::getEtat();
			$etat = $_etat == null ? null : explode(';',$_etat);
			$isOff = ($etat != null) && ($etat[0] == self::MODE_OFF);
			$replace_temp['#consigne#'] = $isOff ? 'OFF' : $consigne . '°';
			$replace_temp['#consigneBG#'] = $isOff ? 'black;' : self::getBackColorForConsigne($consigne);
			$replace_temp['#consigneBorder#'] = '0';

			$replace_temp['#temperatureImg#'] = $temperatureNative == null ? 'battlow.png' : ($temperatureNative < $consigne ? 'chauffage_on.gif' : 'ok_16.gif');

			$consigneType = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			$replace_temp['#consigneTypeId#'] = is_object($consigneType) ? $consigneType->getId() : '';
			$ct = is_object($consigneType) ? $consigneType->execCmd() : '';
			# $ct = 'FollowSchedule' / PermanentOverride / TemporaryOverride;2018-01-28T23:00:00Z
			$ct = explode(";", $ct);

			$consigneTypeImg = null;
			if ( is_object($consigneType) && !$consigneType->getIsVisible() ) {
				// ...
			}
			else {
				$consigneTip = '';
				$consigneTypeUntil = '';
				$consigneTypeUntilFull = '';
				$isEco = $etat != null && $etat[0] == self::MODE_ECO;
				$isAway = $etat != null && $etat[0] == self::MODE_AWAY;
				$isDayOff = $etat != null && $etat[0] == self::MODE_DAYOFF;
				if ( $isEco ) {
					$consigneTip = self::i18n("_tipEco", $consigne+3);
					$consigneTypeImg = 'i_economy_white.png';
				} else if ( $isAway ) {
					$consigneTip = self::i18n("_tipAway");
					$consigneTypeImg = 'i_away_white.png';
				} else if ( $isDayOff ) {
					$consigneTip = self::i18n("_tipDayOff");
					$consigneTypeImg = 'i_dayoff_white.png';
				}
				if ( $isOff ) {
					$consigneTip = self::i18n("_tipOff", $consigne);
					$consigneTypeImg = 'i_off_white.png';
				} else if ( !$isEco && !isAway && $ct[0] == 'FollowSchedule' ) {
					// ...
				} else if ( $ct[0] == 'TemporaryOverride' ) {
					$consigneTip = '';
					$consigneTypeImg = 'temp-override.svg';
					// example : $ct[1] = "2018-01-28T23:00:00Z"
					$time = self::gmtToLocalHM($ct[1]);
					$consigneTypeUntil = $time;
					$consigneTypeUntilFull = $ct[1];
				} else if ( $ct[0] == 'PermanentOverride' ) {
					$consigneTip = '';
					$consigneTypeImg = 'override-active.png';
					$consigneTypeUntil = '';
				}
				$replace_temp['#consigneTypeUntil#'] = $consigneTypeUntil;
				$replace_temp['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
				$replace_temp['#consigneTip#'] = $consigneTip;
			}
			$replace_temp['#consigneTypeImg#'] = $consigneTypeImg == null ? 'ok_16.gif' : $consigneTypeImg;
			$replace_temp['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'visible';

			$tempContent = template_replace($replace_temp, getTemplate('core', $version, 'temperature_content', 'evohome'));
			//self::logDebug('consoleContent='.$consoleContent);
			$replace['#consoleContent#'] = '';
			$replace['#temperatureContent#'] = $tempContent;
		}

		$html = template_replace($replace, getTemplate('core', $version, 'evohome', 'evohome'));
		//self::logDebug('html='.$html);
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		//self::logDebug("<<OUT - toHtml");
		return $html;
	}

	/*
	* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
	public static function postConfig_<Variable>() {
	}
	*/

	/*
	* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
	}
	*/

	/*	* ********************** Statics about Scheduling *************************** */

	static function getSchedule($fileId,$dateTime=0,$doRefresh=false) {
		self::logDebug('IN>> - getSchedule(' . $fileId . ')');
		if ( $fileId == 0 ) {
			$infosZones = self::getInformationsAllZonesE2($doRefresh);
			if ( $infosZones == null ) {
				self::logDebug('<<OUT - getSchedule(0) - error while getInformationsAllZonesE2 (see above)');
				return null;
			}
			$schedule = array('datetime' => $dateTime);
			$scheduleByZone = array();
			foreach ( $infosZones['zones'] as $zone ) {
				$scheduleByZone[] = array(
					'idZone' => $zone['id'],
					'name' => $zone['name'],
					'schedule' => $zone['schedule']);
			}
			$schedule['zones'] = $scheduleByZone;
			self::logDebug('<<OUT - getSchedule(0)');
			return $schedule;
		}
		
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos != null ) {
			$fileContent = file_get_contents($fileInfos['fullPath']);
			self::logDebug('getSchedule from ' . $fileInfos['fullPath']);
			//self::logDebug('content = ' . $fileContent);
			$fileContentDecoded = json_decode($fileContent, true);
			//self::logDebug('jsonDecoded = ' . $fileContentDecoded);
			self::logDebug('<<OUT - getSchedule(' . $fileId . ')');
			return $fileContentDecoded;
		}
		
		self::logDebug('<<OUT - getSchedule(' . $fileId . ') non trouvé');
		return null;
	}

	static function getSchedulePath() {
		$record_dir = dirname(__FILE__) . '/../../data/';
		if (!file_exists($record_dir)) {
			mkdir($record_dir, 0777, true);
		}
		return $record_dir;
	}

	static public function cmpHebdo($a,$b) {
		return strcmp($a['name'], $b['name']);
	}
	static public function getHebdoNames() {
		$liste = array();
		$schedulePath = self::getSchedulePath();
		foreach (ls($schedulePath, '*') as $file) {
			$parts = explode("_", $file, 2);
			$liste[] = array('id' => $parts[0],
							'name' => $parts[1],
							'fullPath' => $schedulePath . $file);
		}
		if ( count($liste) == 0 ) {
			$liste[] = array('id' => 0,
							'name' => 'indisponible',
							'fullPath' => '');
		} else {
			usort($liste, "evohome::cmpHebdo");
		}
		return $liste;
	}

	static function getFileInfosById($fileId) {
		foreach ( self::getHebdoNames() as $item ) {
			if ( $item['id'] == $fileId ) {
				return $item;
			}
		}
		return null;
	}

	/*	* ********************** Actions *************************** */

	function doCaseAction($paramAction, $parameters) {
		self::logDebug('doCaseAction(' . $paramAction . ', ' . json_encode($parameters) . ')');
		switch ($paramAction) {
			case self::CMD_SET_MODE:
				self::setMode($parameters[self::ARG_CODE_MODE]);
				break;

			case self::CMD_SAVE:
				self::saveSchedule($parameters[self::ARG_FILE_NAME], $parameters[self::ARG_FILE_ID]);
				break;

			case self::CMD_RESTORE:
				self::restoreSchedule($parameters[self::ARG_FILE_ID]);
				break;

			case self::CMD_DELETE:
				self::deleteSchedule($parameters[self::ARG_FILE_ID]);
				break;
		}
	}

	function setMode($codeMode) {
		self::logDebug('IN>> - setMode with code=' . $codeMode);

		// Call python function
		self::logDebug('setMode : call python');
		$td = time();
		$retValue = self::runPython('SetModeE2.py', self::getParam(self::CFG_LOCATION_ID) . ' ' . $codeMode);
		$delay = time() - $td;
		$aRet = json_decode($retValue, true);
		self::logDebug('setMode : python return in ' . $delay . 'sec : ' . $retValue);
		if ( !$aRet['success'] ) {
			self::logError("Error while setMode : [" . $aRet['error'] . "]");
			// this call used to remove the loading mask on the screen
			self::getConsole()->refreshComponent(self::getInformationsAllZonesE2());
		} else {
			// Wait 30 sec. because all modified infos (setting point) are not immediately available
			self::logDebug('setMode : wait 30 sec before call getInformationsAllZonesE2');
			sleep(30);
			self::refreshAll(self::getInformationsAllZonesE2(true));
		}

		self::logDebug('<<OUT - setMode');
	}

	function updateScheduleFileId($fileId, $doRefresh=false) {
		self::logDebug("IN>> - updateScheduleFileId");
		$console = self::getConsole();
		$console->setConfiguration(self::CONF_SCHEDULE_ID, $fileId);
		$console->save();
		$console->refreshComponent(self::getInformationsAllZonesE2($doRefresh));
		self::logDebug("<<OUT - updateScheduleFileId");
	}

	function saveSchedule($fileName, $fileId) {
		self::logDebug('IN>> - saveSchedule(' . $fileName . ', ' . $fileId . ')');
		$dateTime = time();
		if ( $fileId == 0 or $fileId == '0' ) {
			$fileId = $dateTime;
			$filePath = self::getSchedulePath() . $fileId . '_' . $fileName;
		} else {
			$fileInfos = self::getFileInfosById($fileId+0);
			$filePath = $fileInfos['fullPath'];
		}
		self::logDebug('launch save action with fileName=' . $filePath);
		// force refresh inside getInformationsAllZonesE2
		$schedule = self::getSchedule(0,$dateTime,true);
		if ( $schedule == null ) {
			self::logDebug('<<OUT - saveSchedule - error while getSchedule (see above)');
			// this call used to remove the loading mask on the screen
			$console->refreshComponent(self::getInformationsAllZonesE2($doRefresh));
		} else {
			$fp = fopen($filePath, 'w');
			fwrite($fp, json_encode($schedule));
			fclose($fp);

			$this->updateScheduleFileId($fileId);
			self::logDebug('<<OUT - saveSchedule');
		}
	}

	function restoreSchedule($fileId) {
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos == null ) {
			self::logError('restoreSchedule on unknown ID=' . $fileId);
			return;
		}
		self::logDebug('restoreSchedule on saving ID=' . $fileId . ', name=' . $fileInfos['name']);
		// Call python function
		self::logDebug('restoreSchedule : call python');
		$td = time();
		$retValue = self::runPython('RestaureZonesE2.py', self::getParam(self::CFG_LOCATION_ID) . ' "' . $fileInfos['fullPath'] . '"');
		$delay = time() - $td;
		self::logDebug('restoreSchedule : python return in ' . $delay . 'sec : ' . $retValue);
		$aRet = json_decode($retValue, true);
		if ( !$aRet['success'] ) {
			self::logError("Error while restoreSchedule : [" . $aRet['error'] . "]");
			// this call used to remove the loading mask on the screen
			self::getConsole()->refreshComponent(self::getInformationsAllZonesE2());
		} else {
			$this->updateScheduleFileId($fileId, true);
		}
	}

	function deleteSchedule($fileId) {
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos == null ) {
			self::logError('deleteSchedule on unknown ID=' . $fileId);
			return;
		}
		unlink($fileInfos['fullPath']);
		self::getConsole()->refreshComponent(self::getInformationsAllZonesE2());
	}

}

class evohomeCmd extends cmd {

	/*	* ************************* Attributs ****************************** */

	public static $_widgetPossibility = array('custom' => false);

	/*	* *********************** Methode static *************************** */

	/*	* ********************* Methode d'instance ************************* */

	/*
	* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
		return true;
	}
	*/

	public function execute($parameters = null) {
		$eqLogic = $this->getEqLogic();
		$paramAction = $this->getLogicalId();

		if ( $this->getType() == "action" ) {
			$eqLogic->doCaseAction($paramAction, $parameters);
		} else {
			throw new Exception(self::i18n("_cmdUnavailable"));
		}
		return true;
	}

}
