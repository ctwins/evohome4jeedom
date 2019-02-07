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
	const CFG_LOADING_SYNC  = 'evoLoadingSync';
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
	const CMD_STATISTICS_ID = 'statistiquesInfos';
	const CMD_TEMPERATURE_ID = 'temperature';
	const CMD_CONSIGNE_ID = 'consigne';
	const CMD_SCH_CONSIGNE_ID = 'progConsigne';
	const CMD_CONSIGNE_TYPE_ID = 'consigneType';
	const CMD_SET_CONSIGNE_ID = 'setConsigne';
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
	const ARG_CODE_MODE = 'select';	// 0.2.1 : codeMode' replaced by 'select' for compatibility with parameter of scenario
	const ARG_FILE_NAME = 'fileName';
	const ARG_FILE_ID = 'fileId';
	const ARG_FILE_REM = 'remark';
	const ARG_FILE_NEW_SCHEDULE = 'scheduleData';
	const ARG_ZONE_ID = 'zoneId';
	const ARG_CONSIGNES_DATA = 'select';
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
	const CACHE_INFOS_API = 'evohomeInfosApi';
	const CACHE_IAZ_DURATION = 86400;
	const CACHE_IAZ_RUNNING = 'getInformationsAllZonesE2Running';
	const CACHE_LIST_LOCATIONS = 'evohomeListLocations';
	const CACHE_STATES_DURATION = 30;
	const CACHE_STATES = 'evohomeStates';
	const CACHE_CURRENT_SCHEDULE = 'evohomeCurrentSchedule';
	const PY_SUCCESS = 'success';
	// InfosZones
	const IZ_TIMESTAMP = 'timestamp';
	# -- infosAPI :
	const IZ_API_V1 = 'apiV1';
	const IZ_SESSION_ID_V1 = 'session_id_v1';
	const IZ_USER_ID_V1 = 'user_id_v1';
	const IZ_SESSION_STATE_V1 = 'session_state_v1';
	const IZ_SESSION_ID_V2 = 'access_token';
	const IZ_SESSION_EXPIRES_V2 = 'access_token_expires';
	const IZ_SESSION_STATE_V2 = 'token_state';
	# -- unused
	const IZ_CACHED = 'cached';

	public static function isDebug() {
		return log::getLogLevel(__CLASS__) == 100;
	}
	public static function logDebug($msg) {
		log::add(__CLASS__, 'debug', $msg);
	}
	public static function logError($msg) {
		log::add(__CLASS__, 'error', $msg);
	}

	static function tsToLocalDateTime($ts) {
		$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
		$date->setTimestamp($ts);
		return $date->format('Y-m-d H:i:s');
	}
	static function tsToLocalTime($ts) {
		$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
		$date->setTimestamp($ts);
		return $date->format('H:i:s');
	}
	static function tsToAbsoluteHMS($ts) {
		$date = new DateTime();
		$date->setTimestamp($ts);
		$date->setTimezone(new DateTimeZone('UTC'));
		return $date->format('H:i:s');
	}
	static function tsToLocalHMS($ts) {
		$date = new DateTime();
		$date->setTimestamp($ts);
		//$date->setTimezone(new DateTimeZone('UTC'));
		return $date->format('H:i:s');
	}
	static function _gmtToLocalDateTime($gmtDateTime) {
		$dt = new DateTime($gmtDateTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone(config::byKey('timezone')));
		return $dt;
	}
	// example : "2018-01-28T23:00:00Z"
	static function gmtToLocalHM($gmtDateTime) {
		$dt = self::_gmtToLocalDateTime($gmtDateTime)->getTimestamp();
		return /*self::isAmPmMode()*/false ? date("g:i a", $dt) : date("G:i", $dt);
	}
	static function gmtToLocalDate($gmtDateTime) {
		return strftime('%x', self::_gmtToLocalDateTime($gmtDateTime)->getTimestamp());
	}
	static function isAdmin() {
		return isConnect('admin') == 1 ? 'true' : 'false';
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
		if ( $text == null || $text == '' ) {
			if ( self::isDebug() ) self::logDebug('jsonDecode null for ' . $fnName);
			return null;
		}
		// 2018-02-24 - fix for compatibility with PHP 7.xx (useless for PHP 5.xx)
		$text = str_replace('True', 'true', str_replace('False', 'false', $text));
		$aValue = json_decode($text, true);
		if ( json_last_error() != JSON_ERROR_NONE ) {
			$aValue = null;
			self::logError('Error while ' . $fnName . ' : json error=' . json_last_error() . ', input was = <' . $text . '>');
		} else if ( self::isDebug() ) {
			self::logDebug("jsonDecode OK for $fnName");
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
		$ret = array();
		$ret['log'] =  __CLASS__ . '_update';
		$ret['state'] = 'ok';
		$ret['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';

		$x = 'which avconv | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) self::logDebug("dependancy_info 1/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		$x = system::getCmdSudo() . system::get('cmd_check') . ' gd | grep php | wc -l';
		$r = exec($x);
		if ( self::isDebug() ) self::logDebug("dependancy_info 2/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		return $ret;
	}

	static function setParam($paramName, $value) {
		config::save($paramName, $value, __CLASS__);
	}

	static function getParam($paramName, $defValue=null) {
		$cfgValue = config::byKey($paramName, __CLASS__);
		return $cfgValue == null ? $defValue : $cfgValue;
	}

	static function lockCron() {
		self::setParam('functionality::cron::enable', 0);
	}

	static function unlockCron() {
		self::setParam('functionality::cron::enable', 1);
	}

	static function setCacheData($cacheName, $content, $duration=null) {
		cache::set($cacheName, $content, $duration == null ? 9*60 : $duration);
	}
	static function getCacheData($cacheName) {
		return cache::byKey($cacheName)->getValue();
	}
	static function getCacheRemaining($cacheName) {
		$cache = cache::byKey($cacheName);
		$tsDtCache = DateTime::createFromFormat('Y-m-d H:i:s', $cache->getDatetime())->getTimestamp();
		$cacheDuration = $cache->getLifetime();
		return ($tsDtCache + $cacheDuration) - (new Datetime())->getTimestamp();
	}
	static function doCacheRemove($cacheName) {
		$cache = cache::byKey($cacheName);
		if ( is_object($cache) ) $cache->remove();
	}

	static function getLoadingInterval() {
		return intVal(self::getParam(self::CFG_LOADING_INTERVAL,10));
	}

	public static function cron() {
		self::logDebug('IN>> - cron');
		if ( self::isIAZrunning() ) {
			self::logDebug('<<OUT - cron - reading still running. exit now');
			return;
		}
		$mark = self::getCacheData(self::CACHE_CRON_TIMER);
		$tsRemain = self::getCacheRemaining(self::CACHE_CRON_TIMER);
		if ( false || $mark == '' || $tsRemain <= 5 ) {
			if ( self::getParam(self::CFG_LOADING_SYNC,0) == 1 ) {
				// adjust fine time :
				$interval = self::getLoadingInterval();
				$min = intVal(date("i"));
				if ( $min % $interval != 0 ) {
					// 10 = 0/10/20/30/40/50
					// 15 = 0/15/30/45
					// 20 = 0/20/40
					// 30 = 0/30
					// So, we adjust by checking : currentMin % interval == 0
					self::logDebug("<<OUT - cron - synchronize interval ($interval) on time (current $min)");
					return;
				}
				self::logDebug("synchronize time is requested and was right ;)");
			}
			$di = self::dependancy_info();
			if ( $di['state'] != 'ok' ) {
				self::logDebug('<<OUT - cron - plugin not ready (dependency_info=NOK)');
			} else {
				if ( self::isDebug() ) {
					// error level to enforce reporting
					log::add('cron_execution', 'warning', 'Launching getInformationsAllZonesE2 with refresh');
				}
				$td = time();
				evohome::getInformationsAllZonesE2(true);
				$delay = time() - $td;
				$cacheDuration = evohome::getLoadingInterval()*60 - $delay - 2;
				evohome::setCacheData(evohome::CACHE_CRON_TIMER, "dummy", $cacheDuration);
			}
		} else if ( self::isDebug() ) {
			self::logDebug("cron : wait for $tsRemain sec.");
		}
		self::logDebug('<<OUT - cron');
	}

	static function setPythonRunning($name) {
		self::setCacheData('PYTHON_RUNNING', $name, 86400);
	}
	static function isPythonRunning() {
		return self::getCacheData('PYTHON_RUNNING');
	}
	static function razPythonRunning() {
		self::doCacheRemove('PYTHON_RUNNING');
	}
	static function runPython($prgName, $parameters=null) {
		while ( ($prev=self::isPythonRunning()) != '' ) {
			if ( self::isDebug() ) self::logDebug("another runPython ($prev) is running, wait 5sec before launching a new one ($prgName)");
			sleep(5);
		}
		self::setPythonRunning($prgName);
		$credential = self::getParam(self::CFG_USER_NAME,'') . ' ' . self::getParam(self::CFG_PASSWORD,'');
		if ( $credential === ' ' ) {
			self::logDebug('runPython too early : account is not set yet');
			razPythonRunning();
			return null;
		}
		$cmd = 'python ' . dirname(__FILE__) . '/../../resources/' . $prgName . ' ' . $credential;

		// -- inject access_token/session from cachedInfosAPI
		$cachedInfosAPI = self::getCacheData(self::CACHE_INFOS_API);
		if ( $cachedInfosAPI == '' ) {
			$cachedInfosAPI = array(
				self::IZ_SESSION_ID_V1=>'0',
				self::IZ_USER_ID_V1=>0,
				self::IZ_SESSION_ID_V2=>'0',
				self::IZ_SESSION_EXPIRES_V2=>0.0);
		}
		$sessionIdV1 = $cachedInfosAPI[self::IZ_SESSION_ID_V1];
		$userIdV1 = $cachedInfosAPI[self::IZ_USER_ID_V1];
		$access_token = $cachedInfosAPI[self::IZ_SESSION_ID_V2];
		$token_expires = $cachedInfosAPI[self::IZ_SESSION_EXPIRES_V2];
		$cmd .= ' "' . $sessionIdV1 . '" "' . $userIdV1 . '" "' . $access_token . '" "' . $token_expires . '"';
		$cmd .= ' ' . (self::isDebug() ? '1' : '0');
		// -- inject parameters if any (auto insert locationId before)
		if ( $parameters !== null ) {
			$cmd .= ' ' . self::getLocationId() . ' ' . $parameters;
		}
		$json = trim(shell_exec($cmd));

		// cache API infos (access_token/session)
		$jsonDec = self::jsonDecode($json, 'runPython('. $prgName . ')');
		if ( !is_null($jsonDec) ) {
			$updated = false;
			if ( array_key_exists(self::IZ_SESSION_ID_V1,$jsonDec) && array_key_exists(self::IZ_USER_ID_V1,$jsonDec) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V1] = $jsonDec[self::IZ_SESSION_ID_V1];
				$cachedInfosAPI[self::IZ_USER_ID_V1] = $jsonDec[self::IZ_USER_ID_V1];
				$updated = true;
				if ( self::isDebug() ) self::logDebug('runPython : session_v1 state=' . array('undefined', 'same', 'new', 'toBeRemoved')[$jsonDec[self::IZ_SESSION_STATE_V1]]);
			}
			if ( array_key_exists(self::IZ_SESSION_ID_V2,$jsonDec) && array_key_exists(self::IZ_SESSION_EXPIRES_V2,$jsonDec) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V2] = $jsonDec[self::IZ_SESSION_ID_V2];
				$cachedInfosAPI[self::IZ_SESSION_EXPIRES_V2] = $jsonDec[self::IZ_SESSION_EXPIRES_V2];
				$updated = true;
				if ( self::isDebug() ) self::logDebug('runPython : access_token state=' . array('undefined', 'same', 'new')[$jsonDec[self::IZ_SESSION_STATE_V2]]);
			}
			if ( $updated ) {
				self::setCacheData(self::CACHE_INFOS_API, $cachedInfosAPI, self::CACHE_IAZ_DURATION);
				// IZ_SESSION_ID_V1 is the first key of the API session infos bloc (even when IZ_SESSION_ID_V2 is present)
				$pos = array_search(self::IZ_SESSION_ID_V1, array_keys($jsonDec));
				if ( is_numeric($pos) ) {
					array_splice($jsonDec, $pos);
				} else {
					// if IZ_SESSION_ID_V1 not here, IZ_SESSION_ID_V2 could be
					$pos = array_search(self::IZ_SESSION_ID_V2, array_keys($jsonDec));
					if ( is_numeric($pos) ) array_splice($jsonDec, $pos);
				}
			} else {
				self::logDebug('runPython : WARNING : no token nor sessionId received');
			}
		}

		self::razPythonRunning();
		return $jsonDec;
	}

	/*
	 * Read all Locations attached to the account
	 */
	public static function listLocations() {
		self::logDebug('IN>> - listLocations');
		$locations = self::getCacheData(self::CACHE_LIST_LOCATIONS);
		if ( $locations == '') {
			$td = time();
			$locations = self::runPython('LocationsInfosE2.py');
			if ( !is_array($locations)  ) {
				self::logDebug('Erreur while LocationsInfosE2 : response was empty or malformed');
			} else if ( !$locations[self::PY_SUCCESS] ) {
				if ( self::isDebug() ) self::logDebug('Erreur while LocationsInfosE2 : <' . json_encode($locations) . '>');
			} else {
				$locations = $locations['locations'];
				self::setCacheData(self::CACHE_LIST_LOCATIONS, $locations);
			}
			if ( self::isDebug() ) self::logDebug('<<OUT - listLocations from python in ' . (time() - $td) . ' sec.');
		} else {
			self::logDebug('<<OUT - listLocations from cache');
		}
		return $locations;
	}

	static function getLocationId() {
		$locId = self::getParam(self::CFG_LOCATION_ID);
		// 2018-02-23 - fix when location not set yet (avoid a python error on arguments)
		return is_numeric($locId) ? $locId : self::CFG_LOCATION_DEFAULT_ID;
	}

	static function activateIAZReentrance($delay) {
		self::setCacheData(self::CACHE_IAZ_RUNNING, "true", $delay);
	}
	static function isIAZrunning() {
		return self::getCacheData(self::CACHE_IAZ_RUNNING) != '';
	}
	static function deactivateIAZReentrance() {
		self::doCacheRemove(self::CACHE_IAZ_RUNNING);
		self::doCacheRemove(self::CACHE_STATES);
	}
	static function waitingIAZReentrance($caller) {
		$isRunning = false;
 		while ( self::isIAZrunning() ) {
			if ( self::isDebug() ) self::logDebug('waitingIAZReentrance(' . $caller . ') 5sec');
			sleep(5);
			$isRunning = true;
		}
		return $isRunning;
	}

	public static function getInformationsAllZonesE2($forceRefresh=false, $readSchedule=true, $msgInfo='') {
		$execUnitId = rand(0,10000);
		if ( self::isDebug() ) self::logDebug('IN>> - getInformationsAllZonesE2[' . $execUnitId . ']');
		$infosZones = self::getCacheData(self::CACHE_IAZ);
		$useCachedData = true;
		$infosZonesBefore = null;
		if ( !is_array($infosZones) || $forceRefresh ) {
			if ( self::waitingIAZReentrance('IAZ-' . $execUnitId) ) {
				$infosZones = self::getCacheData(self::CACHE_IAZ);
				// a reading has just been done
			} else {
				// Wait if another python is running
				while ( ($prev=self::isPythonRunning()) != '' ) {
					if ( self::isDebug() ) self::logDebug("another runPython ($prev) is running, wait 5sec before launching a new one (InfosZonesE2.py)");
					sleep(5);
				}
				self::activateIAZReentrance(15*60);	// was 120 - now 15mn against cloud freezing
				if ( is_array($infosZones) && $infosZones[self::PY_SUCCESS] ) {
					$infosZonesBefore = $infosZones;
					self::refreshAll($infosZonesBefore);
				}
				$td = time();
				$infosZones = self::runPython('InfosZonesE2.py', $readSchedule ? "1" : "0");
				if ( self::isDebug() ) self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from python in ' . (time() - $td) . ' sec.');
				self::deactivateIAZReentrance();
				if ( !is_array($infosZones) ) {
					self::logError('Error while getInformationsAllZonesE2 : <' . json_encode($infosZones) . '>');
					if ( $infosZonesBefore != null ) {
						self::refreshAll($infosZonesBefore,false,$msgInfo);
					}
				} else if ( !$infosZones[self::PY_SUCCESS] ) {
					if ( self::isDebug() ) self::logError('Error while getInformationsAllZonesE2 = <' . json_encode($infosZones) . '>');
					if ( $infosZonesBefore != null ) {
						self::refreshAll($infosZonesBefore,false,$msgInfo);
					}
				} else {
					if ( self::isDebug() ) self::logDebug('getInformationsAllZonesE2 : ' . json_encode($infosZones));
					self::setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION);
					// refresh if needed
					if ( $readSchedule ) {
						self::refreshAll($infosZones,true,$msgInfo);
					}
					$infosZones[self::IZ_CACHED] = false;
				}
				$useCachedData = false;
			}
		}
		if ( $useCachedData ) {
			if ( self::isDebug() ) self::logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from cache (rest to live=' . self::getCacheRemaining(self::CACHE_IAZ) . ')');
			if ( $infosZonesBefore != null ) $infosZones = $infosZonesBefore;
			$infosZones[self::IZ_CACHED] = true;
		}
		if ( self::isDebug() ) self::logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . ']');
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
		if ( $consigne == null ) return 'lightgray';
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

	static function getComponent($zoneId) {
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			if ( $equipment->getConfiguration(self::CONF_ZONE_ID) == $zoneId ) {
				return $equipment;
			}
		}
		return null;
	}
	static function getConsole() {
		return self::getComponent(self::ID_CONSOLE);
	}
	static function getCurrentMode() {
 		$console = self::getConsole();
		if ( $console != null ) {
			$cmdEtat = $console->getCmd(null,self::CMD_STATE);
			if ( $cmdEtat != null && is_object($cmdEtat) ) {
				$etat = $cmdEtat->execCmd();
				if ( $etat != null ) {
					// self::MODE_AUTO and so on...
					return explode(';', $etat)[0];
				}
			}
		}
		return null;
	}

	static function refreshConsole($msgInfo='') {
		if ( self::isDebug() ) self::logDebug("IN>> - refreshConsole");
		$console = self::getConsole();
		if ( $console != null ) {
			$console->setToHtmlProperties(self::getStates(self::getInformationsAllZonesE2()),self::getSchedule(self::CURRENT_SCHEDULE_ID),$msgInfo);
			$console->refreshComponent();
		}
		self::logDebug('<<OUT - refreshConsole');
	}

	static function extractZone($zonesDatas,$zoneId) {
		if ( is_array($zonesDatas) && array_key_exists('zones',$zonesDatas) ) {
			foreach ( $zonesDatas['zones'] as $tmp ) {
				if ( $tmp['zoneId'] == $zoneId ) {
					return $tmp;
				}
			}
		}
		return null;
	}

	public static function getScheduleSubTitle($id,$fileId,$scheduleCurrent,$scheduleToShow,$targetOrientation,$zoneId,$typeSchedule,$isEdit=false) {
		$infoDiff = '';
		if ( $fileId == 0) {
			$subTitle = self::i18n("Programmation courante");
		} else {
			$dt = new DateTime();
			$dt->setTimestamp($scheduleToShow['datetime']);
			$subTitle = self::i18n("Programmation de '{0}' créée le {1} à {2}", [self::getFileInfosById($fileId)['name'], $dt->format('Y-m-d'), $dt->format('H:i:s')]);
			if ( !$isEdit ) {
				$isDiff = false;
				if ( $zoneId == 0 ) {
					$isDiff = json_encode($scheduleToShow['zones']) != json_encode($scheduleCurrent['zones']);
				} else {
					$isDiff = json_encode(self::extractZone($scheduleToShow,$zoneId)) != json_encode(self::extractZone($scheduleCurrent,$zoneId));
				}
				if ( $isDiff ) {
					$infoDiff = self::i18n("différente de la programmation courante") . " *";
				} else {
					$infoDiff = self::i18n("identique à la programmation courante");
				}
			}
		}
		if ( !$isEdit ) {
			if ( $zoneId == 0 ) {
				$ssf = "showScheduleCO('$typeSchedule',$fileId,'$targetOrientation');";
			} else {
				$ssf = "showScheduleTH($id,$zoneId,'$typeSchedule','$targetOrientation');";
			}
			$lbl = self::i18n($targetOrientation == 'V' ? "Vertical" : "Horizontal");
			$subTitle = "<a class='btn btn-success btn-sm tooltips' onclick=\\\"$ssf\\\">$lbl</a>&nbsp;$subTitle";
		} else {
			$infoDiff = self::i18n("Mode édition");
		}
		$subTitle .= $infoDiff == '' ? '' : "<br/><i>$infoDiff</i>";
		return $subTitle;
	}

	public static function getEquNamesAndId() {
		$table = array();
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			$table[$equipment->getConfiguration(self::CONF_ZONE_ID)] = $equipment->getName();
		}
		if ( self::isDebug() ) self::logDebug('getEquNamesAndId : ' . json_encode($table));
		return $table;
	}

	static function getStates($infosZones=null) {
		$states = array();
		$states['unread'] = (self::CACHE_IAZ_DURATION - self::getCacheRemaining(self::CACHE_IAZ)) > self::getLoadingInterval()*60;
		$states['isRunning'] = self::isIAZrunning();
		$states['lastRead'] = !is_array($infosZones) || !array_key_exists(self::IZ_TIMESTAMP,$infosZones) ? 0 : self::tsToLocalDateTime($infosZones[self::IZ_TIMESTAMP]);
		// apiV1 available == accurate values available
		$states['isAccurate'] = !is_array($infosZones) || !array_key_exists(self::IZ_API_V1,$infosZones) ? false : $infosZones[self::IZ_API_V1];
		if ( self::isDebug() ) self::logDebug("getStates : " . json_encode($states));
		return $states;
	}

	static function refreshAll($infosZones,$inject=false,$msgInfo='') {
		self::logDebug("IN>> - refreshAll");
		$states = self::getStates($infosZones);
		$scheduleCurrent = self::getSchedule(self::CURRENT_SCHEDULE_ID);
		foreach (eqLogic::byType(__CLASS__) as $equipment) {
			$equipment->setToHtmlProperties($states,$scheduleCurrent,$msgInfo);
			$msgInfo = '';	// set only on the first equipment
			$equipment->refreshComponent($infosZones,$inject);
		}
		self::logDebug("<<OUT - refreshAll");
	}

	/*********************** Méthodes d'instance **************************/

	function createOrUpdateCmd($order, $logicalId, $name, $type, $subType, $isVisible, $isHistorized) {
		$cmd = $this->getCmd(null, $logicalId);
		$created = false;
		if (is_object($cmd) && ($logicalId == self::CMD_SET_MODE || $logicalId == self::CMD_RESTORE) && $cmd->getSubType() != 'select') {
			self::logDebug('0.2.1 : createOrUpdateCmd replace MODE/RESTORE cmd');
			$cmd->remove();
			$cmd = null;
		}
		if (!is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName(self::i18n($name));
			$cmd->setLogicalId($logicalId);
			$cmd->setIsVisible($isVisible);
			$cmd->setIsHistorized($isHistorized);
			$created = true;
		}
		$cmd->setOrder($order);
		$cmd->setType($type);
		$cmd->setSubType($subType);
		if ( $logicalId == self::CMD_SET_MODE ) {
			$cmd->setConfiguration('listValue',
				self::CODE_MODE_AUTO   . '|' . self::i18n('Planning') . ';' .
				self::CODE_MODE_ECO    . '|' . self::i18n('Economie') . ';' .
				self::CODE_MODE_AWAY   . '|' . self::i18n('Innocupé') . ';' .
				self::CODE_MODE_DAYOFF . '|' . self::i18n('Congé') . ';' .
				self::CODE_MODE_CUSTOM . '|' . self::i18n('Personnalisé') . ';' .
				self::CODE_MODE_OFF    . '|' . self::i18n('Arrêt'));
		} else if ( $logicalId == self::CMD_SET_CONSIGNE_ID ) {
			$list = '';
			$zoneId = $this->getConfiguration(self::CONF_ZONE_ID);
			// TODO: get dynamic values for min/max/step (beware of reentrance)
			for( $t=5 ; $t<=25 ; $t+=0.5 ) {
				// auto means the callback function must check availability of service (presence mode / api available)
				$list .= 'auto#' . $zoneId . '#' . $t . '#null|' . $t . ($t < 25 ? ';' : '');
			}
			$cmd->setConfiguration('listValue', $list);
		}
		if ( $logicalId == self::CMD_TEMPERATURE_ID ) {
			$cmd->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
		} else if ( $logicalId == self::CMD_CONSIGNE_ID || $logicalId == self::CMD_SCH_CONSIGNE_ID ) {
			$cmd->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
		}
		if ( $isHistorized == 1 ) {
			$cmd->setConfiguration('historizeMode', 'none');
			$cmd->setConfiguration('historyPurge', '');
			$cmd->setConfiguration('repeatEventManagement', 'always');
		}
		// flags for the evohome.js
		$cmd->setConfiguration('canBeVisible', $isVisible);
		$cmd->setConfiguration('canBeHistorize', $isHistorized);
		$cmd->save();

		if ( $created && $logicalId == self::CMD_RESTORE ) {
			self::updateRestoreList();
		}

		return $created;
	}

	function deleteCmd($sCmdList) {
		foreach ( $sCmdList as $sCmd ) {
			$cmd = $this->getCmd(null, $sCmd);
			if (is_object($cmd)) $cmd->remove();
		}
	}

	public function postSave() {
		self::logDebug('IN>> - postSave'); 
		if ($this->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE) {
			self::logDebug('postSave - create Console widget');
			self::deleteCmd([self::CMD_TEMPERATURE_ID, self::CMD_CONSIGNE_ID, self::CMD_SCH_CONSIGNE_ID, self::CMD_CONSIGNE_TYPE_ID, self::CMD_SET_CONSIGNE_ID]);
			self::createOrUpdateCmd(0, self::CMD_STATE, 'Etat', 'info', 'string', 1, 0);
			self::createOrUpdateCmd(1, self::CMD_SET_MODE, 'Réglage mode', 'action', 'select', 1, 0);
			self::createOrUpdateCmd(2, self::CMD_SAVE, 'Sauvegarder', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(3, self::CMD_RESTORE, 'Restaure', 'action', 'select', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_DELETE, 'Supprimer', 'action', 'other', 1, 0);
			self::createOrUpdateCmd(5, self::CMD_STATISTICS_ID, "Statistiques", 'info', 'numeric', 1, 0);
		}
		else if ($this->getConfiguration(self::CONF_ZONE_ID) > 0) {
			self::logDebug('postSave - create TH widget');
			self::deleteCmd([self::CMD_STATE, self::CMD_SET_MODE, self::CMD_SAVE, self::CMD_RESTORE, self::CMD_DELETE, self::CMD_STATISTICS_ID]);
			self::createOrUpdateCmd(0, self::CMD_TEMPERATURE_ID, 'Température', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(1, self::CMD_CONSIGNE_ID, 'Consigne', 'info', 'numeric', 1, 1);
			self::createOrUpdateCmd(2, self::CMD_SCH_CONSIGNE_ID, 'Consigne programmée', 'info', 'numeric', 0, 1);
			self::createOrUpdateCmd(3, self::CMD_CONSIGNE_TYPE_ID, 'Type Consigne', 'info', 'string', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_SET_CONSIGNE_ID, 'Set Consigne', 'action', 'select', 1, 0);
		}

		$infosZones = self::getInformationsAllZonesE2();
		$this->injectInformationsFromZone($infosZones);

		if ( self::isDebug() ) self::logDebug('<<OUT - postSave(' . $this->getConfiguration(self::CONF_ZONE_ID) . ')'); 

		return true;
	}

	public function preUpdate() {
		if ($this->getConfiguration(self::CONF_ZONE_ID) == self::ID_CONSOLE) {
			$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
			if ( is_object($cmd) ) {
				$v = $cmd->getIsVisible() ? '1' : '0';
				if ( self::isDebug() ) self::logDebug("preUpdate : visible STAT = $v");
				self::setCacheData("STAT_PREV_VISIBLE", $v);
			}
		}

		return true;
	}

	public function preRemove() {
	}

	public function postRemove() {
	}

	function updateRestoreList() {
		$hns = self::getHebdoNames();
		$listValue = '';
		$idx = 1;
		foreach ( $hns as $hn ) {
			$listValue .= $hn['id'] . '|' . $hn['name'];
			if ( $idx++ < count($hns) ) $listValue .= ';';
		}
		$cmd = $this->getCmd(null, self::CMD_RESTORE);
		$cmd->setConfiguration('listValue', $listValue);
		$ret = $cmd->save();
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

	// $razMinMax = true for a manual command (RUF)
	function injectInformationsFromZone($infosZones, $razMinMax=false) {
		if ( !is_array($infosZones) ) {
			return;
		}
		$zoneId = $this->getConfiguration(self::CONF_ZONE_ID);
		if ( self::isDebug() ) self::logDebug("IN>> - injectInformationsFromZone on zone $zoneId");
		if ( $zoneId == self::ID_NO_ZONE ) {
			self::logError("<<OUT - injectInformationsFromZone - zone undefined ; nothing to do");
			return;
		}
		if ( $zoneId == self::ID_CONSOLE) {
			$tmp = $this->getCmd(null,self::CMD_STATE);
			if(is_object($tmp)){
				$etat = $infosZones['currentMode']
					. ";" . ($infosZones['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
					. ";" . $infosZones['untilMode'];
				$tmp->event($etat);
			}

		} else if ( $zoneId > 0) {
			$infosZone = self::extractZone($infosZones,$zoneId);
			if ( $infosZone == null ) {
				self::logError("<<OUT - injectInformationsFromZone - no data found on zone $zoneId");
				return;
			}
			$temp = self::adjustByUnit($infosZone['temperature'],$infosZone['units']);
			$tmp = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			if (is_object($tmp) ) {
				$tmp->event($temp);
			}
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$tmp->event(self::adjustByUnit($infosZone['setPoint'],$infosZone['units']));
			}
			$tmp = $this->getCmd(null,self::CMD_SCH_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$consigneScheduled = self::getConsigneScheduledForZone($infosZone);
				$tmp->event(self::adjustByUnit($consigneScheduled,$infosZone['units']));
			}
			$consigneInfo = $infosZone['status'] . ";" . $infosZone['until'] . ";" . $infosZone['units'];
			$spc = $infosZone['setPointCapabilities'];
			$consigneInfo = $consigneInfo . ";" . $spc['resolution'] . ";" . $spc['minHeat'] . ";" . $spc['maxHeat'];
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			if (is_object($tmp) ) {
				$tmp->event($consigneInfo);
			}

			if ( self::isDebug() ) {
				self::logDebug('zone ' . $zoneId . '=' . $infosZone['name'] . ' : temp = ' . $infosZone['temperature'] . ', consigne = ' . $infosZone['setPoint'] . ', type = ' . $consigneInfo);
			}
		}
		self::logDebug("<<OUT - injectInformationsFromZone");
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

	function getConsigneScheduled($currentSchedule,$zoneId) {
		$infosZone = self::extractZone($currentSchedule, $zoneId);
		return $infosZone == null ? null : self::getConsigneScheduledForZone($infosZone);
	}

	function getConsigneScheduledForZone($infosZone) {
		$currentDay = strftime('%u', time())-1;
		$currentTime = strftime('%H:%M', time());
		$dsSunday = $infosZone['schedule']['DailySchedules'][6];
		$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
		$lastTemp = $spSundayLast['heatSetpoint'];
		foreach ( $infosZone['schedule']['DailySchedules'] as $ds ) {
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

 	public function toHtmlConsole($pVersion,$version,$replace,$scheduleCurrent) {
		$cmdEtat = $this->getCmd(null,self::CMD_STATE);
		if ( !is_object($cmdEtat) ) return;

		$replace_action = $this->preToHtml($pVersion);
		$replace_action['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

		$_etat = is_object($cmdEtat) ? $cmdEtat->execCmd() : '';
		// "Auto";1 / "AutoWithEco";1/0;H / Away;1/0;D / DayOff;1/0;D / Custom;1/0;D / HeatingOff;1
		// with 1=True ; 0=False ; is the permanentMonde flag
		// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
		$aEtat = explode(';',$_etat);
		$etatImg = 'empty.svg';
		$etatCode = -1;
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
		if ( $aEtat[1] == self::MODE_PERMANENT_ON && $etatCode != self::CODE_MODE_AUTO ) {
			$replace_action['#etatUntilImg#'] = 'override-active.png';
			$replace_action['#etatUntilDisplay#'] = 'none';
		}
		# delay
		else if ( $aEtat[1] == self::MODE_PERMANENT_OFF ) {
			$replace_action['#etatUntilImg#'] = 'temp-override-black.svg';
			// example : $aEtat[2] = "2018-01-28T23:00:00Z"
			$replace_action['#etatUntil#'] = $etatCode == self::CODE_MODE_ECO ? self::gmtToLocalHM($aEtat[2]) : self::gmtToLocalDate($aEtat[2]);
			$replace_action['#etatUntilFull#'] = $aEtat[2];
			$replace_action['#etatUntilDisplay#'] = 'inline';
		}
		else {
			$replace_action['#etatUntilImg#'] = 'empty.svg';	// dummy
			$replace_action['#etatUntilDisplay#'] = 'none';
		}

		$selectStyle = ' selected style="background-color:green;color:white;"';
		$statCmd = $this->getCmd(null,self::CMD_STATISTICS_ID);
		$replace_action['#statDisplay#'] = (is_object($statCmd) && $statCmd->getIsVisible()) ? "block" : "none";
		if ( $replace_action['#statDisplay#'] == 'block') {
			$statScope = !is_object($statCmd) ? 1 : $statCmd->execCmd();
			if ( $statScope === '' ) $statScope = 0;
			$replace_action['#statTitle#'] = self::i18n('Statistiques');
			$replace_action['#statScope0#'] = $statScope == 0 ? $selectStyle : '';
			$replace_action['#statScopeTitle0#'] = self::i18n('Désactivé');
			$replace_action['#statScope1#'] = $statScope == 1 ? $selectStyle : '';
			$replace_action['#statScopeTitle1#'] = self::i18n('Jour');
			$replace_action['#statScope2#'] = $statScope == 2 ? $selectStyle : '';
			$replace_action['#statScopeTitle2#'] = self::i18n('Semaine');
			$replace_action['#statScope3#'] = $statScope == 3 ? $selectStyle : '';
			$replace_action['#statScopeTitle3#'] = self::i18n('Mois');
		}

		$options = '';
		$scheduleFileId = self::getParam(self::iCFG_SCHEDULE_ID,0);
		$jsScheduleFileId = 0;
		foreach ( self::getHebdoNames() as $hn) {
			$options .= '<option value="' . $hn['id'] . '"';
			if ( $hn['id'] == 0 || $hn['id'] == $scheduleFileId ) $options .= $selectStyle;
			if ( $hn['id'] == $scheduleFileId ) {
				$jsScheduleFileId = $scheduleFileId;
			}
			$options .= '>' . $hn['name'] . '</option>';
		}
		$replace_action['#scheduleFileId#'] = $jsScheduleFileId;
		$replace_action['#options#'] = $options;

		// indicateur schedule modifié
		$saveColor = 'white';
		$canRestoreCurrent = 0;
		$saveTitle = self::i18n("Sauvegarde la programmation courante");
		if ( $scheduleFileId != null ) {
			$scheduleSaved = self::getSchedule($scheduleFileId);
			if ( $scheduleSaved != null && $scheduleCurrent != null ) {
				$_scheduleSaved = json_encode($scheduleSaved['zones']);
				$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
				if ( $_scheduleSaved != $_scheduleCurrent ) {
					$saveColor = 'orange';
					$canRestoreCurrent = 1;
					/*if ( self::isDebug() ) {
						self::logDebug("_scheduleSaved = " . $_scheduleSaved);
						self::logDebug("_scheduleCurrent = " . $_scheduleCurrent);
					}*/
					$saveTitle .= ' (' . self::i18n("différente de la dernière programmation restaurée") . ')';
				}
			}
		}
		$replace_action['#title.save#'] = $saveTitle;
		$replace_action['#canRestoreCurrent#'] = $canRestoreCurrent;
		$replace_action['#isAdmin#'] = self::isAdmin();
		$replace_action['#evoSaveColor#'] = $saveColor;

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

		return $replace;
	}

	public function toHtmlTh($pVersion,$version,$replace,$zoneId,$scheduleCurrent,$states) {
		$replace_temp = $this->preToHtml($pVersion);
		// *** TEMPERATURE
		$replace_temp['#etatImg#'] = 'empty.svg';	// dummy
		$replace_temp['#etatUntilImg#'] = 'empty.svg';	// dummy

		$cmdTemperature = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
		$cmdId = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
		$replace_temp['#temperatureId#'] = $cmdId;
		$replace_temp['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "block" : "none";
		$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
		if ( $temperatureNative == null ) {
			$replace_temp['#temperature#'] = '';
			$replace_temp['#temperatureImgDisplay#'] = 'inline;height:36px;width:36px;margin-top:8px;margin-bottom:8px;';
			$replace_temp['#temperatureDisplay2#'] = 'none';
		} else {
			$temperature = self::applyRounding($temperatureNative);
			$replace_temp['#temperature#'] = $temperature . '°';
			$replace_temp['#temperatureImgDisplay#'] = 'inline;height:15px;width:15px;margin-top:20px;';
			$replace_temp['#temperatureDisplay2#'] = 'inline';
		}
		$cmdStatistics = self::getConsole()->getCmd(null,self::CMD_STATISTICS_ID);
		$timeWindow = !is_object($cmdStatistics) || !$cmdStatistics->getIsVisible() || $cmdId == '' ? 0 : max($cmdStatistics->execCmd(), 0);
		$replace_temp['#minMaxDisplay#'] = $timeWindow == 0 ? "none" : "block";
		if ( $timeWindow != 0 ) {
			// https://www.w3schools.com/sql/func_mysql_date_format.asp
			if ( $timeWindow == 1 ) {
				// timeWindow = 1 : same day
				$tw = '%Y%c%d';
			} else if ( $timeWindow == 2 ) {
				// timeWindow = 2 : same week (Monday start of week)
				$tw = '%Y%u';
			} else {
				// timeWindow = 3 : same month
				$tw = '%Y%c';
			}
			$sql = "select * from (";
			$sql .= "select datetime, value from historyArch where cmd_id=$cmdId and DATE_FORMAT(datetime,'$tw')=DATE_FORMAT(now(),'$tw')";
			$sql .= " union";
			$sql .= " select datetime, value from history where cmd_id=$cmdId and DATE_FORMAT(datetime,'$tw')=DATE_FORMAT(now(),'$tw')";
			$sql .= ") as x order by datetime";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ( count($results) == 0 ) {
				$replace_temp['#minMaxDisplay#'] = 'none';
			} else {
				$min = 99;
				$sDateMin = 0;
				$max = 0;
				$sDateMax = 0;
				$totValue = 0.0;
				foreach ($results as $result) {
					$value = $result['value'];
					if ( $value < $min ) { $min = $value; $sDateMin = $result['datetime']; }
					if ( $value > $max ) { $max = $value; $sDateMax = $result['datetime']; }
					$totValue += $value;
				}
				$avg = $totValue / count($results);
				$dMin = strtotime($sDateMin);
				$dMax = strtotime($sDateMax);
				$dMaxMinus1 = 0;
				$dMinPlus1 = 0;
				foreach ($results as $result) {
					$dt = strtotime($result['datetime']);
					$value = $result['value'];
					if ( $dMaxMinus1 == 0 && $dt > $dMax && $max - $value >= 1.0 ) {
						$dMaxMinus1 = $dt - $dMax;
					}
					if ( $dMinPlus1 == 0 && $dt > $dMin && $value - $min >= 1.0 ) {
						$dMinPlus1 = $dt - $dMin;
					}
				}
				$replace_temp['#statTitle#'] = self::i18n($timeWindow == 1 ? "Statistiques du jour" : ($timeWindow == 2 ? "Statistiques de la semaine" : "Statistiques du mois"));
				$replace_temp['#statRazTimeTitle#'] = self::i18n("valeurs réinitialisées");
				$replace_temp['#statRazTime#'] = $timeWindow == 1 ? self::tsToLocalHMS(strtotime($results[0]['datetime'])) : $results[0]['datetime'];

				$replace_temp['#statLastReadTitle#'] = self::i18n("dernière lecture");
				$replace_temp['#statLastRead#'] = self::tsToLocalHMS(strtotime($results[count($results)-1]['datetime']));

				$replace_temp['#statMaxTitle#'] = self::i18n("max");
				$replace_temp['#statThMax#'] = self::applyRounding($max) . '°';
				$replace_temp['#statWhenMax#'] = $timeWindow == 1 ? self::tsToLocalHMS($dMax) : $sDateMax;
				$replace_temp['#statWhenMinus1#'] = $dMaxMinus1 == 0 ? '(' . self::i18n("pas encore") . ')' : self::tsToAbsoluteHMS($dMaxMinus1);

				$replace_temp['#statAvgTitle#'] = self::i18n("moy");
				$replace_temp['#statThAvg#'] = self::applyRounding($avg) . '°';
				$replace_temp['#statNbPoints#'] = self::i18n("{0} points", count($results));

				$replace_temp['#statMinTitle#'] = self::i18n("min");
				$replace_temp['#statThMin#'] = self::applyRounding($min) . '°';
				$replace_temp['#statWhenMin#'] = $timeWindow == 1 ? self::tsToLocalHMS($dMin) : $sDateMin;
				$replace_temp['#statWhenPlus1#'] = $dMinPlus1 == 0 ? '(' . self::i18n("pas encore") . ')' : self::tsToAbsoluteHMS($dMinPlus1);
			}
		}

		// *** CONSIGNE
		$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
		$replace_temp['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
		$replace_temp['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "block" : "none";
		$consigne = is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0;
		$currentMode = self::getCurrentMode();
		$isOff = $currentMode == self::MODE_OFF;
		$isEco = $currentMode == self::MODE_ECO;
		$isAway = $currentMode == self::MODE_AWAY;
		$isDayOff = $currentMode == self::MODE_DAYOFF;
		$isCustom = $currentMode == self::MODE_CUSTOM;
		if ( $isOff ) $infoConsigne = 'OFF';
		else if ( $consigne == null ) $infoConsigne = '-';
		else $infoConsigne = $consigne . '°';
		$replace_temp['#consigne#'] = $infoConsigne;
		$replace_temp['#consigneBG#'] = self::getBackColorForTemp($consigne,$isOff);

		$replace_temp['#temperatureImg#'] = $temperatureNative == null ? 'battlow.png' : ($temperatureNative < $consigne ? 'chauffage_on.gif' : 'check-mark-md.png');

		$cmdConsigneInfos = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);

		$consigneTypeImg = null;
		if ( is_object($cmdConsigneInfos) && $cmdConsigneInfos->getIsVisible() ) {
			# $consigneInfos = FollowSchedule / PermanentOverride / TemporaryOverride ; 2018-01-28T23:00:00Z / <empty> ; Celsius/?? ; 0.5 (step) ; 5 (min) ; 25 (max)
			$consigneInfos = explode(';', $cmdConsigneInfos->execCmd());
			$consigneScheduled = self::getConsigneScheduled($scheduleCurrent,$zoneId);
			$consigneTip = '';
			$consigneTypeUntil = '';
			$consigneTypeUntilFull = '';
			$adjustAvailable = true;
			if ( $isEco ) {
				$consigneTypeUntilFull = self::i18n("Mode économie (remplace {0}°)", $consigneScheduled);
				$consigneTypeImg = 'i_economy_white.png';
				// $adjustAvailable = true;		available when ECO mode
			} else if ( $isDayOff ) {
				$consigneTypeUntilFull = self::i18n("Mode congé");
				$consigneTypeImg = 'i_dayoff_white.png';
				// $adjustAvailable = true;		available when DAY-OFF mode
			} else if ( $isCustom ) {
				$consigneTypeUntilFull = self::i18n("Mode personnalisé");
				$consigneTypeImg = 'i_custom_white.png';
				// $adjustAvailable = true;		available when CUSTOM mode
			}
			if ( $isOff ) {
				$consigneTypeUntilFull = self::i18n("Consigne forcée à {0}° au lieu de {1}°", [$consigne, $consigneScheduled]);
				$consigneTypeImg = 'i_off_white.png';
				$adjustAvailable = false;
			} else if ( $isAway ) {
				$consigneTypeUntilFull = self::i18n("Mode inoccupé (remplace {0}°)", $consigneScheduled);
				$consigneTypeImg = 'i_away_white.png';
				$adjustAvailable = false;		// unavailable when AWAY mode
			} else if ( !$isEco &&!$isDayOff && !$isCustom && $consigneInfos[0] == 'FollowSchedule' ) {
				if ( $consigneScheduled != null && $consigne != null ) {
					if ( $consigne < $consigneScheduled ) {
						$consigneTypeUntilFull = self::i18n("Optimisation active : consigne inférieure à suivre active (remplace {0}°)", $consigneScheduled);
						$consigneTypeImg = 'down green.svg';
					} else if ( $consigne > $consigneScheduled ) {
						$consigneTypeUntilFull = self::i18n("Optimisation active : consigne supérieure à suivre active (remplace {0}°)", $consigneScheduled);
						$consigneTypeImg = 'up red.svg';
					}
				}
			} else if ( $consigneInfos[0] == 'TemporaryOverride' ) {
				$consigneTip = '';
				$consigneTypeImg = 'temp-override.svg';
				// example : $consigneInfos[1] = "2018-01-28T23:00:00Z"
				$time = self::gmtToLocalHM($consigneInfos[1]);
				$consigneTypeUntil = $time;
				$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}° jusqu'à {1}", [$consigneScheduled, $time]);
			} else if ( $consigneInfos[0] == 'PermanentOverride' ) {
				$consigneTypeImg = 'override-active.png';
				$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}°", $consigneScheduled);
			}
			$replace_temp['#consigneTypeUntil#'] = $consigneTypeUntil;
			$replace_temp['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
			$replace_temp['#consigneTip#'] = $consigneTip;
			$replace_temp['#zoneId#'] = $zoneId;
			$replace_temp['#fileId#'] = self::getParam(self::iCFG_SCHEDULE_ID,0);
			//  additional infos
			$replace_temp['#currentConsigne#'] = $consigneScheduled;
			//$replace_temp['#currentConsigneUntil#'] = 'hh:mm';
		}

		$cmdSetConsigne = $this->getCmd(null,self::CMD_SET_CONSIGNE_ID);
		if ( is_object($cmdSetConsigne) && !$cmdSetConsigne->getIsVisible() ) {
			$replace_temp['#setConsigneDisplay#'] = "none";
		} else {
		$cmdId = $replace_temp['#temperatureId#'];
			$replace_temp['#setConsigneDisplay#'] = "table-cell";
			// adjust temp infos
			$replace_temp['#adjustAvailable#'] = $adjustAvailable ? 'true' : 'false';
			$replace_temp['#msgAdjustConsigneUnavailable#'] = self::i18n("Le mode actif ne permet pas d'ajuster les consignes");
			$replace_temp['#msgEnforceConsigne#'] = self::i18n("Forçage de la consigne programmée de {0}°", $consigneScheduled);
			$replace_temp['#adjustStep#'] = $consigneInfos[3];
			$replace_temp['#adjustLow#'] = $consigneInfos[4];
			$replace_temp['#adjustHigh#'] = $consigneInfos[5];
			$replace_temp['#canReset#'] = $consigneScheduled == $consigne ? 0 : 1;
			$replace_temp['#backScheduleTitle#'] = self::i18n('Retour à la valeur programmée de {0}°', $consigneScheduled);
		}
		$replace_temp['#consigneTypeImg#'] = $consigneTypeImg == null ? 'empty.svg' : $consigneTypeImg;
		$replace_temp['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'inline-block';
		// arguments names
		$replace_temp['#argFileId#'] = self::ARG_FILE_ID;
		$replace_temp['#argZoneId#'] = self::ARG_ZONE_ID;
		// codes
		$replace_temp['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_temp['#evoDefaultShowingScheduleMode#'] = self::getParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

		foreach ($this->getCmd('action') as $cmd) {
			$replace_temp['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		$replace['#consoleContent#'] = '';
		$replace['#temperatureContent#'] = template_replace($replace_temp, getTemplate('core', $version, 'temperature_content', __CLASS__));

		return $replace;
	}

	function setToHtmlProperties($pStates,$pScheduleCurrent,$pMsgInfo) {
		//self::logDebug("setToHtmlProperties(..$pMsgInfo)");
		self::setCacheData('toHtmlData_' . $this->getConfiguration(self::CONF_ZONE_ID),
			array("states"=>$pStates, "scheduleCurrent"=>$pScheduleCurrent, "msgInfo"=>$pMsgInfo));
	}
	function getToHtmlProperty($name) {
		$zId = $this->getConfiguration(self::CONF_ZONE_ID);
		$zData = self::getCacheData('toHtmlData_'.$zId);
		return (!is_array($zData) || !array_key_exists($name,$zData)) ? null : $zData[$name];
	}
	function removeToHtmlProperties() {
		self::doCacheRemove('toHtmlData_' . $this->getConfiguration(self::CONF_ZONE_ID));
	}

	function refreshComponent($infosZones=null,$inject=false) {
		//self::logDebug("IN>> refreshComponent");
		if ( is_array($infosZones) && $inject ) {
			$this->injectInformationsFromZone($infosZones);
		}
		$this->refreshWidget();	// does the toHtml by event (in another Thread, so the cache usage)
		//self::logDebug("<<OUT refreshComponent");
	}

 	public function toHtml($pVersion='dashboard') {
		self::doCacheRemove('evohomeWidget' . $pVersion . $this->getId());
		$zoneId = $this->getConfiguration(self::CONF_ZONE_ID);
		self::logDebug("IN>> toHtml($pVersion) on $zoneId (" . $this->getName() . ")");

		$replace = $this->preToHtml($pVersion);
		if (!is_array($replace)) return $replace;

		$version = jeedom::versionAlias($pVersion);

		$cachedData = self::getCacheData(self::CACHE_STATES);
		$refreshCache = false;
		$scheduleCurrent = $this->getToHtmlProperty("scheduleCurrent");
		//self::logDebug("-- private scheduleCurrent ? " . (is_array($scheduleCurrent) ? "yes" : "no"));
		if ( !is_array($scheduleCurrent) ) {
			if ( is_array($cachedData) && array_key_exists('scheduleCurrent',$cachedData) ) {
				self::logDebug("use cachedData for scheduleCurrent");
				$scheduleCurrent = $cachedData['scheduleCurrent'];
			}
		}
		if ( !is_array($scheduleCurrent) ) {
			$scheduleCurrent = self::getSchedule(self::CURRENT_SCHEDULE_ID);
			$refreshCache = true;
		}

		// settings depending of the "states" vars :
		$states = $this->getToHtmlProperty("states");
		//self::logDebug("-- private states ? " . (is_array($states) ? "yes" : "no"));
		if ( !is_array($states) ) {
			if ( is_array($cachedData) && array_key_exists('states',$cachedData) ) {
				$states = $cachedData['states'];
				self::logDebug("use cachedData for states");
			}
		}
		if ( !is_array($states) ) {
			$states = self::getStates(self::getInformationsAllZonesE2());
			$refreshCache = true;
		}
		if ( $refreshCache ) {
			$cachedData = array("states"=>$states, "scheduleCurrent"=>$scheduleCurrent);
			self::setCacheData(self::CACHE_STATES, $cachedData, self::CACHE_STATES_DURATION);
		}

		$msgInfo = $this->getToHtmlProperty("msgInfo");
		//self::logDebug("-- private msgInfo=$msgInfo");

		// single usage :
		$this->removeToHtmlProperties();

		if ( $zoneId == self::ID_CONSOLE ) {
			// CONSOLE
			$replace = $this->toHtmlConsole($pVersion,$version,$replace,$scheduleCurrent);
			$prevStatVisible = self::getCacheData("STAT_PREV_VISIBLE");
			// got a STAT_PREV_VISIBLE, during console refresh
			self::doCacheRemove("STAT_PREV_VISIBLE");
			if ( $prevStatVisible != '' ) {
				$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
				if ( is_object($cmd) && ($cmd->getIsVisible() ? '1' : '0') != $prevStatVisible ) {
					self::logDebug("** during console refresh, detect change stat visible state, launch a full refesh...");
					self::refreshAll(self::getInformationsAllZonesE2());
				}
			}
		}
		else {
			// TH WIDGET
			$replace = $this->toHtmlTh($pVersion,$version,$replace,$zoneId,$scheduleCurrent,$states);
		}

		$replace['#background-color#'] = '#F6F6FF';
		$replace['#evoBackgroundColor#'] = '#F6F6FF';
		$replace['#evoCmdBackgroundColor#'] = '#3498db';

		$replace['#apiAvailable#'] = !$states['unread'] ? "true" : "false";
		$replace['#msgApiUnavailable#'] = self::i18n('Fonction indisponible (erreur en API)');
		$replace['#evoTemperatureColor#'] = $states['unread'] ? 'gray' : 'black';
		$replace['#evoConsigneColor#'] = $states['unread'] ? 'lightgray' : 'white';
		$replace['#iazColorState#'] = $states['isRunning'] ? 'crimson' : ($states['unread'] ? 'red' : ($states['isAccurate'] || self::getParam(self::CFG_ACCURACY,1) == 1 ? 'lightgreen' : 'coral'));
		$replace['#iazIcon#'] = $states['isRunning'] ? 'fa-spinner fa-pulse' : ($states['unread'] ? 'fa-chain-broken' : 'fa-circle');
		$replace['#iazIconSize#'] = $states['isRunning'] ? '16' : ($states['unread'] ? '10' : '10');

		$waitNext = true;
		if ( $states['unread'] ) {
			$txt = self::i18n('Dernière lecture : {0} (problème en lecture)', $states['lastRead']);
		} else if ( $states['isRunning'] ) {
			$txt = self::i18n('Lecture en cours...');
			$waitNext = false;
		} else if ( $states['isAccurate'] ) {
			$txt = self::i18n('Dernière lecture : {0}', $states['lastRead']);
		} else {
			$txt = self::i18n('Dernière lecture : {0} (mode précis indisponible)', $states['lastRead']);
		}
		if ( $waitNext ) {
			$addTime = self::getCacheRemaining(self::CACHE_CRON_TIMER);
			if ( $addTime <= 5 ) $addTime = self::getLoadingInterval();
			$tsNext = time() + $addTime;
			if ( self::getParam(self::CFG_LOADING_SYNC,0) == 1 ) {
				// adjust fine time :
				$intvl = self::getLoadingInterval();
				$min = intVal(strftime("%M",$tsNext));
				if ( ($mod=$min % $intvl) != 0 ) {
					// intvl - (mn % intvl) ) * 60 ; mn=24 ; intvl=10 => 10 - (24 % 10) => 10 - 4 = 6
					$tsNext += 60 * ($intvl - $mod);
				}
			}
			$txt .= " ; " . self::i18n("prochaine lecture") . " ~" . strftime('%H:%M',$tsNext);
		}
		$replace['#iazLastRead#'] = $txt;

		$replace['#evoMsgInfo#'] = str_replace("'", "\'", $msgInfo);

		$html = template_replace($replace, getTemplate('core', $version, 'evohome', __CLASS__));
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		self::logDebug("<<OUT - toHtml");
		return $html;
	}

	/* Called when the  evoHistoryRetention is saved from the configuration panel */
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
	* Non obligatoire : permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
	}
	*/

	/*************** Statics about Scheduling ********************/

	static function getSchedule($fileId,$dateTime=0,$doRefresh=false) {
		if ( self::isDebug() ) self::logDebug('IN>> - getSchedule(' . $fileId . ')');
		if ( $fileId == self::CURRENT_SCHEDULE_ID ) {
			$schedule = self::getCacheData(self::CACHE_CURRENT_SCHEDULE);
			if ( !is_array($schedule) ) {
				$infosZones = self::getInformationsAllZonesE2($doRefresh);
				if ( !is_array($infosZones) ) {
					if ( self::isDebug() ) self::logDebug('<<OUT - getSchedule(' . self::CURRENT_SCHEDULE_ID . ') - error while getInformationsAllZonesE2 (see above)');
					// avoid request again when we know requesting does not work
					self::setCacheData(self::CACHE_CURRENT_SCHEDULE, array('dummy','1'), self::CACHE_STATES_DURATION);
					return null;
				}
				$scheduleByZone = array();
				foreach ( $infosZones['zones'] as $zone ) {
					$scheduleByZone[] = array(
						'zoneId' => $zone['zoneId'],
						'name' => $zone['name'],
						'schedule' => $zone['schedule']);
				}
				$schedule = array('datetime'=>$dateTime, 'zones'=>$scheduleByZone);
				self::setCacheData(self::CACHE_CURRENT_SCHEDULE, $schedule, self::CACHE_STATES_DURATION);
			} else {
				self::logDebug('got getSchedule(0) from cache');
			}
			self::logDebug('<<OUT - getSchedule(0)');
			return $schedule;
		}
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos != null ) {
			$fileContent = file_get_contents($fileInfos['fullPath']);
			if ( self::isDebug() ) self::logDebug('getSchedule from ' . $fileInfos['fullPath']);
			$fileContentDecoded = json_decode($fileContent, true);
			if ( self::isDebug() ) self::logDebug('<<OUT - getSchedule(' . $fileId . ')');
			return $fileContentDecoded;
		}
		if ( self::isDebug() ) self::logDebug('<<OUT - getSchedule(' . $fileId . ') non trouvé');
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

	static function updateScheduleFileId($fileId, $msgInfo='', $schedule=null) {
		self::logDebug("IN>> - updateScheduleFileId");
		self::setParam(self::iCFG_SCHEDULE_ID, $fileId);

		// read data without schedules infos
		$allInfos = self::getInformationsAllZonesE2($schedule != null, $schedule == null);
		if ( $schedule != null && is_array($allInfos) ) {
			self::logDebug("updateScheduleFileId : merge restored schedule data with fresh InformationsAllZonesE2");
			foreach ( $schedule['zones'] as &$srcZone ) {
				foreach ( $allInfos['zones'] as &$dstZone ) {
					if ( $srcZone['zoneId'] == $dstZone['zoneId'] ) {
						if ( self::isDebug() ) self::logDebug("- merging zone " . $dstZone['zoneId']);
						$dstZone['schedule'] = $srcZone['schedule'];
						break;
					}
				}
			}
			$tsRemain = self::getCacheRemaining(self::CACHE_IAZ);
			self::setCacheData(self::CACHE_IAZ, $allInfos, $tsRemain);
		}
		self::refreshAll($allInfos,false,$msgInfo);

		self::logDebug("<<OUT - updateScheduleFileId");
	}

	/*************** Statics about AJAX calls ********************/

	static function ajaxChangeStatScope($newStatScope) {
		$console = self::getConsole();
		if ( $console != null ) {
			$cmdStatistics = $console->getCmd(null,self::CMD_STATISTICS_ID);
			if ( is_object($cmdStatistics) ) {
				$cmdStatistics->event($newStatScope);
				self::refreshAll(self::getInformationsAllZonesE2());
			}
		}
	}

	static function ajaxSynchronizeTH($locationId,$sZones,$resizeWhenSynchronize) {
		self::logDebug("IN>> - ajaxSynchronizeTH");
		$zones = self::jsonDecode($sZones, "ajaxSynchronizeTH");
		$zones[] = array("id"=>self::ID_CONSOLE, "name"=>self::i18n("Console"));
		$nbAdded = 0;
		foreach ($zones as $zone) {
			if ( self::isDebug() ) self::logDebug("Check for " . $zone["name"]);
			$todo = true;
			foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
				if ( $eqLogic->getConfiguration(self::CONF_ZONE_ID) == $zone["id"] ) {
					self::logDebug("-- refresh existing (cmds & size)");
					if ( $resizeWhenSynchronize ) {
						if ( $zone["id"] == self::ID_CONSOLE ) {
							$eqLogic->setDisplay("height", "162px");
							$eqLogic->setDisplay("width", "176px");
						} else {
							$eqLogic->setDisplay("height", "120px");
							$eqLogic->setDisplay("width", "220px");
						}
					}
					$eqLogic->save();
					$todo = false;
					break;
				}
			}
			if ($todo) {
				self::logDebug("-- create");
				$eqLogic = new evohome();
				$eqLogic->setEqType_name(__CLASS__);
				//$eqLogic->setLogicalId(xxx);	will be undefined (useless in our case) - should be zoneId instead of Configuration prop ;)
				$eqLogic->setName($zone["name"]);
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setCategory("heating", 1);
				$eqLogic->setConfiguration(self::CONF_ZONE_ID, $zone["id"]);
				foreach (object::all() as $obj) {
					if ( stripos($zone["name"],$obj->getName()) !== false || stripos($obj->getName(),$zone["name"]) !== false ) {
						$eqLogic->setObject_id($obj->getId());
						break;
					}
				}
				if ( $zone["id"] == self::ID_CONSOLE ) {
					$eqLogic->setDisplay("height", "162px");
					$eqLogic->setDisplay("width", "176px");
				} else {
					$eqLogic->setDisplay("height", "120px");
					$eqLogic->setDisplay("width", "220px");
				}
				$eqLogic->save();
				$nbAdded += 1;
				self::logDebug("-- done !");
			}
		}
		self::logDebug("<<OUT - ajaxSynchronizeTH");
		return $nbAdded > 0;
	}

	/************************ Actions ****************************/

	function doCaseAction($paramAction, $parameters) {
		if ( self::isDebug() ) self::logDebug('doCaseAction(' . $paramAction . ')');
		switch ($paramAction) {
			// -- Console
			case self::CMD_SET_MODE:
				self::setMode($parameters);
				break;

			case self::CMD_SAVE:
				self::saveSchedule($parameters);
				break;

			case self::CMD_RESTORE:
				self::restoreSchedule($parameters);
				break;

			case self::CMD_DELETE:
				self::deleteSchedule($parameters);
				break;

			// -- Temperature/Consigne
			case self::CMD_SET_CONSIGNE_ID:
				self::setConsigne($parameters);
				break;
		}
	}

	function setMode($parameters) {
		$codeMode = $parameters[self::ARG_CODE_MODE];
		if ( $codeMode == null || $codeMode == '' ) {
			self::logDebug('IN>><<OUT - setMode called without code');
			return;
		}
		self::waitingIAZReentrance('SetMode-' . rand(0,10000));
		self::lockCron();
		if ( self::isDebug() ) self::logDebug('IN>> - setMode with code=' . $codeMode);

		// Call python function
		self::logDebug('setMode : call python');
		$td = time();
		$aRet = self::runPython('SetModeE2.py', $codeMode);
		if ( self::isDebug() ) self::logDebug('setMode : python return in ' . (time() - $td) . 'sec');
		if ( !is_array($aRet) ) {
			self::logError("Error while setMode : response was empty or malformed");
			$msgInfo = self::i18n("Erreur en changement de mode");
		} else if ( !$aRet[self::PY_SUCCESS] ) {
			self::logError("Error while setMode : <" . json_encode($aRet) . ">");
			if ( self::isDebug() ) self::logDebug(' -- datas = : ' . $retValue);
			$msgInfo = self::i18n("Erreur en changement de mode : {0} - {1}", [$aRet["code"], $aRet["message"]]);
		} else {
			sleep(10);	// wait a bit before loading new values
			$msgInfo = "1" . self::i18n("Le mode de présence a été correctement modifié");
		}
		self::getInformationsAllZonesE2(true,true,$msgInfo);

		self::logDebug('<<OUT - setMode');
		self::unlockCron();
	}

	function saveSchedule($parameters) {
		$fileName = $parameters[self::ARG_FILE_NAME];
		$fileId = $parameters[self::ARG_FILE_ID];
		$commentary = $parameters[self::ARG_FILE_REM];
		$newSchedule = $parameters[self::ARG_FILE_NEW_SCHEDULE];
		if ( self::isDebug() ) self::logDebug('IN>> - saveSchedule(' . $fileName . ', ' . $fileId . ', ' . ($newSchedule == null ? '<currentSchedule>' : '<newSchedule>') . ')');
		self::waitingIAZReentrance('SaveSChedule-' . rand(0,10000));
		self::lockCron();
		$dateTime = time();
		if ( (int)$fileId == self::CURRENT_SCHEDULE_ID ) {
			$fileId = $dateTime;
			$filePath = self::getSchedulePath() . $fileId . '_' . $fileName;
		} else {
			$fileInfos = self::getFileInfosById((int)$fileId);
			$filePath = $fileInfos['fullPath'];
		}
		if ( self::isDebug() ) self::logDebug('launch save action with fileName="' . $filePath . '"');
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
		self::updateRestoreList();
		self::unlockCron();
	}

	function restoreSchedule($parameters) {
		$fileId = $parameters[self::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($fileId);
		if ( $fileInfos == null ) {
			self::logError('restoreSchedule on unknown ID=' . $fileId);
			return;
		}
		self::waitingIAZReentrance('RestoreSchedule-' . rand(0,10000));
		self::lockCron();
		if ( self::isDebug() ) self::logDebug('restoreSchedule on saving ID=' . $fileId . ', name=' . $fileInfos['name']);
		// Call python function
		self::logDebug('restoreSchedule : call python');
		$td = time();
		$aRet = self::runPython('RestaureZonesE2.py', '"' . $fileInfos['fullPath'] . '"');
		if ( self::isDebug() ) self::logDebug('restoreSchedule : python return in ' . (time() - $td) . 'sec : ' . json_encode($aRet));
		if ( !is_array($aRet) ) {
			self::logError('Error while restoreSchedule : response was empty or malformed');
			// this call used to remove the loading mask on the screen
			self::refreshConsole(self::i18n("Erreur pendant l'envoi de la programmation"));
		}
		else if ( !$aRet[self::PY_SUCCESS] ) {
			self::logError('Error while restoreSchedule : <' . json_encode($aRet) . '>');
			// this call used to remove the loading mask on the screen
			self::refreshConsole(self::i18n("Erreur pendant l'envoi de la programmation : {0} : {1}", [$aRet["code"], $aRet["message"]]));
		} else {
			$fp = fopen($fileInfos['fullPath'], 'r');
			$fileContent = fread($fp,filesize($fileInfos['fullPath']));
			$schedule = self::jsonDecode($fileContent, 'restoreSchedule2');
			fclose($fp);
			self::updateScheduleFileId($fileId, "1".self::i18n("L'envoi de la programmation s'est correctement effectué"), $schedule);
		}
		self::unlockCron();
	}

	function deleteSchedule($parameters) {
		$fileId = $parameters[self::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($fileId);
		$msgInfo = '';
		if ( $fileInfos == null ) {
			self::logError('deleteSchedule on unknown ID=' . $fileId);
			$msgInfo = self::i18n("Fichier introuvable");
		} else {
			$cmdRestoreId = self::getConsole()->getCmd(null, self::CMD_RESTORE)->getId();
			$sql = "select count(*) as cnt from scenarioExpression where expression = '#" . $cmdRestoreId . "#' and options like '%\"select\":\"$fileId\"%'";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ( count($results) == 1 && $results[0]['cnt'] == 1 ) {
				if ( self::isDebug() ) self::logDebug("Schedule $fileId is used in Scenario !");
				$msgInfo = self::i18n("Le fichier '{0}' est utilisé dans un {1} Scenario(s)", [ $fileInfos['name'], $results[0]['cnt']]);
			} else {
				if ( self::isDebug() ) self::logDebug('deleteSchedule on ID=' . $fileId);
				unlink($fileInfos['fullPath']);
				self::updateRestoreList();
			}
		}
		self::refreshConsole($msgInfo);
	}

	function setConsigne($parameters) {
		self::logDebug('IN>> - setConsigne');

		$params = explode('#', $parameters[self::ARG_CONSIGNES_DATA]);
		// $data = 'manuel/auto # zoneId # value (nn.n or 0=reset) # until ('null' or 'timevalue' (feature)
		$data = array('mode'=>$params[0],
						 'zoneId'=>$params[1],	// string or numeric
						 'value'=>$params[2],		// keep in string
						 'until'=>$params[3]);	// unused yet (forced to null == PermanentOverride)
		if ( $data['mode'] == 'auto' ) {
			// triggered by scenario
			if ( self::getStates()['unread'] ) {
				self::logDebug('<<OUT - setConsigne auto (from scenario) - unavailable (api off)');
				return;
			}
			$currentMode = self::getCurrentMode();
			if ( $currentMode == self::MODE_OFF || $currentMode == self::MODE_AWAY ) {
				self::logDebug('<<OUT - setConsigne auto (from scenario) - unavailable (incompatible mode)');
				return;
			}
		}
		if ( $data['until'] == '' || $data['until'] == 'null' ) $data['until'] = null;
		$cmdParam = str_replace('"', '\"', json_encode($data));
		if ( self::isDebug() ) self::logDebug("setConsigne with " . $cmdParam);

		// ...appel python...
		$infos = self::runPython('SetTempE2.py', $cmdParam);
		$updated = false;
		if ( self::isDebug() ) self::logDebug("retour setTemp = " . json_encode($infos));
		if ( !is_array($infos) ) {
			self::logError('Error while setConsigne : response was empty or malformed');
		} else {
			$infosZones = self::getInformationsAllZonesE2();
			$msgInfo = null;
			if ( !$infos[self::PY_SUCCESS] ) {
				if ( self::isDebug() ) self::logDebug("Error while SetTempE1 : <" . json_encode($infos) . ">");
				$msgInfo = self::i18n("Erreur pendant l'envoi de la consigne : {0} - {1}", [$infos["code"], $infos["message"]]);
			} else if ( is_array($infosZones) ) {
				// Refresh zoneId
				// merge requested value into zone data :
				foreach ( $infosZones['zones'] as &$infosZone ) {
					if ( $data['zoneId'] == $infosZone['zoneId'] ) {
						$infosZone['setPoint'] = $data['value'] == 0 ? self::getConsigneScheduledForZone($infosZone) : $data['value'];
						$infosZone['status'] = $data['value'] == 0 ? 'FollowSchedule' : ($data['until'] == null ? 'PermanentOverride' : 'TemporaryOverride');
						$infosZone['until'] = $data['until'] == null ? 'NA' : $data['until'];
						$msgInfo = "1" . self::i18n("La consigne de {0}° a été correctement envoyée vers : {1}", [$infosZone['setPoint'], self::getComponent($infosZone['zoneId'])->getName()]);
						$updated = true;
						break;
					}
				}
				if ( $updated ) {
					self::setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION);
				}
			}
			if ( is_array($infosZones) ) {
				$TH = self::getComponent($data['zoneId']);
				if ( $TH != null ) {
					$states = self::getStates($infosZones);
					$scheduleCurrent = self::getSchedule(self::CURRENT_SCHEDULE_ID);
					$TH->setToHtmlProperties($states,$scheduleCurrent,$msgInfo);
					$TH->refreshComponent($infosZones,$updated);
				}
			}
		}
		self::logDebug('<<OUT - setConsigne');
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
