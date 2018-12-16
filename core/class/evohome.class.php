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
	const CFG_LOCATION_DEFAULT_ID = -1;
	const CFG_ACCURACY  = 'evoDecimalsNumber';
	const CFG_TEMP_UNIT = 'evoTempUnit';
	const CFG_UNIT_CELSIUS = 'C';
	const CFG_UNIT_FAHRENHEIT = 'F';
	const CFG_LOADING_INTERVAL  = 'evoLoadingInterval';
	const CFG_HISTORY_RETENTION  = 'evoHistoryRetention';
	const CFG_REFRESH_BEFORE_SAVE  = 'evoRefreshBeforeSave';
	const CFG_DEF_SHOW_SCHEDULE_MODE  = 'evoDefaultShowingScheduleMode';
	const CFG_SCH_MODE_HORIZONTAL = 'H';
	const CFG_SCH_MODE_VERTICAL = 'V';
	const CFG_SHOWING_MODES = 'evoShowingModes';
	const CFG_SHOWING_MODES_CONSOLE = 'C';
	const CFG_SHOWING_MODES_POPUP = 'P';
	const CFG_SCH_EDIT_AVAILABLE = "evoEditAvailable";
	const iCFG_SCHEDULE_ID = 'intScheduleFileId';
	const CONF_ZONE_ID = 'zoneId';
	const CMD_STATE = "etat";
	const CMD_SET_MODE = "setmode";
	const CMD_SAVE = 'save';
	const CMD_RESTORE = "restore";
	const CMD_DELETE = "delete";
	const CMD_TEMPERATURE_ID = 'temperature';
	const CMD_CONSIGNE_ID = 'consigne';
	const CMD_SCH_CONSIGNE_ID = 'progConsigne';
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
	const ARG_FILE_REM = 'remark';
	const ARG_FILE_NEW_SCHEDULE = 'scheduleData';
	const ARG_ZONE_ID = 'zoneId';
	const CURRENT_SCHEDULE_ID = 0;
	// Codes selon WebAPI/emea/api/v1/temperatureControlSystem/%s/mode
	const CODE_MODE_AUTO = 0;
	const CODE_MODE_OFF = 1;
	const CODE_MODE_ECO = 2;
	const CODE_MODE_AWAY = 3;
	const CODE_MODE_DAYOFF = 4;
	const CODE_MODE_CUSTOM = 6;
	const LOG_INFO_ZONES = false;
	// Caches names
	const CACHE_CRON_TIMER = 'cronTimer';
	const CACHE_IAZ = 'evohomegetInformationsAllZonesE2';
	const CACHE_IAZ_RUNNING = 'getInformationsAllZonesE2Running';
	const CACHE_LIST_LOCATIONS = 'evohomeGetLocations';

	static function isDebug() {
		return log::getLogLevel(__CLASS__) == 100;
	}
	public static function logDebug($msg) {
		log::add(__CLASS__, 'debug', $msg);
	}
	public static function logError($msg) {
		log::add(__CLASS__, 'error', $msg);
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
	static function getDateHour() {
		return (new DateTime())->format('Y-m-d H:i:s');
	}

	static function i18n($txt, $arg=null) {
		if ( substr($txt,-1) == '}' ) $txt .= '__';
		$txt = __($txt, __FILE__);
		if ( substr($txt,-2) == '__' ) $txt = substr($txt,0,-2);
		if ( $arg == null ) return $txt;
		if ( !is_array($arg) ) return str_replace('{0}', $arg, $txt);
		for ( $i=0 ; $i<count($arg) ; $i++ ) $txt = str_replace("{".$i."}", $arg[$i], $txt);
		return $txt;
	}

	static function jsonDecode($text, $fnName) {
		if ( $text == null ) {
			return null;
		}
		// 2018-02-24 - fix for compatibility with PHP 7.xx (useless for PHP 5.xx)
		$text = str_replace('True', 'true', str_replace('False', 'false', $text));
		$aValue = json_decode($text, true);
		if ( json_last_error() != JSON_ERROR_NONE) {
			$aValue = null;
			self::logError('Error while ' . $fnName . ' : json error = ' . json_last_error() . ', input was [' . $text . ']');
		} else if ( self::isDebug() ) {
			self::logDebug('jsonDecode OK for ' . $fnName);
		}
		return $aValue;
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
		$script = dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance';
		return array('script' => $script, 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function dependancy_info() {
		$return = array();
		$return['log'] =  __CLASS__ . '_update';
		$return['state'] = 'ok';
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';

		$x = 'which avconv | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) {
			self::logDebug("dependancy_info 1/3 [$x] = [$r]");
		}
		if ($r == 0) {
			$return['state'] = 'nok';
		}

		$x = system::getCmdSudo() . system::get('cmd_check') . ' gd | grep php | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) {
			self::logDebug("dependancy_info 2/3 [$x] = [$r]");
		}
		if ($r == 0) {
			$return['state'] = 'nok';
		}

		/*$x = system::getCmdSudo() . system::get('cmd_check') . ' python-pip | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) {
			self::logDebug("dependancy_info 3/4 [$x] = [$r]");
		}
		if ($r == 0) {
			$return['state'] = 'nok';
		}*/

		$x = system::getCmdSudo() . 'pip list | grep evohomeclient | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) {
			self::logDebug("dependancy_info 3/3 [$x] = [$r]");
		}
		if ( $r == 0) {
			$return['state'] = 'nok';
		}

		return $return;
	}

	static function setParam($paramName, $value) {
		config::save($paramName, $value, __CLASS__);
	}

	static function getParam($paramName, $defValue=null) {
		$cfgValue = config::byKey($paramName, __CLASS__);
		return $cfgValue == null /*|| $cfgValue == ""*/ ? $defValue : $cfgValue;
	}

	static function lockCron() {
		self::setParam('functionality::cron::enable', 0);
	}

	static function unlockCron() {
		self::setParam('functionality::cron::enable', 1);
	}

	public static function cron() {
		self::logDebug('IN>> - cron');
		$cacheMarker = cache::byKey(self::CACHE_CRON_TIMER);
		$mark = $cacheMarker->getValue();
		if ( $mark == '' ) {
			$td = time();
			$di = self::dependancy_info();
			if ( $di['state'] != 'ok' ) {
				self::logDebug('<<OUT - cron - plugin not ready (dependency_info=NOK)');
			} else {
				if ( self::isDebug() ) {
					// error level to enforce reporting
					log::add('cron_execution', 'error', 'Launching getInformationsAllZonesE2 with refresh');
				}
				self::getInformationsAllZonesE2(true);
				$delay = time() - $td;
				$cacheDuration = intVal(self::getParam(self::CFG_LOADING_INTERVAL,10)) * 60 - $delay - 2;
				self::cacheData(self::CACHE_CRON_TIMER, "mark", $cacheDuration);
			}
		} else if ( self::isDebug() ) {
			$tsRemain = self::getCacheRemaining(self::CACHE_CRON_TIMER);
			self::logDebug("cron : wait for $tsRemain sec.");
		}
		self::logDebug('<<OUT - cron');
	}

	static function cacheData($cacheName, $content, $duration=null) {
		$cache = new cache();
		$cache->setKey($cacheName);
		$cache->setValue($content);
		$cache->setLifetime($duration == null ? 9*60 : $duration);
		$cache->save();
	}

	static function getCacheRemaining($cacheName) {
		$cache = cache::byKey($cacheName);
		$tsDtCache = DateTime::createFromFormat('Y-m-d H:i:s', $cache->getDatetime())->getTimestamp();
		$cacheDuration = $cache->getLifetime();
		return ($tsDtCache + $cacheDuration) - (new Datetime())->getTimestamp();
	}

	static function runPython($prgName, $parameters=null) {
		$credential = self::getParam(self::CFG_USER_NAME,'') . ' ' . self::getParam(self::CFG_PASSWORD,'');
		if ( $credential === ' ' ) {
			self::logDebug('runPython too early : account is not set yet');
			return null;
		}
		return shell_exec(
			'python ' . dirname(__FILE__) . '/../../resources/' . $prgName
				. ' ' . $credential . ($parameters == null ? '' : ' ' . $parameters)
			);
	}

	/*
	 * Read all Locations attached to the account
	 * Use Python library "EvoHome2"
	 */
	public static function listLocations() {
		self::logDebug('INT>> - listLocations');
		$cachedContent = cache::byKey(self::CACHE_LIST_LOCATIONS);
		$locations = $cachedContent->getValue();
		if ( $locations == '') {
			$td = time();
			$locations = self::runPython('LocationsInfosE2.py');
			$delay = time() - $td;
			self::cacheData(self::CACHE_LIST_LOCATIONS, $locations);
			if ( self::isDebug() ) {
				self::logDebug('<<OUT - listLocations from python in ' . $delay . ' sec.');
			}
		} else {
			self::logDebug('<<OUT - listLocations from cache');
		}
		return self::jsonDecode($locations, "listLocations");
	}

	static function getLocationId() {
		$locId = self::getParam(self::CFG_LOCATION_ID);
		// 2018-02-23 - fix when location not set yet (avoid a python error on arg3)
		return is_numeric($locId) ? $locId : self::CFG_LOCATION_DEFAULT_ID;
	}

	static function activateIAZReentrance($delay) {
		self::cacheData(self::CACHE_IAZ_RUNNING, "true", $delay);
	}
	static function deactivateIAZReentrance() {
		$runningCache = cache::byKey(self::CACHE_IAZ_RUNNING);
		if ( $runningCache != null ) $runningCache->remove();
	}
	static function waitingIAZReentrance() {
		$runningCache = cache::byKey(self::CACHE_IAZ_RUNNING);
		$wasWaiting = false;
 		while ( $runningCache->getValue() != '' ) {
			self::logDebug('InformationsAllZonesE2 (python) reading in progress, wait 5sec. for ending...');
			sleep(5);
			$runningCache = cache::byKey(self::CACHE_IAZ_RUNNING);
			$wasWaiting = true;
		}
		return $wasWaiting;
	}

	public static function getInformationsAllZonesE2($forceRefresh=false, $readSchedule=1) {
		$execUnitId = rand(0,10000);
		if ( self::isDebug() ) {
			self::logDebug('IN>> - getInformationsAllZonesE2[' . $execUnitId . ']');
		}
		$cachedContent = cache::byKey(self::CACHE_IAZ);
		$zones = $cachedContent->getValue();
		$decodeCachedData = true;
		if ( $zones == '' || $forceRefresh ) {
			if ( self::waitingIAZReentrance() ) {
				$cachedContent = cache::byKey(self::CACHE_IAZ);
				$zones = $cachedContent->getValue();
				// a reading has just been done
			} else {
				self::activateIAZReentrance(120);
				$td = time();
				$zones = self::runPython('InfosZonesE2.py', self::getLocationId() . ' ' . $readSchedule);
				if ( self::isDebug() ) {
					self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from python in ' . (time() - $td) . ' sec.');
				}
				$infosZones = self::jsonDecode($zones, 'getInformationsAllZonesE2(python)');
				self::deactivateIAZReentrance();
				if ( $infosZones != null ) {
					if ( !$infosZones['success'] ) {
						self::logError('Error while getInformationsAllZonesE2 : ' . json_encode($infosZones));
						if ( self::isDebug() ) {
							self::logDebug(' -- datas received = : ' . $zones);
						}
						$infosZones = null;
					} else {
						self::logDebug('getInformationsAllZonesE2 : ' . json_encode($infosZones));
						$cacheDuration = 30*60;	//(intVal(self::getParam(self::CFG_LOADING_INTERVAL,10)) + 3) * 60;
						self::cacheData(self::CACHE_IAZ, $zones, $cacheDuration);
						if ( $readSchedule == 1 ) {
							self::refreshAll($infosZones);
						}
						$infosZones['cached'] = false;
					}
				}
				$decodeCachedData = false;
			}
		}
		if ( $decodeCachedData ) {
			if ( self::isDebug() ) {
				$tsRemain = self::getCacheRemaining(self::CACHE_IAZ);
				self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from cache (rest to live=' . $tsRemain . ')');
			}
			$infosZones = self::jsonDecode($zones, 'getInformationsAllZonesE2(cache)');
			$infosZones['cached'] = true;
		}
		if ( self::isDebug() ) {
			if ( self::LOG_INFO_ZONES && $infosZones != null ) {
				self::logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . '] : ' . $zones);
			} else {
				self::logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . ']');
			}
		}
		return $infosZones;
	}

	const C2BG = array(25=>'#f21f1f',
					   22=>'#ff5b1a',
					   19=>'#fa9e2d',
					   16=>'#2e9985',
					    0=>'#247eb2');
	const F2BG = array((25* 9/5 + 32)=>'#f21f1f',
					   (22* 9/5 + 32)=>'#ff5b1a',
					   (19* 9/5 + 32)=>'#fa9e2d',
					   (16* 9/5 + 32)=>'#2e9985',
					                0=>'#247eb2');
	public static function getBackColorForTemp($consigne,$isOff=false) {
		if ( $isOff ) return 'black';
		$X2BG = self::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) == self::CFG_UNIT_CELSIUS ? self::C2BG : self::F2BG;
		foreach ( $X2BG as $ref=>$bgRef ) {
			if ($consigne >= $ref) {
				$bg = $bgRef;
				break;
			}
		}
		return $bg;
	}

	static function getConsole() {
		self::logDebug('IN>> - getConsole');
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			if ( $equipment->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE ) {
				self::logDebug('<<OUT getConsole : done !');
				return $equipment;
			}
		}
		self::logDebug('<<OUT - getConsole : not found !');
		return null;
	}

	static function refreshConsole() {
		self::logDebug('IN>> - refreshConsole');
		$console = self::getConsole();
		if ( $console != null ) {
			$console->refreshComponent(self::getInformationsAllZonesE2());
		}
		self::logDebug('<<OUT - refreshConsole');
	}

	public static function getScheduleSubTitle($fileId, $scheduleToShow, $targetOrientation, $zoneId, $typeSchedule, $isEdit=false) {
		$infoDiff = '';
		if ( $fileId == 0) {
			$subTitle = self::i18n("Programmation courante");
		} else {
			$dt = new DateTime();
			$dt->setTimestamp($scheduleToShow['datetime']);
			$subTitle = self::i18n("Programmation de '{0}' créée le {1} à {2}", [self::getFileInfosById($fileId)['name'], $dt->format('Y-m-d'), $dt->format('H:i:s')]);
			if ( !$isEdit ) {
				$scheduleCurrent = self::getSchedule(0);
				if ( json_encode($scheduleToShow['zones']) != json_encode($scheduleCurrent['zones']) ) {
					$infoDiff = self::i18n("différente de la programmation courante");
				} else {
					$infoDiff = self::i18n("identique à la programmation courante");
				}
			}
		}
		if ( !$isEdit ) {
			if ( $zoneId == 0 ) {
				$ssf = "showSchedule($fileId,'$targetOrientation',0);";
			} else {
				$ssf = "showSchedule_$zoneId('$typeSchedule','$targetOrientation');";
			}
			$lbl = self::i18n($targetOrientation == 'V' ? "Vertical" : "Horizontal");
			$subTitle = "<a class='btn btn-success btn-sm tooltips' onclick=\\\"$ssf\\\">$lbl</a>&nbsp;$subTitle";
		} else {
			$infoDiff = self::i18n("Mode édition");
		}
		$subTitle .= $infoDiff == '' ? '' : "<br/><i>$infoDiff</i>";
		return $subTitle;
	}

	static function refreshAll($infosZones) {
		self::logDebug("IN>> - refreshAll");
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			$equipment->refreshComponent($infosZones);
		}
		self::logDebug("<<OUT - refreshAll");
	}

	public static function getEquNamesAndId() {
		$table = array();
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			$table[$equipment->getConfiguration(self::CONF_ZONE_ID)] = $equipment->getName();
		}
		self::logDebug('getEquNamesAndId : ' . json_encode($table));
		return $table;
	}

	/*********************** Méthodes d'instance **************************/

	function refreshComponent($infosZones) {
		if ( $infosZones != null ) {
			$this->injectInformationsFromZone($infosZones);
		}
		$mc = cache::byKey('evohomeWidgetmobile' . $this->getId());
		$mc->remove();
		$this->toHtml('mobile');

		$mc = cache::byKey('evohomeWidgetdashboard' . $this->getId());
		$mc->remove();
		$this->toHtml('dashboard');

		$this->refreshWidget();
	}

	function createOrUpdateCmd($order, $logicalId, $name, $type, $subType, $isVisible, $isHistorized) {
		$cmd = $this->getCmd(null, $logicalId);
		$created = false;
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName(self::i18n($name));
			$cmd->setLogicalId($logicalId);
			$created = true;
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setOrder($order);
		$cmd->setType($type);
		$cmd->setSubType($subType);
		$cmd->setIsVisible($isVisible);
		if ( $logicalId == self::CMD_TEMPERATURE_ID ) {
			self::logDebug('createOrUpdateCmd set THERMOSTAT_TEMPERATURE');
			$cmd->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
		} else if ( $logicalId == self::CMD_CONSIGNE_ID || $logicalId == self::CMD_SCH_CONSIGNE_ID ) {
			self::logDebug('createOrUpdateCmd set THERMOSTAT_SETPOINT');
			$cmd->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
		}
		if ( $isHistorized == 1 ) {
			$cmd->setIsHistorized(1);
			$cmd->setConfiguration('historizeMode', 'none');
			$cmd->setConfiguration('historyPurge', '');
			$cmd->setConfiguration('repeatEventManagement', 'always');
		}
		// flags for the evohoms.js
		$cmd->setConfiguration('canBeVisible', $isVisible);
		$cmd->setConfiguration('canBeHistorize', $isHistorized);
		$cmd->save();

		return $created;
	}

	function deleteCmd($cmds) {
		foreach ( $cmds as $cmd ) {
			$cmd = $this->getCmd(null, $cmd);
			if (is_object($cmd)) $cmd->remove();
		}
	}

	public function postSave() {
		self::logDebug('IN>> - postSave'); 
		$created = false;
		if ($this->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE) {
			self::logDebug('postSave - create Console widget');
			self::deleteCmd([self::CMD_TEMPERATURE_ID, self::CMD_CONSIGNE_ID, self::CMD_SCH_CONSIGNE_ID, self::CMD_CONSIGNE_TYPE_ID]);
			$created = self::createOrUpdateCmd(0, self::CMD_STATE, 'Etat', 'info', 'string', 1, 0);
			self::createOrUpdateCmd(1, self::CMD_SET_MODE, 'Réglage mode', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(2, self::CMD_SAVE, 'Sauvegarder', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(3, self::CMD_RESTORE, 'Restaure', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_DELETE, 'Supprimer', 'action', 'other', 1, 0);
		}
		else if ($this->getConfiguration(self::CONF_ZONE_ID) > 0) {
			self::logDebug('postSave - create TH widget');
			self::deleteCmd([self::CMD_STATE, self::CMD_SET_MODE, self::CMD_SAVE, self::CMD_RESTORE, self::CMD_DELETE]);
			$created = self::createOrUpdateCmd(0, self::CMD_TEMPERATURE_ID, 'Température', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(1, self::CMD_CONSIGNE_ID, 'Consigne', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(2, self::CMD_SCH_CONSIGNE_ID, 'Consigne programmée', 'info', 'numeric', 0, 1);
			self::createOrUpdateCmd(3, self::CMD_CONSIGNE_TYPE_ID, 'Type Consigne', 'info', 'string', 1, 0);
		}

		//if ( $created ) {
			self::logDebug('postSave - object created, now inject current values'); 
			$infosZones = self::getInformationsAllZonesE2();
			$this->injectInformationsFromZone($infosZones);
		//}

		if ( self::isDebug() ) {
			self::logDebug('<<OUT - postSave : ' . $this->getConfiguration(self::CONF_ZONE_ID)); 
		}
	}

	public function preUpdate() {
	}

	public function postUpdate() {
	}

	public function preRemove() {
	}

	public function postRemove() {
	}

	function adjustByUnit($temp, $unitsFrom) {
		if ( $temp == null ) {
			return null;
		}
		if ( substr($unitsFrom,0,1) == self::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) ) {
			return $temp;
		}
		if ( $unitsFrom == 'Celsius' ) {
			// >> Fahrenheit
			return $temp * 9/5 + 32;
		}
		if ( $unitsFrom == 'Fahrenheit' ) {
			// >> Celsius
			return ($temp - 32) * 5/9;
		}
	}

	function injectInformationsFromZone($infosZones) {
		if ( $infosZones == null ) {
			return;
		}
		$zoneId = $this->getConfiguration(self::CONF_ZONE_ID);
		if ( self::isDebug() ) {
			self::logDebug("IN>> - injectInformationsFromZone on zone " . $zoneId);
		}
		if ( $zoneId == self::ID_NO_ZONE ) {
			return;
		}
		if ( $zoneId == self::ID_CONSOLE) {
			// ...
			$tmp = $this->getCmd(null,self::CMD_STATE);
			if(is_object($tmp)){
				$etat = $infosZones['currentMode']
					. ";" . ($infosZones['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
					. ";" . $infosZones['untilMode'];
				$tmp->event($etat);
			}

		} else if ( $zoneId > 0) {
			$infosZone = null;
			foreach ( $infosZones['zones'] as $tmp ) {
				//self::logDebug("injectInformationsFromZone - for infosZones, look in " . $tmp);
				if ( $tmp['zoneId'] == $zoneId ) {
					$infosZone = $tmp;
					break;
				}
			}
			if ( $infosZone == null ) {
				self::logError("<<OUT - injectInformationsFromZone - no data found on zone " . $zoneId);
				return;
			}
			$tmp = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			if (is_object($tmp) ) {
				$tmp->event(self::adjustByUnit($infosZone['temperature'],$infosZone['units']));
			}
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$tmp->event(self::adjustByUnit($infosZone['setPoint'],$infosZone['units']));
			}
			$tmp = $this->getCmd(null,self::CMD_SCH_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$currentConsigne = self::getCurrentConsigneForZone($infosZone);
				$tmp->event(self::adjustByUnit($currentConsigne,$infosZone['units']));
			}
			$consigneType = $infosZone['status'] . ";" . $infosZone['until'] . ";" . $infosZone['units'];
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			if (is_object($tmp) ) {
				$tmp->event($consigneType);
			}

			if ( self::isDebug() ) {
				self::logDebug('zone ' . $zoneId . '=' . $infosZone['name'] . ' : temp = ' . $infosZone['temperature'] . ', consigne = ' . $infosZone['setPoint'] . ', type = ' . $consigneType);
			}
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
		list($entier, $decRound) = explode('.', number_format($valRound,2));
		switch ( self::getParam(self::CFG_ACCURACY,1) ) {
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

	function getCurrentConsigne($currentSchedule,$zoneId) {
		if ( $currentSchedule != null ) {
			foreach ( $currentSchedule['zones'] as $myData ) {
				if ( $myData['zoneId'] == $zoneId ) {
					return self::getCurrentConsigneForZone($myData);
				}
			}
		}
		return null;
	}

	function getCurrentConsigneForZone($myData) {
		$currentDay = strftime('%u', time())-1;
		$currentTime = strftime('%H:%M', time());
		$dsSunday = $myData['schedule']['DailySchedules'][6];
		$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
		$lastTemp = $spSundayLast['heatSetpoint'];
		foreach ( $myData['schedule']['DailySchedules'] as $ds ) {
			$mark = 0;
			$midnightAdded = $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00';
			if ( $midnightAdded ) {
				array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'heatSetpoint'=>$lastTemp));
			}
			for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
				$sp = $ds['Switchpoints'][$i-1];
				$hm = substr($sp['TimeOfDay'],0,5);
				if ( $ds['DayOfWeek'] == $currentDay ) {
					if ( $i == sizeof($ds['Switchpoints']) ) {
						$mark++;
					} else {
						$spNext = $ds['Switchpoints'][$i];
						$hmNext = substr($spNext['TimeOfDay'],0,5);
						if ( $hmNext > $currentTime ) {
							$mark++;
						}
					}
				}
				$lastTemp = $sp['heatSetpoint'];
				if ( $mark == 1 ) return $lastTemp;
			}
		}
		return null;
	}

 	public function toHtml($pVersion = 'dashboard') {
		$replace = $this->preToHtml($pVersion);
		if (!is_array($replace)) {
			return $replace;
		}

		$version = jeedom::versionAlias($pVersion);
		$replace['#background-color#'] = '#F6F6FF';

		$zoneId = $this->getConfiguration(self::CONF_ZONE_ID);
		$scheduleCurrent = self::getSchedule(self::CURRENT_SCHEDULE_ID);

		// CONSOLE
		$cmdEtat = $this->getCmd(null,self::CMD_STATE);
		if ( is_object($cmdEtat) ) {
			//$replace['#width#'] = '220px';
			$replace_action = $this->preToHtml($pVersion);
			$replace_action['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

			$_etat = is_object($cmdEtat) ? $cmdEtat->execCmd() : '';
			// "Auto";1 / "AutoWithEco";1/0;H / Away;1/0;D / DayOff;1/0;D / Custom;1/0;D / HeatingOff;1
			// with 1=True ; 0=False ; is the permanentMonde flag
			// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
			$aEtat = explode(';',$_etat);
			$etatImg = 'empty.svg';
			$etatCode = '';
			if ( $aEtat[0] == self::MODE_AUTO ) {
				$etatImg = 'i_calendar.svg';
				$etatCode = self::CODE_MODE_AUTO;
			} else if ( $aEtat[0] == self::MODE_ECO ) {
				$etatImg = 'i_economy.svg';
				$etatCode = self::CODE_MODE_ECO;
			} else if ( $aEtat[0] == self::MODE_AWAY ) {
				$etatImg = 'i_away.svg';
				$etatCode = self::CODE_MODE_AWAY;
			} else if ( $aEtat[0] == self::MODE_DAYOFF ) {
				$etatImg = 'i_dayoff.svg';
				$etatCode = self::CODE_MODE_DAYOFF;
			} else if ( $aEtat[0] == self::MODE_CUSTOM ) {
				$etatImg = 'i_custom.svg';
				$etatCode = self::CODE_MODE_CUSTOM;
			} else if ( $aEtat[0] == self::MODE_OFF ) {
				$etatImg = 'i_off.svg';
				$etatCode = self::CODE_MODE_OFF;
			}
			$replace_action['#etatImg#'] = $etatImg;
			$replace_action['#etatCode#'] = $etatCode;
			# permanent
			if ( $aEtat[1] == self::MODE_PERMANENT_ON && $aEtat[0] != self::MODE_AUTO ) {
				$replace_action['#etatUntilImg#'] = 'override-active.png';
				$replace_action['#etatUntilDisplay#'] = 'none';
			}
			# delay
			else if ( $aEtat[1] == self::MODE_PERMANENT_OFF ) {
				$replace_action['#etatUntilImg#'] = 'temp-override-black.svg';
				// example : $ct[1] = "2018-01-28T23:00:00Z"
				$replace_action['#etatUntil#'] = $etatCode == self::CODE_MODE_ECO ? self::gmtToLocalHM($etat[2]) : self::gmtToLocalDate($etat[2]);
				$replace_action['#etatUntilFull#'] = $aEtat[2];
				$replace_action['#etatUntilDisplay#'] = 'visible';
			}
			else {
				$replace_action['#etatUntilImg#'] = 'empty.svg';	// dummy
				$replace_action['#etatUntilDisplay#'] = 'none';
			}

			$options = '';
			$scheduleFileId = self::getParam(self::iCFG_SCHEDULE_ID,0);
			$jsScheduleFileId = 0;
			foreach ( self::getHebdoNames() as $hn) {
				$options .= '<option value="' . $hn['id'] . '"';
				if ( $hn['id'] == 0 || $hn['id'] == $scheduleFileId ) $options .= ' selected style="background-color:green;color:white;"';
				if ( $hn['id'] == $scheduleFileId ) {
					$jsScheduleFileId = $scheduleFileId;
				}
				$options .= '>' . $hn['name'] . '</option>';
			}
			$replace_action['#scheduleFileId#'] = $jsScheduleFileId;
			$replace_action['#options#'] = $options;

			// indicateur schedule modifié
			$saveColor = 'white';
			$saveTitle = self::i18n("Sauvegarde la programmation courante");
			if ( $scheduleFileId != null ) {
				$scheduleSaved = self::getSchedule($scheduleFileId);
				if ( $scheduleSaved != null && $scheduleCurrent != null ) {
					$_scheduleSaved = json_encode($scheduleSaved['zones']);
					$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
					if ( $_scheduleSaved != $_scheduleCurrent ) {
						$saveColor = 'orange';
						if ( self::isDebug() ) {
							self::logDebug("_scheduleSaved = " . $_scheduleSaved);
							self::logDebug("_scheduleCurrent = " . $_scheduleCurrent);
						}
						$saveTitle .= ' (' . self::i18n("différente de la dernière programmation restaurée") . ')';
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
			$replace_action['#argZoneId#'] = self::ARG_ZONE_ID;
			$replace_action['#argFileRem#'] = self::ARG_FILE_REM;
			// codes mode
			$replace_action['#codeAuto#'] = self::CODE_MODE_AUTO;
			$replace_action['#codeEco#'] = self::CODE_MODE_ECO;
			$replace_action['#codeAway#'] = self::CODE_MODE_AWAY;
			$replace_action['#codeDayOff#'] = self::CODE_MODE_DAYOFF;
			$replace_action['#codeCustom#'] = self::CODE_MODE_CUSTOM;
			$replace_action['#codeOff#'] = self::CODE_MODE_OFF;
			$replace_action['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
			// configuration
			$replace_action['#displaySetModePopup#'] = self::getParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODES_CONSOLE) == self::CFG_SHOWING_MODES_POPUP ? "visible" : "none";
			$replace_action['#displaySetModeConsole#'] = self::getParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODES_CONSOLE) == self::CFG_SHOWING_MODES_CONSOLE ? "1" : "0";
			$replace_action['#evoDefaultShowingScheduleMode#'] = self::getParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);
			$replace_action['#background-color#'] = $replace['#background-color#'];
			// i18n
			$rbs = self::getParam(self::CFG_REFRESH_BEFORE_SAVE,0);
			$msg = array('scheduleTitle'=>"Programmes hebdo.",
				'title.setMode'=>"Réglage du mode de présence",
				'modeAuto'=>"Planning",
				'modeEco'=>"Economie",
				'modeAway'=>"Innocupé",
				'modeDayOff'=>"Congé",
				'modeCustom'=>"Personnalisé",
				'modeOff'=>"Arrêt",
				'setModeConfirm'=>"Confirmez-vous la demande du mode {0} ?",
				'setModeInfoList'=>"Bascule vers le mode '{0}'",
				'title.showCurrent'=>"Affiche la programmation courante",
				'title.showSelected'=>"Affiche la programmation indiquée dans la liste",
				'title.restore'=>"Restaure la programmation sélectionnée dans la liste",
				'title.delete'=>"Supprime la programmation sélectionnée dans la liste",
				'saveAs'=>"Sauvegarder la programmation actuelle" . ($rbs == 1 ? " (lecture préalable)" : ""),
				'saveName'=>"Nom",
				'saveRemark'=>"Commentaire",
				'saveReplace'=>"Confirmez-vous la mise à jour de la sauvegarde existante '{0}' ?",
				'saveInfoList'=>"Enregistre la programmation actuelle vers le fichier '{0}'",
				'restoreConfirm'=>"Etes-vous sûr de vouloir rétablir la programmmation avec '{0}' ?",
				'restoreInfoList'=>"Charge la programmation depuis le fichier '{0}'",
				'deleteConfirm'=>"Etes-vous sûr de vouloir supprimer la programmmation '{0}' ?",
				'deleteInfoList'=>"Supprime le fichier '{0}'"
				);
			foreach ( $msg as $code=>$txt ) $replace_action["#$code#"] = self::i18n($txt);

			$replace['#consoleContent#'] = template_replace($replace_action, getTemplate('core', $version, 'console_content', __CLASS__));
			$replace['#temperatureContent#'] = '';
		}
		else {
			$replace_temp = $this->preToHtml($pVersion);
			// *** TEMPERATURE
			$replace_temp['#etatImg#'] = 'empty.svg';	// dummy
			$replace_temp['#etatUntilImg#'] = 'empty.svg';	// dummy

			$cmdTemperature = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			$replace_temp['#temperatureId#'] = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
			$replace_temp['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "visible" : "none";
			$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
			if ( $temperatureNative == null ) {
				$replace_temp['#temperature#'] = '';
				$replace_temp['#temperatureImgDisplay#'] = 'inline;height:36px;width:36px;margin-top:8px;margin-bottom:8px;';
				$replace_temp['#temperatureDisplay2#'] = 'none';
			} else {
				$temperature = self::applyRounding($temperatureNative);
				$replace_temp['#temperature#'] = $temperature . '°';
				$replace_temp['#temperatureImgDisplay#'] = 'inline;height:15px;width:15px;margin-top:20px;';
				$replace_temp['#temperatureDisplay2#'] = 'visible';
			}

			// *** CONSIGNE
			$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			$replace_temp['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
			$replace_temp['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "block" : "none";
			$consigne = is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0;
			$_etat = self::getEtat();
			$aEtat = $_etat == null ? null : explode(';',$_etat);
			$isOff = $aEtat != null && $aEtat[0] == self::MODE_OFF;
			$isEco = $aEtat != null && $aEtat[0] == self::MODE_ECO;
			$isAway = $aEtat != null && $aEtat[0] == self::MODE_AWAY;
			$isDayOff = $aEtat != null && $aEtat[0] == self::MODE_DAYOFF;
			if ( $isOff ) $infoConsigne = 'OFF';
			else if ( $consigne == null ) $infoConsigne = '-';
			else $infoConsigne = $consigne . '°';
			$replace_temp['#consigne#'] = $infoConsigne;
			$replace_temp['#consigneBG#'] = self::getBackColorForTemp($consigne,$isOff);
			$replace_temp['#consigneBorder#'] = '0';

			$replace_temp['#temperatureImg#'] = $temperatureNative == null ? 'battlow.png' : ($temperatureNative < $consigne ? 'chauffage_on.gif' : 'check-mark-md.png');

			$cmdConsigneType = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			//$replace_temp['#consigneTypeId#'] = is_object($cmdConsigneType) ? $cmdConsigneType->getId() : '';

			$consigneTypeImg = null;
			if ( is_object($cmdConsigneType) && !$cmdConsigneType->getIsVisible() ) {
				// ...
			}
			else {
				$ct = is_object($cmdConsigneType) ? $cmdConsigneType->execCmd() : '';
				# $ct = FollowSchedule / PermanentOverride / TemporaryOverride ; 2018-01-28T23:00:00Z / <empty> ; Celsius/??
				$ct = explode(';', $ct);
				$currentConsigne = self::getCurrentConsigne($scheduleCurrent,$zoneId);
				$consigneTip = '';
				$consigneTypeUntil = '';
				$consigneTypeUntilFull = '';
				if ( $isEco ) {
					$consigneTypeUntilFull = self::i18n("Mode économie (remplace {0}°)", $currentConsigne);
					$consigneTypeImg = 'i_economy_white.png';
				} else if ( $isDayOff ) {
					$consigneTypeUntilFull = self::i18n("Mode congé");
					$consigneTypeImg = 'i_dayoff_white.png';
				}
				if ( $isOff ) {
					$consigneTypeUntilFull = self::i18n("Consigne forcée à {0}° au lieu de {1}°", [$consigne, $currentConsigne]);
					$consigneTypeImg = 'i_off_white.png';
				} else if ( $isAway ) {
					$consigneTypeUntilFull = self::i18n("Mode inoccupé (remplace {0}°)", $currentConsigne);
					$consigneTypeImg = 'i_away_white.png';
				} else if ( !$isEco && $ct[0] == 'FollowSchedule' ) {
					if ( $currentConsigne != null ) {
						if ( $consigne < $currentConsigne ) {
							$consigneTypeUntilFull = self::i18n("Optimisation active : consigne inférieure à suivre active (remplace {0}°)", $currentConsigne);
							$consigneTypeImg = 'down green.svg';
						} else if ( $consigne > $currentConsigne ) {
							$consigneTypeUntilFull = self::i18n("Optimisation active : consigne supérieure à suivre active (remplace {0}°)", $currentConsigne);
							$consigneTypeImg = 'up red.svg';
						}
					}
				} else if ( $ct[0] == 'TemporaryOverride' ) {
					$consigneTip = '';
					$consigneTypeImg = 'temp-override.svg';
					// example : $ct[1] = "2018-01-28T23:00:00Z"
					$time = self::gmtToLocalHM($ct[1]);
					$consigneTypeUntil = $time;
					$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}° jusqu'à {1}", [$currentConsigne, $time]);
				} else if ( $ct[0] == 'PermanentOverride' ) {
					$consigneTypeImg = 'override-active.png';
					$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}°", $currentConsigne);
				}
				$replace_temp['#consigneTypeUntil#'] = $consigneTypeUntil;
				$replace_temp['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
				$replace_temp['#consigneTip#'] = $consigneTip;
				$replace_temp['#zoneId#'] = $zoneId;
				$replace_temp['#fileId#'] = self::getParam(self::iCFG_SCHEDULE_ID,0);
			}
			$replace_temp['#consigneTypeImg#'] = $consigneTypeImg == null ? 'empty.svg' : $consigneTypeImg;
			$replace_temp['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'inline';
			// arguments names
			$replace_temp['#argFileId#'] = self::ARG_FILE_ID;
			$replace_temp['#argZoneId#'] = self::ARG_ZONE_ID;
			// codes
			$replace_temp['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
			// configuration
			$replace_temp['#evoDefaultShowingScheduleMode#'] = self::getParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

			$replace['#consoleContent#'] = '';
			$replace['#temperatureContent#'] = template_replace($replace_temp, getTemplate('core', $version, 'temperature_content', __CLASS__));
		}

		$html = template_replace($replace, getTemplate('core', $version, 'evohome', __CLASS__));
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		return $html;
	}

	/*
	* Called when the  evoHistoryRetention is saved from the configuration panel */
	public static function postConfig_evoHistoryRetention() {
		self::logDebug('IN>> - postConfig_evoHistoryRetention');
		$hr = self::getParam(self::CFG_HISTORY_RETENTION);
		foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if ( $cmd->getIsHistorized() ) {
					$cmd->setConfiguration('historyPurge',$hr);
					$cmd->save();
				}
			}
		}
		self::refreshConsole();
		self::logDebug('<<OUT - postConfig_evoHistoryRetention');
	}
	
	public static function getActionSaveId() {
		foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
			foreach ($eqLogic->getCmd('action') as $cmd) {
				if ( $cmd->getLogicalId() === 'save' ) {
					return $cmd->getId();
				}
			}
		}
		return null;
	}
	
	/*
	* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
	}
	*/

	/*	* ********************** Statics about Scheduling *************************** */

	static function getSchedule($fileId,$dateTime=0,$doRefresh=false) {
		if ( self::isDebug() ) {
			self::logDebug('IN>> - getSchedule(' . $fileId . ')');
		}
		if ( $fileId == self::CURRENT_SCHEDULE_ID ) {
			$infosZones = self::getInformationsAllZonesE2($doRefresh);
			if ( $infosZones == null ) {
				self::logDebug('<<OUT - getSchedule(self::CURRENT_SCHEDULE_ID) - error while getInformationsAllZonesE2 (see above)');
				return null;
			}
			$schedule = array('datetime' => $dateTime);
			$scheduleByZone = array();
			foreach ( $infosZones['zones'] as $zone ) {
				$scheduleByZone[] = array(
					'zoneId' => $zone['zoneId'],
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
			if ( self::isDebug() ) {
				self::logDebug('getSchedule from ' . $fileInfos['fullPath']);
			}
			$fileContentDecoded = json_decode($fileContent, true);
			if ( self::isDebug() ) {
				self::logDebug('<<OUT - getSchedule(' . $fileId . ')');
			}
			return $fileContentDecoded;
		}
		if ( self::isDebug() ) {
			self::logDebug('<<OUT - getSchedule(' . $fileId . ') non trouvé');
		}
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
			$parts = explode('_', $file, 2);
			$liste[] = array('id' => $parts[0],
							'name' => $parts[1],
							'fullPath' => $schedulePath . $file);
		}
		if ( count($liste) == 0 ) {
			$liste[] = array('id' => 0,
							'name' => self::i18n('vide'),
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

	static function updateScheduleFileId($fileId, $schedule=null) {
		self::logDebug("IN>> - updateScheduleFileId");
		self::setParam(self::iCFG_SCHEDULE_ID, $fileId);

		// read data without schedules infos
		$allInfos = self::getInformationsAllZonesE2($schedule != null, $schedule == null ? 1 : 0);
		if ( $schedule != null ) {
			self::logDebug("updateScheduleFileId : merge restored schedule data with fresh InformationsAllZonesE2");
			foreach ( $schedule['zones'] as &$srcZone ) {
				foreach ( $allInfos['zones'] as &$dstZone ) {
					if ( $srcZone['zoneId'] == $dstZone['zoneId'] ) {
						if ( self::isDebug() ) {
							self::logDebug("- merging zone " . $dstZone['zoneId']);
						}
						$dstZone['schedule'] = $srcZone['schedule'];
						break;
					}
				}
			}
			$tsRemain = self::getCacheRemaining(self::CACHE_IAZ);
			self::cacheData(self::CACHE_IAZ, json_encode($allInfos), $tsRemain);
		}
		self::refreshAll($allInfos);

		self::logDebug("<<OUT - updateScheduleFileId");
	}

	/*	* ********************** Actions *************************** */

	function doCaseAction($paramAction, $parameters) {
		if ( self::isDebug() ) {
			self::logDebug('doCaseAction(' . $paramAction . ')');
		}
		switch ($paramAction) {
			case self::CMD_SET_MODE:
				self::setMode($parameters[self::ARG_CODE_MODE]);
				break;

			case self::CMD_SAVE:
				self::saveSchedule($parameters);
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
		if ( $codeMode == null || $codeMode == '' ) {
			self::logDebug('IN>><<OUT - setMode called without code');
			return;
		}
		self::waitingIAZReentrance();
		self::lockCron();
		if ( self::isDebug() ) {
			self::logDebug('IN>> - setMode with code=' . $codeMode);
		}

		// Call python function
		self::logDebug('setMode : call python');
		$td = time();
		$retValue = self::runPython('SetModeE2.py', self::getLocationId() . ' ' . $codeMode);
		if ( self::isDebug() ) {
			self::logDebug('setMode : python return in ' . (time() - $td) . 'sec');
		}
		$aRet = self::jsonDecode($retValue, 'setMode');
		if ( $aRet == null ) {
			// this call used to remove the loading mask on the screen
			self::refreshConsole();
		} else if ( !$aRet['success'] ) {
			self::logError("Error while setMode : [" . json_encode($aRet) . "]");
			if ( self::isDebug() ) {
				self::logDebug(' -- datas = : ' . $retValue);
			}
			// this call used to remove the loading mask on the screen
			self::refreshConsole();
		} else {
			// Wait 30 sec. because all modified infos (setting point) are not immediately available
			self::logDebug('setMode : wait 30 sec before call getInformationsAllZonesE2 (evotouch takes a long time to be refreshed)');
			sleep(30);
			self::refreshAll(self::getInformationsAllZonesE2(true));
		}

		self::logDebug('<<OUT - setMode');
		self::unlockCron();
	}

	function saveSchedule($parameters) {
		$fileName = $parameters[self::ARG_FILE_NAME];
		$fileId = $parameters[self::ARG_FILE_ID];
		$commentary = $parameters[self::ARG_FILE_REM];
		$newSchedule = $parameters[self::ARG_FILE_NEW_SCHEDULE];
		if ( self::isDebug() ) {
			self::logDebug('IN>> - saveSchedule(' . $fileName . ', ' . $fileId . ', ' . ($newSchedule == null ? '<currentSchedule>' : '<newSchedule>') . ')');
		}
		self::waitingIAZReentrance();
		self::lockCron();
		$dateTime = time();
		if ( (int)$fileId == self::CURRENT_SCHEDULE_ID ) {
			$fileId = $dateTime;
			$filePath = self::getSchedulePath() . $fileId . '_' . $fileName;
		} else {
			$fileInfos = self::getFileInfosById((int)$fileId);
			$filePath = $fileInfos['fullPath'];
		}
		if ( self::isDebug() ) {
			self::logDebug('launch save action with fileName="' . $filePath . '"');
		}
		// force refresh inside getInformationsAllZonesE2
		if ( $newSchedule == null ) {
			$rbs = self::getParam(self::CFG_REFRESH_BEFORE_SAVE,0);
			$schedule = self::getSchedule(self::CURRENT_SCHEDULE_ID,$dateTime,$rbs==1);
		} else {
			$schedule = array('datetime' => $dateTime);
			$schedule['zones'] = json_decode($newSchedule,true);
		}
		if ( $schedule == null ) {
			self::logDebug('<<OUT - saveSchedule - error while getSchedule (see above)');
			// this call used to remove the loading mask on the screen
			self::refreshConsole();
		} else {
			$fp = fopen($filePath, 'w');
			$schedule['comment'] = $commentary;
			fwrite($fp, json_encode($schedule));
			fclose($fp);

			if ( $newSchedule == null ) {
				self::updateScheduleFileId($fileId);
			} else {
				self::refreshAll(null);
			}
			self::logDebug('<<OUT - saveSchedule');
		}
		self::unlockCron();
	}

	function restoreSchedule($fileId) {
		self::waitingIAZReentrance();
		self::lockCron();
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos == null ) {
			self::logError('restoreSchedule on unknown ID=' . $fileId);
			return;
		}
		if ( self::isDebug() ) {
			self::logDebug('restoreSchedule on saving ID=' . $fileId . ', name=' . $fileInfos['name']);
		}
		// Call python function
		self::logDebug('restoreSchedule : call python');
		$td = time();
		$retValue = self::runPython('RestaureZonesE2.py', self::getLocationId() . ' "' . $fileInfos['fullPath'] . '"');
		$delay = time() - $td;
		if ( self::isDebug() ) {
			self::logDebug('restoreSchedule : python return in ' . $delay . 'sec : ' . $retValue);
		}
		$aRet = self::jsonDecode($retValue, 'restoreSchedule1');
		if ( $aRet == null ) {
			// this call used to remove the loading mask on the screen
			self::refreshConsole();
		}
		else if ( !$aRet['success'] ) {
			self::logError("Error while restoreSchedule : [" . json_encode($aRet) . "]");
			if ( self::isDebug() ) {
				self::logDebug(' -- datas = : ' . $retValue);
			}
			// this call used to remove the loading mask on the screen
			self::refreshConsole();
		} else {
			$fp = fopen($fileInfos['fullPath'], 'r');
			$fileContent = fread($fp,filesize($fileInfos['fullPath']));
			$schedule = self::jsonDecode($fileContent, 'restoreSchedule2');
			fclose($fp);
			// Wait 10 sec. because all modified infos (setting point) are not immediately available
			self::logDebug('setMode : wait 10 sec (evotouch takes time to be refreshed after schedule changes)');
			sleep(10);
			self::updateScheduleFileId($fileId, $schedule);
		}
		self::unlockCron();
	}

	function deleteSchedule($fileId) {
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos == null ) {
			self::logError('deleteSchedule on unknown ID=' . $fileId);
			return;
		}
		self::logDebug('deleteSchedule on ID=' . $fileId);
		unlink($fileInfos['fullPath']);
		self::refreshConsole();
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
			throw new Exception(self::i18n("Commande non implémentée"));
		}
		return true;
	}

}
