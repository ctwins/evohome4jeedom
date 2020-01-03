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
require_once 'evohome.utils.php';

class evohome extends eqLogic {
	//public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

	const CFG_USER_NAME = 'evoUserName';
	const CFG_PASSWORD = 'evoPassword';
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
	const CFG_SCH_EDIT_AVAILABLE = "evoEditAvailable";
	const CFG_SHOWING_MODES = 'evoShowingModes';
	const CFG_SHOWING_MODE_CONSOLE = 'C';
	const CFG_SHOWING_MODE_POPUP = 'P';
	const CFG_HP_SETTING_MODES = 'evoHeatPointSettingModes';
	const CFG_HP_SETTING_MODE_INTEGRATED = 'I';
	const CFG_HP_SETTING_MODE_POPUP = 'P';
	const CFG_BACKCOLOR_TITLE_MODES = 'evoBackColorTitleModes';
	const CFG_BCT_MODE_NONE = '0';
	const CFG_BCT_MODE_SYSTEM = '1';
	const CFG_BCT_MODE_2T = '2';
	const CFG_BCT_MODE_NT = '3';
	const CFG_BCT_2N_A = "CFG_BCT_MODE_2T_A";
	const CFG_BCT_2N_B = "CFG_BCT_MODE_2T_B";
	const iCFG_SCHEDULE_ID = 'intScheduleFileId';
	const CONF_TYPE_EQU = 'typeEqu';
	const CONF_LOC_ID = 'locationId';
	// 0.4.2 - Deprecated
	const CONF_ZONE_ID = 'zoneId';
	const CONF_MODEL_TYPE = 'modelType';
	const CONF_ALLOWED_SYSTEM_MODE = 'allowedSystemMode';
	const TYPE_EQU_CONSOLE = 'C';
	const TYPE_EQU_THERMOSTAT = 'TH';
	const MODEL_TYPE_HEATING_ZONE = 'HeatingZone';
	const MODEL_TYPE_ROUND_WIRELESS = 'RoundWireless';
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
	const FollowSchedule = "FollowSchedule";
	const PermanentOverride = "PermanentOverride";
	const TemporaryOverride = "TemporaryOverride";
	const CMD_SET_CONSIGNE_ID = 'setConsigne';
	const ID_NO_ZONE = -2;
	const OLD_ID_CONSOLE = -1;
	const MODE_AUTO = 'Auto';
	const MODE_ECO = 'AutoWithEco';
	const MODE_AWAY = 'Away';
	const MODE_DAYOFF = 'DayOff';
	const MODE_CUSTOM = 'Custom';
	const MODE_OFF = 'HeatingOff';
	const MODE_PERMANENT_ON = '1';
	const MODE_PERMANENT_OFF = '0';
	const ARG_LOC_ID = 'locId';
	const ARG_CODE_MODE = 'select';	// 0.2.1 : codeMode' replaced by 'select' for compatibility with parameter of scenario
	const ARG_FILE_NAME = 'fileName';
	const ARG_FILE_ID = 'select';	// 0.3.2 : fix (was fileId) for compatibility with scenario select field
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
	const CACHE_CRON_ACTIF = 'cronActive';
	const CACHE_IAZ = 'evohomegetInformationsAllZonesE2';
	const CACHE_INFOS_API = 'evohomeInfosApi';
	const CACHE_IAZ_DURATION = 86400;
	const CACHE_IAZ_RUNNING = 'getInformationsAllZonesE2Running';
	const CACHE_LIST_LOCATIONS = 'evohomeListLocations';
	const CACHE_STATES_DURATION = 30;
	const CACHE_STATES = 'evohomeStates';
	const CACHE_CURRENT_SCHEDULE = 'evohomeCurrentSchedule';
	const CACHE_PYTHON_RUNNING = 'PYTHON_RUNNING';
	const CACHE_STAT_PREV_VISIBLE = 'STAT_PREV_VISIBLE';
	const CACHE_SCHEDULE_DELTA = 'SCHEDULE_DELTA';
	const CACHE_SYNCHRO_RUNNING = "SYNCHRO_RUNNING";
	const PY_SUCCESS = 'success';
	// InfosZones
	const IZ_TIMESTAMP = 'timestamp';
	# -- infosAPI :
	const IZ_API_V1 = 'apiV1';
	const IZ_GATEWAY_CNX_LOST = 'cnxLost';
	const IZ_SESSION_ID_V1 = 'session_id_v1';
	const IZ_USER_ID_V1 = 'user_id_v1';
	const IZ_SESSION_STATE_V1 = 'session_state_v1';
	const IZ_SESSION_ID_V2 = 'access_token';
	const IZ_SESSION_EXPIRES_V2 = 'access_token_expires';
	const IZ_SESSION_STATE_V2 = 'token_state';

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

	private static function getModeFromHName($hName) {
		switch ( $hName ) {
			case self::MODE_AUTO:	return self::CODE_MODE_AUTO;
			case self::MODE_ECO:	return self::CODE_MODE_ECO;
			case self::MODE_AWAY:	return self::CODE_MODE_AWAY;
			case self::MODE_DAYOFF:	return self::CODE_MODE_DAYOFF;
			case self::MODE_CUSTOM:	return self::CODE_MODE_CUSTOM;
			case self::MODE_OFF:	return self::CODE_MODE_OFF;
		}
		// NB : 'AutoWithReset' seems == Auto (sent with SetModeE2, data retrieve = Auto)
		return null;
	}

	private static function getModeName($code) {
		switch ( $code ) {
			case self::CODE_MODE_AUTO:		return self::i18n('Planning');
			case self::CODE_MODE_ECO:		return self::i18n('Economie');
			case self::CODE_MODE_AWAY:		return self::i18n('Innocupé');
			case self::CODE_MODE_DAYOFF:	return self::i18n('Congé');
			case self::CODE_MODE_CUSTOM:	return self::i18n('Personnalisé');
			case self::CODE_MODE_OFF:		return self::i18n('Arrêt');
		}
		return "";
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

		// 0.4.2 - change dependency check
		// 0.4.3 - change 2>nul >> 2>dev/null (thanks github/titidnh)
		$x = system::getCmdSudo() . ' dpkg-query --show python-requests 2>/dev/null | wc -l';
		$r = exec($x);
		if ( isDebug() ) logDebug("dependancy_info 1/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		$x = system::getCmdSudo() . system::get('cmd_check') . ' gd | grep php | wc -l';
		$r = exec($x);
		if ( isDebug() ) logDebug("dependancy_info 2/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		return $ret;
	}

	static function getEquipments() {
		return eqLogic::byType(__CLASS__);
	}
	static function getLocationsId() {
		$locIds = array();
		foreach (self::getEquipments() as $equ) {
			$locId = $equ->getConfiguration(self::CONF_LOC_ID, self::CFG_LOCATION_DEFAULT_ID);
			if ( !in_array($locId,$locIds) ) $locIds[] = $locId;
		}
		return $locIds;
	}

	static function getLocationId($equ) {
		return $equ->getConfiguration(self::CONF_LOC_ID, self::CFG_LOCATION_DEFAULT_ID);
	}

	static function getLoadingInterval() {
		return intVal(evoGetParam(self::CFG_LOADING_INTERVAL,10));
	}

	static function isCronActive() {
		return /*config::byKey('functionality::cron::enable', 'evohome', 1) == 1 &&*/ getCacheData(self::CACHE_CRON_ACTIF) == "1";
	}

	public static function cron() {
		logDebug('IN>> - cron : ' . cache::byKey('plugin::cron::inprogress')->getValue(0));
		try {
			setCacheData(self::CACHE_CRON_ACTIF, "1", 62);
			$forcage = false;	// for tests only
			if ( $forcage ) {
				self::deactivateIAZReentrance();
				self::razPythonRunning();
			}
			if ( self::isIAZrunning() ) {
				logDebug('<<OUT - cron - reading still running. exit now');
				return;
			}
			$mark = getCacheData(self::CACHE_CRON_TIMER);
			$tsRemain = getCacheRemaining(self::CACHE_CRON_TIMER);
			if ( $forcage || $mark == '' || $tsRemain <= 5 ) {
				if ( !$forcage && evoGetParam(self::CFG_LOADING_SYNC,0) == 1 ) {
					// adjust fine time :
					$interval = self::getLoadingInterval();
					$min = intVal(date("i"));
					if ( $min % $interval != 0 ) {
						// 10 = 0/10/20/30/40/50
						// 15 = 0/15/30/45
						// 20 = 0/20/40
						// 30 = 0/30
						// So, we adjust by checking : currentMin % interval == 0
						logDebug("<<OUT - cron - synchronize interval ($interval) on time (current $min)");
						return;
					}
					logDebug("synchronize time is requested and was right ;)");
				}
				$di = self::dependancy_info();
				if ( $di['state'] != 'ok' ) {
					logDebug('<<OUT - cron - plugin not ready (dependency_info=NOK)');
				} else {
					if ( isDebug() ) {
						// warning level to enforce reporting
						log::add('cron_execution', 'warning', 'Launching getInformationsAllZonesE2 with refresh');
					}
					$td = time();
					foreach ( self::getLocationsId() as $locId ) {
						self::getInformationsAllZonesE2($locId,true);
					}
					$delay = time() - $td;
					$cacheDuration = self::getLoadingInterval()*60 - $delay - 2;
					setCacheData(self::CACHE_CRON_TIMER, "dummy", $cacheDuration);
				}
			} else if ( isDebug() ) {
				logDebug("cron : wait for $tsRemain sec.");
			}
		} catch (Exception $e ) {
			logError('Exception while cron');
		}
		logDebug('<<OUT - cron');
	}

	static function setPythonRunning($name) {
		setCacheData(self::CACHE_PYTHON_RUNNING, $name, 86400);
	}
	static function isPythonRunning() {
		// 0.4.0 - change the way
		/*$nb = exec("ps -ef | grep 'python /var/www/html/plugins/evohome/' | wc -l");
		if ( isDebug() ) logDebug('running python process : nb=' . ($nb/2 - 1));
		return $nb != 2;*/
		$out = "";
		evohome::__execute("ps -ef | grep 'python /var/www/html/plugins/evohome'", null, $out, 0, 1);
		$tmp = explode("www-data ", $out);
		$parts = array();
		foreach ( $tmp as $line ) if ( $line != '' && stripos($line,"grep") === false ) $parts[] = trim($line);
		$nbPython = count($parts);
		if ( isDebug() ) logDebug("running python process : nb=$nbPython");
		return $nbPython != 0;
	}
	static function razPythonRunning() {
		doCacheRemove(self::CACHE_PYTHON_RUNNING);
	}

	static function __execute($cmd, $data, &$stdout, $timeout=0, $depth=0) {
		$td = time();
		$pipes = array();
		$process = proc_open($cmd, array(array('pipe','r'),array('pipe','w'),array('pipe','w')), $pipes);
		if ( !is_resource($process) ) {
			$stdout = "Error while proc_open";
			log::add("cron_execution", "error", $stdout);
			return 1;
		}
		//stream_set_blocking($pipes[0], 0);
		stream_set_blocking($pipes[1], 0);
		stream_set_blocking($pipes[2], 0);
		//fwrite($pipes[0], $stdin);
		fclose($pipes[0]);

		$start = time();
		while ( $timeout == 0 || (time() - $start < $timeout) ) {
			set_time_limit(60);	// any value (0 could be a bad idea)
			$stdout .= stream_get_contents($pipes[1]);
			while ( ($buffer = fgets($pipes[2])) !== false ) {
				if ( $data != null && ($p=strpos($buffer, "Waiting for")) !== false ) {
					self::refreshComponent($data,
						"1" . $data["task"] . " : " . str_replace("\n","",substr($buffer,$p))
						. " (".tsToLocalMS(time()-$td) . ")");
				}
				log::add("cron_execution", 'warning', $buffer);
			}
			$status = proc_get_status($process);
			if (!$status['running']) {
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				return $status['exitcode'];	// should be 0 in normal cases
			}
			usleep(250000);
		}
		$stdout = "Timeout detected (more than $timeout)";
		log::add("cron_execution", 'error', $stdout);
		fclose($pipes[1]);
		fclose($pipes[2]);
		if ( $depth == 1 ) {
			// kill the sh process which is parent of the real python process
			proc_terminate($process, 9);
		} else {
			// 0.4.0 - looking for and destroy the 2 process (parent/child)
			$out = "";
			self::__execute("ps -ef | grep 'python /var/www/html/plugins/evohome'", null, $out, 0, 1);
			$parts = array();
			foreach ( explode("www-data ", $out) as $line ) {
				if ( $line != '' && stripos($line,"grep") === false ) $parts[] = trim($line);
			}
			if ( count($parts) > 0 ) {	// it should be ;)
				foreach ( $parts as $part ) {
					$split = explode(' ', $part);
					log::add("cron_execution", 'error', "kill " . $part);
					exec("kill -9 " . $split[0]);
				}
			}
		}
		return 1;
	}

	static function runPython($prgName, $taskName, $data=null, $parameters=null) {
		$td = time();
		while ( self::isPythonRunning() ) {
			$prevTask = getCacheData(self::CACHE_PYTHON_RUNNING);
			if ( $prevTask === '' ) break;	// 0.4.0 - prevent some cases
			if ( isDebug() ) logDebug("another runPython ($prevTask) is running (a), wait 5sec before launching a new one ($taskName)");
			if ( time() - $td > 250 ) {
				logDebug("runPython : Timeout while waiting another python task ($prevTask) to end");
				return "Timeout while waiting another python task ($prevTask) to end";
			}
			set_time_limit(60);	// 0.4.0 - prevent losing control ; any value (0 could be a bad idea)
			sleep(5);
		}
		$credential = evoGetParam(self::CFG_USER_NAME,'') . ' ' . evoGetParam(self::CFG_PASSWORD,'');
		if ( $credential === ' ' ) {
			logDebug("runPython too early : account is not set yet");
			return "runPython too early : account is not set yet";
		}

		if ( $data != null && $data['task'] != null ) {
			self::refreshComponent($data, "1".$data['task']." : ".self::i18n("démarrage"));
		}
		self::setPythonRunning($taskName);
		$cmd = 'python ' . dirname(__FILE__) . '/../../resources/' . $prgName . ' ' . $credential;

		// -- inject access_token/session from cachedInfosAPI
		$cachedInfosAPI = getCacheData(self::CACHE_INFOS_API);
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
		$cmd .= ' ' . (isDebug() ? '1' : '0');
		// -- inject parameters if any (auto insert locationId before)
		if ( $parameters !== null ) {
			//$cmd .= ' ' . self::getLocationId() . ' ' . $parameters;
			// 0.4.0 : $parameters contains locId in first position
			$cmd .= ' ' . $parameters;
		}
		try {
			logDebug("Launching $prgName");
			//$json = trim(shell_exec($cmd));
			$json = '';
			// timeout=1mn40 max (2mn could be too much against max_execution_time, and in this case, all is lost..)
			// see also : https://stackoverflow.com/questions/6861033/how-to-catch-the-fatal-error-maximum-execution-time-of-30-seconds-exceeded-in-p
			$ret = self::__execute($cmd, $data, $json, 310);
			if ( isDebug() && $ret != 0 ) logDebug("Error while __execute ($ret) : <$json>");
			$json = trim($json);
		} catch (Exception $e) {
			logError("Exception while running python part");
			$json = '';
		}

		// cache API infos (access_token/session)
		$jsonRet = evoJsonDecode($json, 'runPython('. $prgName . ')');
		if ( is_null($jsonRet) ) {
			$jsonRet = $json;	// will be not an array, and will be treated as an error (log report this content)
		} else {
			$updated = false;
			if ( array_key_exists(self::IZ_SESSION_ID_V1,$jsonRet) && array_key_exists(self::IZ_USER_ID_V1,$jsonRet) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V1] = $jsonRet[self::IZ_SESSION_ID_V1];
				$cachedInfosAPI[self::IZ_USER_ID_V1] = $jsonRet[self::IZ_USER_ID_V1];
				$updated = true;
				if ( isDebug() ) logDebug('runPython : session_v1 state=' . array('undefined', 'same', 'new', 'toBeRemoved')[$jsonRet[self::IZ_SESSION_STATE_V1]]);
			}
			if ( array_key_exists(self::IZ_SESSION_ID_V2,$jsonRet) && array_key_exists(self::IZ_SESSION_EXPIRES_V2,$jsonRet) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V2] = $jsonRet[self::IZ_SESSION_ID_V2];
				$cachedInfosAPI[self::IZ_SESSION_EXPIRES_V2] = $jsonRet[self::IZ_SESSION_EXPIRES_V2];
				$updated = true;
				if ( isDebug() ) logDebug('runPython : access_token state=' . array('undefined', 'same', 'new')[$jsonRet[self::IZ_SESSION_STATE_V2]]);
			}
			if ( $updated ) {
				setCacheData(self::CACHE_INFOS_API, $cachedInfosAPI, self::CACHE_IAZ_DURATION);
				// IZ_SESSION_ID_V1 is the first key of the API session infos bloc (even when IZ_SESSION_ID_V2 is present)
				$pos = array_search(self::IZ_SESSION_ID_V1, array_keys($jsonRet));
				if ( is_numeric($pos) ) {
					array_splice($jsonRet, $pos);
				} else {
					// if IZ_SESSION_ID_V1 not here, IZ_SESSION_ID_V2 could be
					$pos = array_search(self::IZ_SESSION_ID_V2, array_keys($jsonRet));
					if ( is_numeric($pos) ) array_splice($jsonRet, $pos);
				}
			} else {
				logDebug('runPython : WARNING : no token nor sessionId received');
			}
		}

		self::razPythonRunning();
		if ( isDebug() ) logDebug("python.$taskName done in " . (time() - $td) . "sec", $jsonRet);
		return $jsonRet;
	}

	/*
	 * Read all Locations attached to the account
	 */
	public static function listLocations($enforce=false) {
		logDebug('IN>> - listLocations');
		$locations = getCacheData(self::CACHE_LIST_LOCATIONS);
		if ( $enforce || $locations == '') {
			$td = time();
			$locations = self::runPython("LocationsInfosE2.py","LocationsInfosE2_$td");
			if ( !is_array($locations)  ) {
				logError('Erreur while LocationsInfosE2 : response was empty or malformed', $locations);
				$locations = null;
			} else if ( !$locations[self::PY_SUCCESS] ) {
				logError('Erreur while LocationsInfosE2', $locations);
				$locations = null;
			} else {
				$locations = $locations['locations'];
				setCacheData(self::CACHE_LIST_LOCATIONS, $locations);
			}
			if ( isDebug() ) logDebug('<<OUT - listLocations from python');
		} else {
			logDebug('<<OUT - listLocations from cache');
		}
		return $locations;
	}

	static function activateIAZReentrance($delay) {
		setCacheData(self::CACHE_IAZ_RUNNING, "true", $delay);
	}
	static function isIAZrunning() {
		return getCacheData(self::CACHE_IAZ_RUNNING) != '';
	}
	static function deactivateIAZReentrance() {
		doCacheRemove(self::CACHE_IAZ_RUNNING);
		doCacheRemove(self::CACHE_STATES);
	}
	static function waitingIAZReentrance($caller) {
		$isRunning = false;
 		while ( self::isIAZrunning() ) {
			if ( isDebug() ) logDebug('waitingIAZReentrance(' . $caller . ') 5sec');
			sleep(5);
			$isRunning = true;
		}
		return $isRunning;
	}

	public static function getInformationsAllZonesE2($locId,$forceRefresh=false, $readSchedule=true, $msgInfo='', $taskIsRunning=false) {
		try {
			$execUnitId = rand(0,10000);
			if ( isDebug() ) logDebug("IN>> - getInformationsAllZonesE2[$execUnitId,$locId]");
			$infosZones = getCacheData(self::CACHE_IAZ,$locId);
			$useCachedData = true;
			$infosZonesBefore = null;
			if ( !is_array($infosZones) || $forceRefresh ) {
				if ( self::waitingIAZReentrance('IAZ-' . $execUnitId) ) {
					$infosZones = getCacheData(self::CACHE_IAZ,$locId);
					// a reading has just been done
				} else {
					// Wait if another python is running
					$tdw = time();
					while ( self::isPythonRunning() ) {
						$prev = getCacheData(self::CACHE_PYTHON_RUNNING);
						if ( $prev === '' ) break;	// 0.4.0 - prevent some cases
						if ( isDebug() ) logDebug("another runPython ($prev) is running (b), wait 5sec before launching a new one (InfosZonesE2_$execUnitId)");
						if ( time() - $tdw > 250 ) {
							logError("Previous call to python ($prev) is blocking other requests");
							return null;
						}
						set_time_limit(60);	// 0.4.0 - prevent losing control ; any value (0 could be a bad idea)
						sleep(5);
					}
					self::activateIAZReentrance(15*60);	// was 120 - now 15mn against cloud freezing
					if ( is_array($infosZones) && $infosZones[self::PY_SUCCESS] ) {
						$infosZonesBefore = $infosZones;
						if ( !$taskIsRunning ) {
							self::refreshAllForLoc($locId,$infosZonesBefore);
						} else {
							self::refreshAllForLoc($locId,$infosZonesBefore, false, $msgInfo, $taskIsRunning);
						}
					}
					$infosZones = self::runPython('InfosZonesE2.py', "InfosZonesE2_$execUnitId", null, $locId . " " . ($readSchedule ? "1" : "0"));
					self::deactivateIAZReentrance();
					if ( !is_array($infosZones) ) {
						logError('Error while getInformationsAllZonesE2 : response was empty of malformed', $infosZones);
						if ( $infosZonesBefore != null ) {
							if ( $taskIsRunning ) {
								self::refreshAllForLoc($locId,$infosZonesBefore);
							} else {
								self::refreshAllForLoc($locId,$infosZonesBefore,false,$msgInfo);
							}
						}
					} else if ( !$infosZones[self::PY_SUCCESS] ) {
						logError('Error while getInformationsAllZonesE2', $infosZones);
						if ( $infosZonesBefore != null ) {
							if ( $taskIsRunning ) {
								self::refreshAllForLoc($locId,$infosZonesBefore);
							} else {
								self::refreshAllForLoc($locId,$infosZonesBefore,false,$msgInfo);
							}
						}
					} else {
						setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION, $locId);
						// refresh if needed
						if ( $readSchedule ) self::refreshAllForLoc($locId,$infosZones,true,$msgInfo,$taskIsRunning);
					}
					$useCachedData = false;
				}
			}
			if ( $useCachedData ) {
				if ( isDebug() ) logDebug('got getInformationsAllZonesE2[' . $execUnitId . '] from cache (rest to live=' . getCacheRemaining(self::CACHE_IAZ,$locId) . ')');
				if ( $infosZonesBefore != null ) $infosZones = $infosZonesBefore;
			}
			if ( isDebug() ) logDebug('<<OUT getInformationsAllZonesE2[' . $execUnitId . ']');
			return $infosZones;
		} catch (Exception $e) {
			logError("Exception while getInformationsAllZonesE2");
			return null;
		}
	}

	public static function getBackColorForTemp($temp,$isOff=false) {
		if ( $temp == null ) return 'lightgray';
		if ( $isOff ) return 'black';
		$X2BG = evoGetParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) == self::CFG_UNIT_CELSIUS ? self::C2BG : self::F2BG;
		foreach ( $X2BG as $ref=>$bgRef ) {
			if ($temp >= $ref) {
				$bg = $bgRef;
				break;
			}
		}
		return $bg;
	}

	static function getComponent($zoneOrLocId) {
		foreach (self::getEquipments() as $equipment) {
			// NB : zoneOrLocId ==> zoneId for a TH component ; locationId for a Console component
			if ( $equipment->getLogicalId() == $zoneOrLocId ) {
				return $equipment;
			}
		}
		return null;
	}
	static function getConsole($locId) {
		return self::getComponent($locId);
	}
	static function getCurrentMode($locId) {
 		$console = self::getConsole($locId);
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

	static function refreshConsole($locId, $msgInfo='', $taskIsRunning=false) {
		self::refreshComponent(array("zoneId"=>$locId, "taskIsRunning"=>$taskIsRunning), $msgInfo);
	}
	static function refreshComponent($data, $msgInfo='') {
		$zoneId = $data['zoneId'];
		if ( isDebug() ) logDebug("IN>> - refreshComponent($zoneId)");
		$comp = self::getComponent($zoneId);
		if ( $comp != null ) {
			$locId = self::getLocationId($comp);
			$consigne = array_key_exists('consigne',$data) ? $data['consigne'] : null;
			$comp->setToHtmlProperties(self::getStates($locId,self::getInformationsAllZonesE2($locId)),self::getSchedule($locId,self::CURRENT_SCHEDULE_ID),$msgInfo,$data['taskIsRunning'],$consigne);
			$comp->iRefreshComponent();
		}
		logDebug('<<OUT - refreshComponent');
	}

	public static function getScheduleSubTitle($id,$locId,$fileId,$scheduleCurrent,$scheduleToShow,$targetOrientation,$zoneId,$typeSchedule,$isEdit=false) {
		$infoDiff = '';
		if ( $fileId == 0) {
			$subTitle = self::i18n("Programmation courante");
		} else {
			$dt = new DateTime();
			$dt->setTimestamp($scheduleToShow['datetime']);
			$subTitle = self::i18n("Programmation de '{0}' créée le {1} à {2}", [self::getFileInfosById($locId,$fileId)['name'], $dt->format('Y-m-d'), $dt->format('H:i:s')]);
			if ( !$isEdit ) {
				$isDiff = false;
				if ( $zoneId == 0 ) {
					$isDiff = json_encode($scheduleToShow['zones']) != json_encode($scheduleCurrent['zones']);
				} else {
					$isDiff = json_encode(extractZone($scheduleToShow,$zoneId)) != json_encode(extractZone($scheduleCurrent,$zoneId));
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
				$ssf = "showScheduleCO($id,'$typeSchedule',$fileId,'$targetOrientation');";
			} else {
				$ssf = "showScheduleTH($locId,$id,$zoneId,'$typeSchedule','$targetOrientation');";
			}
			$lbl = self::i18n($targetOrientation == 'V' ? "Vertical" : "Horizontal");
			$subTitle = "<a class='btn btn-success btn-sm tooltips' onclick=\\\"$ssf\\\">$lbl</a>&nbsp;$subTitle";
		} else {
			$infoDiff = self::i18n("Mode édition");
		}
		$subTitle .= $infoDiff == '' ? '' : "<br/><i>$infoDiff</i>";
		return $subTitle;
	}

	public static function getEquNamesAndId($locId) {
		$table = array();
		foreach (self::getEquipments() as $equipment) {
			if ( self::getLocationid($equipment) == $locId ) {
				$table[$equipment->getLogicalId()] = $equipment->getName();
			}
		}
		if ( isDebug() ) logDebug('getEquNamesAndId : ' . json_encode($table));
		return $table;
	}

	const STATE_UNREAD = 'unread';
	const STATE_CRON_ACTIVE = 'cronActive';
	const STATE_IS_RUNNING = 'isRunning';
	const STATE_LAST_READ = 'lastRead';
	const STATE_IS_ACCURATE = 'isAccurate';
	const STATE_CNX_LOST = 'cnxLost';
	static function getStates($locId,$infosZones=null) {
		$states = array();
		$states[self::STATE_UNREAD] = (self::CACHE_IAZ_DURATION - getCacheRemaining(self::CACHE_IAZ.$locId)) > self::getLoadingInterval()*60;
		$states[self::STATE_CRON_ACTIVE] = self::isCronActive();
		$states[self::STATE_IS_RUNNING] = self::isIAZrunning();
		$states[self::STATE_LAST_READ] = !is_array($infosZones) || !array_key_exists(self::IZ_TIMESTAMP,$infosZones) ? 0 : tsToLocalDateTime($infosZones[self::IZ_TIMESTAMP]);
		// apiV1 available == accurate values available
		$states[self::STATE_IS_ACCURATE] = !is_array($infosZones) || !array_key_exists(self::IZ_API_V1,$infosZones) ? false : $infosZones[self::IZ_API_V1];
		$states[self::STATE_CNX_LOST] = !is_array($infosZones) || !array_key_exists(self::IZ_GATEWAY_CNX_LOST,$infosZones) ? '' : gmtToLocalDateHMS($infosZones[self::IZ_GATEWAY_CNX_LOST]);
		if ( isDebug() ) logDebug("getStates : " . json_encode($states));
		return $states;
	}

	static function refreshAllForLoc($locId,$infosZones,$inject=false,$msgInfo='',$taskIsRunning=false) {
		logDebug("IN>> - refreshAllForLoc");
		$states = self::getStates($locId,$infosZones);
		$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
		foreach (self::getEquipments() as $equipment) {
			if ( self::getLocationId($equipment) == $locId ) {
				// NB : $taskIsRunning should be set on console only
				$equipment->setToHtmlProperties($states,$scheduleCurrent,$msgInfo,$taskIsRunning && $equipment->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE);
				$msgInfo = '';	// set only on the first equipment
				$equipment->iRefreshComponent($infosZones,$inject);
			}
		}
		logDebug("<<OUT - refreshAllForLoc");
	}

	static function fillSetConsigneData($cmd,$zoneId,$minHeat,$maxHeat, $doSave=false) {
		if ( isDebug() ) logDebug("adjust min=$minHeat/max=$maxHeat of the SET_CONSIGNE command on the zone=$zoneId");
		// 0.4.1 - 1st choice to go back to the scheduled value
		$list = "auto#$zoneId#0#0#null|" . self::i18n("Annulation (retour à la valeur programmée)") . ";";
		// 0.9 is the supposed value for the °F... (0.5 * 9/5)
		$step = evoGetParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) == self::CFG_UNIT_CELSIUS ? 0.5 : 0.9;
		for( $t=$minHeat ; $t<=$maxHeat ; $t+=$step ) {
			// auto means the callback function must check availability of service (presence mode / api available)
			$list .= "auto#$zoneId#$t#$t#null|$t" . ($t < $maxHeat ? ';' : '');
		}
		$cmd->setConfiguration('listValue', $list);
		$cmd->setConfiguration('minHeat', $minHeat);
		$cmd->setConfiguration('maxHeat', $maxHeat);
		if ( $doSave )$cmd->save();
	}

	/*********************** Méthodes d'instance **************************/

	function setAllowedSystemModes($asmList) {
		$allowedModes = array();
		foreach ($asmList as $asm) {
			if ( ($code = self::getModeFromHName($asm)) !== null ) $allowedModes[] = $code;
		}
		$this->setConfiguration(self::CONF_ALLOWED_SYSTEM_MODE, $allowedModes);
	}

	function createOrUpdateCmd($order, $logicalId, $name, $type, $subType, $isVisible, $isHistorized) {
		$cmd = $this->getCmd(null, $logicalId);
		$created = false;
		if (is_object($cmd) && $cmd->getSubType() != $subType) {
			logDebug('0.2.1 : createOrUpdateCmd replace MODE/RESTORE cmd');
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
			$asmCodes = $this->getConfiguration(self::CONF_ALLOWED_SYSTEM_MODE);
			$list = '';
			foreach ($asmCodes as $code) {
				if ( $list != '' ) $list .= ";";
				$list .= $code . '|' . self::getModeName($code);
			}
			if ( $list == '' ) $list = '0|Unavailable';
			$cmd->setConfiguration('listValue', $list);
		} else if ( $logicalId == self::CMD_SET_CONSIGNE_ID ) {
			// 0.4.1 - become a default setting, before reading real values of min/max inside "injectInformationsFromZone"
			$zoneId = $this->getLogicalId();
			self::fillSetConsigneData($cmd,$zoneId,self::adjustbyUnit(5,"C"),self::adjustbyUnit(25,"C"));
		}
		// 0.4.2 - les infos précédentes n'étaient pas compatible "appli mobile"
		if ( $logicalId == self::CMD_TEMPERATURE_ID ) {
			//$cmd->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
			$cmd->setDisplay('generic_type', 'TEMPERATURE');
			$cmd->setGeneric_type('TEMPERATURE');
			$cmd->setUnite("°");
		} else if ( $logicalId == self::CMD_CONSIGNE_ID || $logicalId == self::CMD_SCH_CONSIGNE_ID ) {
			//$cmd->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
			$cmd->setDisplay('generic_type', 'GENERIC_INFO');
			$cmd->setGeneric_type('GENERIC_INFO');
			$cmd->setUnite("°");
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

		if ( /*$created &&*/ $logicalId == self::CMD_RESTORE ) {
			$this->updateRestoreList(self::getLocationId($this));
		}

		return $created;
	}

	public function preUpdate() {
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
			if ( is_object($cmd) ) {
				$v = $cmd->getIsVisible() ? '1' : '0';
				if ( isDebug() ) logDebug("preUpdate : visible STAT = $v");
				setCacheData(self::CACHE_STAT_PREV_VISIBLE, $v);
			}
		}

		return true;
	}

	public function preRemove() {
	}

	function deleteCmd($sCmdList) {
		foreach ( $sCmdList as $sCmd ) {
			$cmd = $this->getCmd(null, $sCmd);
			if (is_object($cmd)) $cmd->remove();
		}
	}

	public function postSave() {
		logDebug("postSave");
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			logDebug('postSave - create Console widget');
			$this->deleteCmd([self::CMD_TEMPERATURE_ID, self::CMD_CONSIGNE_ID, self::CMD_SCH_CONSIGNE_ID, self::CMD_CONSIGNE_TYPE_ID, self::CMD_SET_CONSIGNE_ID]);
			$created = $this->createOrUpdateCmd(0, self::CMD_STATE, 'Etat', 'info', 'string', 1, 0);
			$this->createOrUpdateCmd(1, self::CMD_SET_MODE, 'Réglage mode', 'action', 'select', 1, 0);
			$this->createOrUpdateCmd(2, self::CMD_SAVE, 'Sauvegarder', 'action', 'other', 1, 0);
			$this->createOrUpdateCmd(3, self::CMD_RESTORE, 'Restaure', 'action', 'select', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_DELETE, 'Supprimer', 'action', 'other', 1, 0);
			$this->createOrUpdateCmd(5, self::CMD_STATISTICS_ID, "Statistiques", 'info', 'numeric', 1, 0);
		}
		else if ($this->getLogicalId() > 0) {
			logDebug('postSave - create TH widget');
			$this->deleteCmd([self::CMD_STATE, self::CMD_SET_MODE, self::CMD_SAVE, self::CMD_RESTORE, self::CMD_DELETE, self::CMD_STATISTICS_ID]);
			$created = $this->createOrUpdateCmd(0, self::CMD_TEMPERATURE_ID, 'Température', 'info', 'numeric', 1, 1);
			$this->createOrUpdateCmd(1, self::CMD_CONSIGNE_ID, 'Consigne', 'info', 'numeric', 1, 1);
			$this->createOrUpdateCmd(2, self::CMD_SCH_CONSIGNE_ID, 'Consigne programmée', 'info', 'numeric', 0, 1);
			$this->createOrUpdateCmd(3, self::CMD_CONSIGNE_TYPE_ID, 'Type Consigne', 'info', 'string', 0, 0);	// 0.4.1 - no display usage
			$this->createOrUpdateCmd(4, self::CMD_SET_CONSIGNE_ID, 'Set Consigne', 'action', 'select', 1, 0);
		}

		$infosZones = self::getInformationsAllZonesE2(self::getLocationId($this));
		$this->injectInformationsFromZone($infosZones);

		if ( isDebug() ) logDebug('<<OUT - postSave(' . $this->getLogicalId() . ')'); 

		return true;
	}

	public function postRemove() {
	}

	function updateRestoreList($locId) {
		$hns = self::getHebdoNames($locId);
		$listValue = '';
		$idx = 1;
		foreach ( $hns as $hn ) {
			$listValue .= $hn['id'] . '|' . $hn['name'];
			if ( $idx++ < count($hns) ) $listValue .= ';';
		}
		$cmd = $this->getCmd(null, self::CMD_RESTORE);
		$cmd->setConfiguration('listValue', $listValue);
		$cmd->save();
	}

	static function C2F($t,$delta=false) {
		return $t * 9/5 + (!$delta ? 32 : 0);
	}
	static function F2C($t,$delta=false) {
		return ($t - (!$delta ? 32 :0)) * 5/9;
	}
	static function adjustByUnit($temp, $unitsFrom, $delta=false) {
		if ( $temp == null ) return null;
		$unitsFrom = substr($unitsFrom,0,1);
		if ( $unitsFrom == evoGetParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) ) return $temp;
		// >> Celsius > Fahrenheit
		if ( $unitsFrom == self::CFG_UNIT_CELSIUS ) return self::C2F($temp,$delta);
		// >> Fahrenheit > Celsius
		if ( $unitsFrom == self::CFG_UNIT_FAHRENHEIT ) return self::F2C($temp,$delta);
	}
	// revert conversion : used by Set Consigne, as this function receive converted values
	static function revertAdjustByUnit($temp, $unitsFrom) {
		if ( $temp == null ) return null;
		$unitsFrom = substr($unitsFrom,0,1);
		if ( $unitsFrom == evoGetParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) ) return $temp;
		// >> Fahrenheit > Celsius
		if ( $unitsFrom == self::CFG_UNIT_CELSIUS ) return self::F2C($temp);
		// >> Celsius > Fahrenheit
		if ( $unitsFrom == self::CFG_UNIT_FAHRENHEIT ) return self::C2F($temp);
	}

	// $razMinMax = true for a manual command (RUF)
	function injectInformationsFromZone($infosZones, $razMinMax=false) {
		if ( !is_array($infosZones) ) {
			return;
		}
		$zoneId = $this->getLogicalId();
		if ( isDebug() ) logDebug("IN>> - injectInformationsFromZone on zone $zoneId");
		if ( $zoneId == self::ID_NO_ZONE ) {
			logError("<<OUT - injectInformationsFromZone - zone undefined ; nothing to do");
			return;
		}
		if ( $this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE ) {
			$tmp = $this->getCmd(null,self::CMD_STATE);
			if(is_object($tmp)){
				$etat = $infosZones['currentMode']
					. ";" . ($infosZones['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
					. ";" . $infosZones['untilMode'];
				$tmp->event($etat);
			}

		} else if ( $zoneId > 0) {	// this check should be useless
			$infosZone = extractZone($infosZones,$zoneId);
			if ( $infosZone == null ) {
				logError("<<OUT - injectInformationsFromZone - no data found on zone $zoneId");
				return;
			}
			$temp = self::adjustByUnit($infosZone['temperature'],$infosZone['units']);
			$tmp = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
			if (is_object($tmp) ) {
				$prev = $tmp->execCmd();
				$tmp->event($temp);
			} else {
				$prev = 0;
			}
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$tmp->event(self::adjustByUnit($infosZone['setPoint'],$infosZone['units']));
			}
			$tmp = $this->getCmd(null,self::CMD_SCH_CONSIGNE_ID);
			if (is_object($tmp) ) {
				$consigneScheduled = self::getConsigneScheduledForZone($infosZone)['TH'];
				$tmp->event(self::adjustByUnit($consigneScheduled,$infosZone['units']));
			}
			$spc = $infosZone['setPointCapabilities'];
			// 0.4.1 - now, these 3 values are "unit adjusted"
			$minHeat = self::adjustByUnit($spc['minHeat'],$infosZone['units']);
			$maxHeat = self::adjustByUnit($spc['maxHeat'],$infosZone['units']);
			$resolution = self::adjustByUnit($spc['resolution'],$infosZone['units'],true);
			$consigneInfo = $infosZone['status']
				. ";" . $infosZone['until']
				. ";" . $infosZone['units']
				. ";$resolution;$minHeat;$maxHeat;$prev"
				. ";" . (array_key_exists('battLow',$infosZone) ? $infosZone['battLow'] : '')
				. ";" . (array_key_exists('cnxLost',$infosZone) ? $infosZone['cnxLost'] : '');
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			if (is_object($tmp) ) {
				$tmp->event($consigneInfo);
			}
			// 0.4.1 - auto-adjust the list of available values for the SET_CONSIGNE action :
			$tmp = $this->getCmd(null,self::CMD_SET_CONSIGNE_ID);
			if (is_object($tmp) && (intval($tmp->getConfiguration('minHeat')) != $minHeat || intval($tmp->getConfiguration('maxHeat')) != $maxHeat) ) {
				self::fillSetConsigneData($tmp,$zoneId,$minHeat,$maxHeat,true);
			}

			if ( isDebug() ) {
				logDebug("zone$zoneId=" . $infosZone['name'] . " : temp = " . $infosZone['temperature'] . ", consigne = " . $infosZone['setPoint'] . ", type = $consigneInfo");
			}
		}
		logDebug("<<OUT - injectInformationsFromZone");
	}

	private function applyRounding($temperatureNative) {
		$valRound = round($temperatureNative*100)/100;
		list($entier, $decRound) = explode('.', number_format($valRound,2));
		switch ( evoGetParam(self::CFG_ACCURACY,1) ) {
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
		$infosZone = extractZone($currentSchedule, $zoneId);
		return $infosZone == null ? null : self::getConsigneScheduledForZone($infosZone);
	}

	function getConsigneScheduledForZone($infosZone) {
		if ( count($infosZone['schedule']['DailySchedules']) == 0 ) return null;
		$currentDay = strftime('%u', time())-1;
		$currentTime = strftime('%H:%M', time());
		$dsSunday = $infosZone['schedule']['DailySchedules'][6];
		$lastTemp = $dsSunday['Switchpoints'][count($dsSunday['Switchpoints'])-1]['heatSetpoint'];
		foreach ( $infosZone['schedule']['DailySchedules'] as $ds ) {
			$mark = 0;
			if ( $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00' ) {
				array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'heatSetpoint'=>$lastTemp));
			}
			$nbPoints = count($ds['Switchpoints']);
			for ( $i=1 ; $i <= $nbPoints ; $i++) {
				$sp = $ds['Switchpoints'][$i-1];
				$hm = substr($sp['TimeOfDay'],0,5);
				if ( $ds['DayOfWeek'] == $currentDay ) {
					if ( $i == $nbPoints ) {
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
				if ( $mark == 1 ) {
					$until = $i == $nbPoints ? "00:00" : substr($ds['Switchpoints'][$i]['TimeOfDay'],0,5);
					//logDebug("zoneId=".$infosZone['zoneId']. ", lastTemp=$lastTemp, TimeOfDay=$until");
					return array('TH'=>$lastTemp, 'UNTIL'=>$until);
				}
			}
		}
		return null;
	}

 	public function toHtmlConsole($pVersion,$version,$replace,$scheduleCurrent) {
		$cmdEtat = $this->getCmd(null,self::CMD_STATE);
		if ( !is_object($cmdEtat) ) return;

		$replace_action = $this->preToHtml($pVersion);
		$locId = self::getLocationId($this);
		$replace_action['#locId#'] = $locId;
		$replace_action['#argLocId#'] = self::ARG_LOC_ID;
		$replace_action['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

		$_etat = is_object($cmdEtat) ? $cmdEtat->execCmd() : '';
		// "Auto";1 / "AutoWithEco";1/0;H / Away;1/0;D / DayOff;1/0;D / Custom;1/0;D / HeatingOff;1
		// with 1=True ; 0=False ; is the permanentMonde flag
		// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
		$aEtat = explode(';',$_etat);
		$etatImg = 'empty.svg';
		$etatCode = self::getModeFromHName($aEtat[0]);
		switch ( $etatCode ) {
			case self::CODE_MODE_AUTO: 		$etatImg = 'i_calendar.svg'; break;
			case self::CODE_MODE_ECO:		$etatImg = 'i_economy.svg'; break;
			case self::CODE_MODE_AWAY:		$etatImg = 'i_away.svg'; break;
			case self::CODE_MODE_DAYOFF:	$etatImg = 'i_dayoff.svg'; break;
			case self::CODE_MODE_CUSTOM:	$etatImg = 'i_custom.svg'; break;
			case self::CODE_MODE_OFF:		$etatImg = 'i_off.svg'; break;
			default:						$etatImg = 'empty.svg'; break;
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
			$replace_action['#etatUntil#'] = $etatCode == self::CODE_MODE_ECO ? gmtToLocalHM($aEtat[2]) : gmtToLocalDate($aEtat[2]);
			$replace_action['#etatUntilFull#'] = $aEtat[2];
			$replace_action['#etatUntilDisplay#'] = 'inline';
		}
		else {
			$replace_action['#etatUntilImg#'] = 'empty.svg';	// dummy
			$replace_action['#etatUntilDisplay#'] = 'none';
		}

		$selectStyle = ' selected style="background-color:green !important;color:white !important;"';
		$unselectStyle = ' style="background-color:#efefef !important;color:black !important;"';
		$statCmd = $this->getCmd(null,self::CMD_STATISTICS_ID);
		$replace_action['#statDisplay#'] = (is_object($statCmd) && $statCmd->getIsVisible()) ? "block" : "none";
		if ( $replace_action['#statDisplay#'] == 'block') {
			$statScope = !is_object($statCmd) ? 1 : $statCmd->execCmd();
			if ( $statScope === '' ) $statScope = 0;
			$replace_action['#statTitle#'] = self::i18n('Statistiques');
			$replace_action['#statScope0#'] = $statScope == 0 ? $selectStyle : $unselectStyle;
			$replace_action['#statScopeTitle0#'] = self::i18n('Désactivé');
			$replace_action['#statScope1#'] = $statScope == 1 ? $selectStyle : $unselectStyle;
			$replace_action['#statScopeTitle1#'] = self::i18n('Jour');
			$replace_action['#statScope2#'] = $statScope == 2 ? $selectStyle : $unselectStyle;
			$replace_action['#statScopeTitle2#'] = self::i18n('Semaine');
			$replace_action['#statScope3#'] = $statScope == 3 ? $selectStyle : $unselectStyle;
			$replace_action['#statScopeTitle3#'] = self::i18n('Mois');
		}

		$options = '';
		$scheduleFileId = evoGetParam(self::iCFG_SCHEDULE_ID, 0, $locId);
		$jsScheduleFileId = 0;
		foreach ( self::getHebdoNames($locId) as $hn) {
			$options .= '<option value="' . $hn['id'] . '"';
			$options .= ( $hn['id'] == 0 || $hn['id'] == $scheduleFileId ) ? $selectStyle : $unselectStyle;
			if ( $hn['id'] == $scheduleFileId ) {
				$jsScheduleFileId = $scheduleFileId;
			}
			$options .= '>' . $hn['name'] . '</option>';
		}
		$replace_action['#scheduleFileId#'] = $jsScheduleFileId;
		$replace_action['#scheduleOptions#'] = $options;

		// indicateur schedule modifié
		$saveColor = 'white';
		$canRestoreCurrent = 0;
		$saveTitle = self::i18n("Sauvegarde la programmation courante");
		$scheduleDelta = "0";
		if ( $scheduleFileId != null ) {
			$scheduleSaved = self::getSchedule($locId,$scheduleFileId);
			if ( $scheduleSaved != null && $scheduleCurrent != null ) {
				$_scheduleSaved = json_encode($scheduleSaved['zones']);
				$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
				if ( $_scheduleSaved != $_scheduleCurrent ) {
					$saveColor = 'orange';
					$canRestoreCurrent = 1;
					$scheduleDelta = "1";
					/*if ( isDebug() ) {
						logDebug("_scheduleSaved = " . $_scheduleSaved);
						logDebug("_scheduleCurrent = " . $_scheduleCurrent);
					}*/
					$saveTitle .= ' (' . self::i18n("différente de la dernière programmation restaurée ou éditée") . ')';
				}
			}
		}
		//logDebug("***** console scheduleDelta = $scheduleDelta");
		setCacheData(self::CACHE_SCHEDULE_DELTA, $scheduleDelta, 2);
		$replace_action['#title.save#'] = $saveTitle;
		$replace_action['#canRestoreCurrent#'] = $canRestoreCurrent;
		$replace_action['#isAdmin#'] = isAdmin();
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
		// codes mode allowed
		$replace_action['#codesAllowed#'] = json_encode($this->getConfiguration(self::CONF_ALLOWED_SYSTEM_MODE));
		// codes mode
		$replace_action['#codeAuto#'] = self::CODE_MODE_AUTO;
		$replace_action['#codeEco#'] = self::CODE_MODE_ECO;
		$replace_action['#codeAway#'] = self::CODE_MODE_AWAY;
		$replace_action['#codeDayOff#'] = self::CODE_MODE_DAYOFF;
		$replace_action['#codeCustom#'] = self::CODE_MODE_CUSTOM;
		$replace_action['#codeOff#'] = self::CODE_MODE_OFF;
		$replace_action['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_action['#displaySetModePopup#'] = evoGetParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODE_CONSOLE) == self::CFG_SHOWING_MODE_POPUP ? "visible" : "none";
		$replace_action['#displaySetModeConsole#'] = evoGetParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODE_CONSOLE) == self::CFG_SHOWING_MODE_CONSOLE ? "1" : "0";
		$replace_action['#evoDefaultShowingScheduleMode#'] = evoGetParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

		// i18n
		$rbs = evoGetParam(self::CFG_REFRESH_BEFORE_SAVE,0);
		$msg = array('scheduleTitle'=>"Programmes hebdo.",
			'title.setMode'=>"Réglage du mode de présence",
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
		$replace_action["#modeAuto#"] = self::getModeName(self::CODE_MODE_AUTO);
		$replace_action["#modeEco#"] = self::getModeName(self::CODE_MODE_ECO);
		$replace_action["#modeAway#"] = self::getModeName(self::CODE_MODE_AWAY);
		$replace_action["#modeDayOff#"] = self::getModeName(self::CODE_MODE_DAYOFF);
		$replace_action["#modeCustom#"] = self::getModeName(self::CODE_MODE_CUSTOM);
		$replace_action["#modeOff#"] = self::getModeName(self::CODE_MODE_OFF);

		$replace['#consoleContent#'] = template_replace($replace_action, getTemplate('core', $version, 'console_content', __CLASS__));
		$replace['#temperatureContent#'] = '';

		$replace['#batteryImgDisplay#'] = 'none';
		$replace['#batteryImg#'] = 'empty.svg';
		$replace['#batteryImgTitle#'] = '';

		return $replace;
	}

	public function toHtmlTh($pVersion,$version,$replace,$scheduleCurrent,$states,$forcedConsigne) {
		$zoneId = $this->getLogicalId();

		$replace_temp = $this->preToHtml($pVersion);
		$locId = self::getLocationId($this);
		$replace_temp['#locId#'] = $locId;
		$replace_temp['#argLocId#'] = self::ARG_LOC_ID;
		$replace_temp['#zoneId#'] = $zoneId;
		$replace_temp['#fileId#'] = evoGetParam(self::iCFG_SCHEDULE_ID, 0, $locId);

		// *** TEMPERATURE
		$replace_temp['#etatImg#'] = 'empty.svg';	// dummy
		$replace_temp['#etatUntilImg#'] = 'empty.svg';	// dummy

		$cmdTemperature = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
		$cmdId = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
		$replace_temp['#temperatureId#'] = $cmdId;
		$replace_temp['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "block" : "none";
		$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
		if ( $temperatureNative == null ) {
			$temperature = 0;
			$replace_temp['#temperature#'] = '';
			$replace_temp['#temperatureDisplay2#'] = 'none';
		} else {
			$temperature = self::applyRounding($temperatureNative);
			$replace_temp['#temperature#'] = $temperature . '°';
			$replace_temp['#temperatureDisplay2#'] = 'table-cell';
		}

		// *** CONSIGNE
		$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
		$replace_temp['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
		$replace_temp['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "block" : "none";
		$consigne = $forcedConsigne != null ? $forcedConsigne : (is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0);
		$currentMode = self::getCurrentMode($locId);
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

		switch ( $temperatureNative == null ? 0 : ($temperatureNative < $consigne ? 2 : 1) ) {
			case 0 :
			$replace_temp['#temperatureImg#'] = 'batt-hs.png';
			$replace_temp['#temperatureImgStyle#'] = 'height:36px;width:36px;margin-top:2px;';
			$replace_temp['#temperatureDeltaDisplay#'] = 'none;';
			break;

			case 1:
			$replace_temp['#temperatureImg#'] = 'check-mark-md.png';
			$replace_temp['#temperatureImgStyle#'] = 'height:20px;';
			$replace_temp['#temperatureDeltaDisplay#'] = 'block';
			break;

			case 2:
			$replace_temp['#temperatureImg#'] = 'chauffage_on.gif';
			$replace_temp['#temperatureImgStyle#'] = 'height:15px;width:15px;';
			$replace_temp['#temperatureDeltaDisplay#'] = 'block';
		}

		$consigneTypeImg = null;
		$cmdConsigneInfos = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
		if ( is_object($cmdConsigneInfos) ) {	// 0.4.1 - remove the check of isVisible (useless and side effects in case of)
			$consigneInfos = explode(';', $cmdConsigneInfos->execCmd());
			# $consigneInfos[0] = FollowSchedule / PermanentOverride / TemporaryOverride
			# $consigneInfos[1] = 2018-01-28T23:00:00Z / <empty>
			# $consigneInfos[2] = Celsius/Fahrenheit
			# $consigneInfos[3] = 0.5 (step)	)
			# $consigneInfos[4] = 5 (min)  == self->getConfiguration('minHeat')		) 0.4.1 - these 3 values are now "adjusted by unit"
			# $consigneInfos[5] = 25 (max) == self->getConfiguration('maxHeat')		)
			# $consigneInfos[6] = delta previous measure (0/-1:+1)
			# $consigneInfos[7] = timeBattLow / <empty>
			# $consigneInfos[8] = timeCnxLost / <empty>
			$consignePair = self::getConsigneScheduled($scheduleCurrent,$zoneId);
			$consigneScheduled = $consignePair == null ? null : self::adjustByUnit($consignePair['TH'],$consigneInfos[2]);	// 0.4.1 - adjust added
			$sConsigneScheduled = $consigneScheduled == null ? ("[".self::i18n("non déterminé")."]") : $consigneScheduled;
			$consigneTip = '';
			$consigneTypeUntil = '';
			$consigneTypeUntilFull = '';
			$adjustAvailable = true;
			if ( $isEco ) {
				$consigneTypeUntilFull = self::i18n("Mode économie (remplace {0}°)", $sConsigneScheduled);
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
				$consigneTypeUntilFull = self::i18n("Consigne forcée à {0}° au lieu de {1}°", [$consigne, $sConsigneScheduled]);
				$consigneTypeImg = 'i_off_white.png';
				$adjustAvailable = false;
			} else if ( $isAway ) {
				$consigneTypeUntilFull = self::i18n("Mode inoccupé (remplace {0}°)", $sConsigneScheduled);
				$consigneTypeImg = 'i_away_white.png';
				$adjustAvailable = false;		// unavailable when AWAY mode
			} else if ( !$isEco &&!$isDayOff && !$isCustom && $consigneInfos[0] == self::FollowSchedule ) {
				if ( $consigneScheduled != null && $consigne != null ) {
					// SetPoint was auto-adjusted, let's see :
					if ( $consigne < $consigneScheduled ) {
						$minHeat = $consigneInfos[4];
						//$minHeat = self->getConfiguration('minHeat');
						if ( $consigne == $minHeat ) {
							$consigneTypeUntilFull = self::i18n("Fenêtre ouverte détectée");
							$consigneTypeImg = 'o-window.png" style="height:15px;';
						} else {
							$consigneTypeUntilFull = self::i18n("Optimisation active : consigne inférieure à suivre active (remplace {0}°)", $consigneScheduled);
							$consigneTypeImg = 'down green.svg';
						}
					} else if ( $consigne > $consigneScheduled ) {
						$consigneTypeUntilFull = self::i18n("Optimisation active : consigne supérieure à suivre active (remplace {0}°)", $consigneScheduled);
						$consigneTypeImg = 'up red.svg';
					}
				}
			} else if ( $consigneInfos[0] == self::TemporaryOverride ) {
				$consigneTip = '';
				$consigneTypeImg = 'temp-override.svg';
				// example : $consigneInfos[1] = "2018-01-28T23:00:00Z"
				$time = gmtToLocalHM($consigneInfos[1]);
				$consigneTypeUntil = $time;
				$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}° jusqu'à {1}", [$sConsigneScheduled, $time]);
			} else if ( $consigneInfos[0] == self::PermanentOverride ) {
				$consigneTypeImg = 'override-active.png';
				$consigneTypeUntilFull = self::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			}
			$replace_temp['#consigneTypeUntil#'] = $consigneTypeUntil;
			$replace_temp['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
			$replace_temp['#consigneTip#'] = $consigneTip;
			//  additional infos
			$replace_temp['#currentConsigne#'] = $consigneScheduled == null ? 0 : $consigneScheduled;
			$replace_temp['#currentConsigneUntil#'] = $consignePair == null ? '' : $consignePair['UNTIL'];
		}

		$cmdSetConsigne = $this->getCmd(null,self::CMD_SET_CONSIGNE_ID);
		if ( is_object($cmdSetConsigne) && !$cmdSetConsigne->getIsVisible() ) {
			$replace_temp['#setConsigneDisplayV1#'] = "none";
			$replace_temp['#setConsigneDisplayV2#'] = "none";
		} else {
			$typeAdjust = evoGetParam(self::CFG_HP_SETTING_MODES,self::CFG_HP_SETTING_MODE_INTEGRATED) == self::CFG_HP_SETTING_MODE_INTEGRATED ? 1 : 2;
			$replace_temp['#setConsigneDisplayV1#'] = $typeAdjust == 1 ? "table-cell" : "none";
			$replace_temp['#setConsigneDisplayV2#'] = $typeAdjust == 2 ? "table-cell" : "none";
			// adjust temp infos
			$replace_temp['#adjustAvailable#'] = $adjustAvailable ? 'true' : 'false';
			$replace_temp['#msgAdjustConsigneUnavailable#'] = self::i18n("Le mode actif ne permet pas d'ajuster les consignes");
			$replace_temp['#msgEnforceConsigne#'] = self::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			$replace_temp['#adjustStep#'] = $consigneInfos[3];
			$replace_temp['#canReset#'] = $consigneScheduled == null || $consigneScheduled == $consigne ? 0 : 1;
			$replace_temp['#backScheduleTitle#'] = $consigneScheduled == null ? '' : self::i18n('Retour à la valeur programmée de {0}°', $consigneScheduled);
		}
		$replace_temp['#adjustLow#'] = $consigneInfos[4];
		$replace_temp['#adjustHigh#'] = $consigneInfos[5];
		$replace_temp['#consigneTypeImg#'] = $consigneTypeImg == null ? 'empty.svg' : $consigneTypeImg;
		$replace_temp['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'inline-block';
		// arguments names
		$replace_temp['#argFileId#'] = self::ARG_FILE_ID;
		$replace_temp['#argZoneId#'] = self::ARG_ZONE_ID;
		// codes
		$replace_temp['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_temp['#evoDefaultShowingScheduleMode#'] = evoGetParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

		// Info Batterie (A)
		$replace_temp['#temperatureImgTitle#'] = $consigneInfos[8] == '' && $temperatureNative != null ? '' : ($temperatureNative == null  ? self::i18n("Connexion perdue (date inconnue), batterie HS") : self::i18n("Connexion perdue depuis {0}, batterie HS", gmtToLocalDateHMS($consigneInfos[8])));

		// fix 7 - error reported by TLoo - 2019-02-09 - btw, plugin without Console is uncomplete ;)
		$console = self::getConsole($locId);
		if ( $console != null ) {
			$cmdStatistics = $console->getCmd(null,self::CMD_STATISTICS_ID);
			$timeWindow = !is_object($cmdStatistics) || !$cmdStatistics->getIsVisible() || $cmdId == '' ? 0 : max($cmdStatistics->execCmd(), 0);
		}else {
			$timeWindow = 0;
		}
		$replace_temp['#minMaxDisplay#'] = $timeWindow == 0 ? "none" : "block";
		if ( $timeWindow == 0 ) {
			$replace_temp['#statDelta#'] = '&nbsp;';
			$replace_temp['#statDeltaTitle#'] = '';
			$replace_temp['#deltaDisplay#'] = 'none';
			$replace_temp['#deltaImg#'] = 'empty.svg';
		} else {
			$temperature = self::applyRounding($temperatureNative);
			$replace_temp['#statDelta#'] = $temperature == 0 ? '' : ($temperature > $consigne ? '+' : '') . round($temperature - $consigne,2) . '°';
			$replace_temp['#statDeltaTitle#'] = self::i18n("Ecart consigne");
			$delta = $consigneInfos[6] == 0 ? 0 : round($temperature - self::applyRounding($consigneInfos[6]),2);
			$replace_temp['#deltaDisplay#'] = $delta == 0 ? "none" : "inline-block";
			$replace_temp['#deltaValue#'] = ($delta > 0 ? "+" : "") . self::i18n("{0}° depuis la précédente mesure", $delta);
			$replace_temp['#deltaImg#'] = $delta > 0 ? 'green-up-anim.gif' : 'red-down-anim.gif';
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
				$replace_temp['#statRazTime#'] = $timeWindow == 1 ? tsToLocalHMS(strtotime($results[0]['datetime'])) : $results[0]['datetime'];

				$replace_temp['#statLastReadTitle#'] = self::i18n("dernière lecture");
				$replace_temp['#statLastRead#'] = tsToLocalHMS(strtotime($results[count($results)-1]['datetime']));

				$replace_temp['#statMaxTitle#'] = self::i18n("max");
				$replace_temp['#statThMax#'] = self::applyRounding($max) . '°';
				$replace_temp['#statWhenMax#'] = $timeWindow == 1 ? tsToLocalHMS($dMax) : $sDateMax;
				$replace_temp['#statWhenMinus1#'] = $dMaxMinus1 == 0 ? '(' . self::i18n("pas encore") . ')' : tsToAbsoluteHM($dMaxMinus1);

				$replace_temp['#statAvgTitle#'] = self::i18n("moy");
				$replace_temp['#statThAvg#'] = self::applyRounding($avg) . '°';
				$replace_temp['#statNbPoints#'] = self::i18n("{0} points", count($results));

				$replace_temp['#statMinTitle#'] = self::i18n("min");
				$replace_temp['#statThMin#'] = self::applyRounding($min) . '°';
				$replace_temp['#statWhenMin#'] = $timeWindow == 1 ? tsToLocalHMS($dMin) : $sDateMin;
				$replace_temp['#statWhenPlus1#'] = $dMinPlus1 == 0 ? '(' . self::i18n("pas encore") . ')' : tsToAbsoluteHM($dMinPlus1);
			}
		}

		foreach ($this->getCmd('action') as $cmd) {
			$replace_temp['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		$replace['#consoleContent#'] = '';
		$replace['#temperatureContent#'] = template_replace($replace_temp, getTemplate('core', $version, 'temperature_content', __CLASS__));

		// Battery info (B)
		$replace['#batteryImgDisplay#'] = $consigneInfos[7] . $consigneInfos[8] === '' && $temperatureNative != null ? 'none' : 'flex';
		$replace['#batteryImg#'] = $consigneInfos[8] != '' || $temperatureNative == null ? 'batt-hs-small.png' : ($consigneInfos[7] != '' ? 'batt-low-small.png' : 'empty.svg');
		$replace['#batteryImgTitle#'] = $consigneInfos[8] != '' ? self::i18n("Batterie HS depuis {0}", gmtToLocalDateHMS($consigneInfos[8])) : ($temperatureNative == null ? self::i18n("Batterie HS (date inconnue)") : ($consigneInfos[7] != '' ? self::i18n("Batterie faible depuis {0}", gmtToLocalDateHMS($consigneInfos[7])) : ''));

		// new 0.4.1 - Adjust TH - labels go to i18n
		$replace['#lblAdjTHTitle1#'] = self::i18n("Modification de la consigne sur '{0}'");
		$replace['#lblAdjTHTitle2#'] = self::i18n("La consigne de {0}° sera maintenue :");
		$replace['#lblAdjTHUntilCurrentSchedule#'] = self::i18n("jusqu'à la fin de la programmation courante, soit {0}");
		$replace['#lblAdjTHPermanent#'] = self::i18n("de façon permanente");
		$replace['#lblAdjTHUntil#'] = self::i18n("jusqu'à");
		$replace['#lblAdjTHUntilEndOfDay#'] = self::i18n("jusqu'à la fin de la journée");

		// new 0.4.1 - adjust background title of the widgets
		$bctMode = evoGetParam(self::CFG_BACKCOLOR_TITLE_MODES,self::CFG_BCT_MODE_NONE);
		$THcolor = 'var(--link-color)';
		//logDebug("bctMode = $bctMode");
		if ( $bctMode == self::CFG_BCT_MODE_NONE ) {
			// nothing to do
			$tA = "rgb(0,0,0,0)";
			$tB = "rgb(0,0,0,0)";
			$tBp = $tB;
		} else if ( $bctMode == self::CFG_BCT_MODE_SYSTEM ) {
			$tA = $replace['#background-color#'];
			$tB = $replace['#background-color#'];
			if ( jeedom::version() < 4 ) $THcolor = '#fff';
			$tBp = $tB;
		} else if ( $bctMode == self::CFG_BCT_MODE_2T ) {
			if ( $temperature >= intval(evoGetParam(self::CFG_BCT_2N_B,28)) ) {
				$tA = /*backgroundTopGradient ? "rgb(255,0,0,0)" :*/ "rgb(255,50,0,1)";
				$tB = "rgb(255,50,0,1)";
				$THcolor = '#fff';
			} else if ( $temperature >= intval(evoGetParam(self::CFG_BCT_2N_A,26)) ) {
				$tA = /*backgroundTopGradient ? "rgb(255,171,0,0)" :*/ "rgb(255,171,0,1)";
				$tB = "rgb(255,171,0,1)";
				$THcolor = '#fff';
			} else {
				$tA = $replace['#background-color#'];
				$tB = $replace['#background-color#'];
				if ( jeedom::version() < 4 ) $THcolor = '#fff';
			}
			$tBp = $tB;
		} else { // CFG_BCT_MODE_NT
			$THcolor = '#fff';
			$tA = "rgb(0,0,0,0)";
			$tB = "";
			$pc = 75;
			foreach ( self::C2BG as $tr=>$bgRef ) {
				if ($tB == "" && $temperature >= $tr) {
					$tB = $bgRef;
					$pc = $temperature == 0 ? 100 : 100 * (min(1, ($temperature - $tr) / 3));
					break;
				}
			}
			$tBp = $tB . " " . $pc . "%";
		}
		$replace['#background-color-A#'] = $tA;
		$replace['#background-color-B#'] = $tBp;
		$replace['#background-color-Bf#'] = $tB;
		$replace['#TH-color#'] = $THcolor;

		return $replace;
	}

	function setToHtmlProperties($pStates,$pScheduleCurrent,$pMsgInfo,$pTaskIsRunning=false,$pConsigne=null) {
		$lId = self::getLocationId($this);
		$zId = $this->getLogicalId();
		$key = 'toHtmlData_'.$lId."_".$zId;
		setCacheData($key,
			array("states"=>$pStates, "scheduleCurrent"=>$pScheduleCurrent, "msgInfo"=>$pMsgInfo, "taskIsRunning"=>$pTaskIsRunning ,"forcedConsigne"=>$pConsigne));
	}
	function setMsgInfo($msgInfo) {
		$lId = self::getLocationId($this);
		$zId = $this->getLogicalId();
		$key = 'toHtmlData_'.$lId."_".$zId;
		$zData = getCacheData($key);
		if ( array_key_exists("xx",$zData) ) {
			$zData["msgInfo"] = $pMsgInfo;
			setCacheData($key, $zData);
		}
	}
	function getToHtmlProperty($name) {
		$lId = self::getLocationId($this);
		$zId = $this->getLogicalId();
		$zData = getCacheData('toHtmlData_'.$lId."_".$zId);
		return (!is_array($zData) || !array_key_exists($name,$zData)) ? null : $zData[$name];
	}
	function removeToHtmlProperties() {
		$lId = self::getLocationId($this);
		$zId = $this->getLogicalId();
		doCacheRemove('toHtmlData_'.$lId."_".$zId);
	}

	function iRefreshComponent($infosZones=null,$inject=false) {
		//logDebug("IN>> refreshComponent");
		if ( is_array($infosZones) && $inject ) {
			$this->injectInformationsFromZone($infosZones);
		}
		$this->refreshWidget();	// does the toHtml by event (in another Thread, so the cache usage)
		//logDebug("<<OUT refreshComponent");
	}

 	public function toHtml($pVersion='dashboard') {
		doCacheRemove('evohomeWidget' . $pVersion . $this->getId());
		$locId = self::getLocationId($this);
		$typeEqu = $this->getConfiguration(self::CONF_TYPE_EQU);
		$zoneId = $this->getLogicalId();
		//logDebug("IN>> toHtml($pVersion) lid=$locId, zid= $zoneId (" . $this->getName() . ")");

		$replace = $this->preToHtml($pVersion);
		if (!is_array($replace)) return $replace;

		$version = jeedom::versionAlias($pVersion);

		$cachedData = getCacheData(self::CACHE_STATES.$locId);
		$refreshCache = false;
		$scheduleCurrent = $this->getToHtmlProperty("scheduleCurrent");
		//logDebug("-- private scheduleCurrent ? " . (is_array($scheduleCurrent) ? "yes" : "no"));
		if ( !is_array($scheduleCurrent) ) {
			if ( is_array($cachedData) && array_key_exists('scheduleCurrent',$cachedData) ) {
				//logDebug("use cachedData for scheduleCurrent");
				$scheduleCurrent = $cachedData['scheduleCurrent'];
			}
		}
		if ( !is_array($scheduleCurrent) ) {
			$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
			$refreshCache = true;
		}

		// settings depending of the "states" vars :
		$states = $this->getToHtmlProperty("states");
		//logDebug("-- private states ? " . (is_array($states) ? "yes" : "no"));
		if ( !is_array($states) ) {
			if ( is_array($cachedData) && array_key_exists('states',$cachedData) ) {
				//logDebug("use cachedData for states");
				$states = $cachedData['states'];
			}
		}
		if ( !is_array($states) ) {
			$states = self::getStates($locId,self::getInformationsAllZonesE2($locId));
			$refreshCache = true;
		}
		if ( $refreshCache ) {
			$cachedData = array("states"=>$states, "scheduleCurrent"=>$scheduleCurrent);
			setCacheData(self::CACHE_STATES, $cachedData, self::CACHE_STATES_DURATION, $locId);
		}

		$msgInfo = $this->getToHtmlProperty("msgInfo");

		$taskIsRunning = $this->getToHtmlProperty("taskIsRunning");
		if ( is_null($taskIsRunning) ) $taskIsRunning = false;

		if ( $typeEqu == self::TYPE_EQU_CONSOLE ) {
			//if ( isDebug() ) logDebug("-- toHtmlConsole msgInfo=$msgInfo");
			// CONSOLE
			$replace = $this->toHtmlConsole($pVersion,$version,$replace,$scheduleCurrent);
			$prevStatVisible = getCacheData(self::CACHE_STAT_PREV_VISIBLE);
			// got a STAT_PREV_VISIBLE, during console refresh
			doCacheRemove(self::CACHE_STAT_PREV_VISIBLE);
			if ( $prevStatVisible != '' ) {
				$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
				if ( is_object($cmd) && ($cmd->getIsVisible() ? '1' : '0') != $prevStatVisible ) {
					logDebug("** during console refresh, detect change stat visible state, launch a full refesh...");
					self::refreshAllForLoc($locId,self::getInformationsAllZonesE2($locId));
				}
			}
		}
		else {
			// TH WIDGET
			$forcedConsigne = $this->getToHtmlProperty("forcedConsigne");
			$replace = $this->toHtmlTh($pVersion,$version,$replace,$scheduleCurrent,$states,$forcedConsigne);
		}

		// single usage :
		$this->removeToHtmlProperties();

		$replace['#taskIsRunning#'] = $taskIsRunning ? "true" : "false";
		$replace['#evoBackgroundColor#'] = '#F6F6FF';
		$replace['#evoCmdBackgroundColor#'] = '#3498db';
		if ( jeedom::version() < 4 ) {
			$replace['#new-background-color#'] = 'background-color:#F6F6FF !important;';
		}

		$stateUnread = $states[self::STATE_UNREAD];
		$stateCnxLost = $states[self::STATE_CNX_LOST];
		$stateIsRunning = $states[self::STATE_IS_RUNNING];
		$stateLastRead = $states[self::STATE_LAST_READ];
		$stateIsAccurate = $states[self::STATE_IS_ACCURATE];
		$stateCronActive = $states[self::STATE_CRON_ACTIVE];
		$replace['#apiAvailable#'] = !$stateUnread && $stateCnxLost == '' ? "true" : "false";
		$replace['#msgApiUnavailable#'] = self::i18n("Fonction indisponible (erreur en API)");
		$replace['#evoTemperatureColor#'] = $stateUnread ? 'gray' : 'black';
		$replace['#evoConsigneColor#'] = $stateUnread ? 'lightgray' : 'white';
		$replace['#iazColorState#'] = $stateIsRunning ? 'crimson' : ($stateUnread || $stateCnxLost != '' ? 'red' : ($stateIsAccurate || evoGetParam(self::CFG_ACCURACY,1) == 1 ? 'lightgreen' : 'coral'));
		$replace['#iazIcon#'] = $stateIsRunning ? 'fas fa-spinner fa-pulse' : ($stateCnxLost != '' ? 'fas fa-wifi' : ($stateUnread ? (!$stateCronActive ? 'fas fa-bell-slash' : 'fas fa-unlink') : 'fas fa-circle'));
		$replace['#iazIconSize#'] = $stateIsRunning ? '16' : ($stateCnxLost != '' ? '14' : '10');
		$replace['#iazMarginRight#'] = $stateIsRunning ? '5' : ($stateCnxLost != '' ? '5' : '7');

		$waitNext = true;
		if ( $stateUnread ) {
			if ( $stateCronActive ) {
				$txt = self::i18n("Dernière lecture : {0} (problème en lecture)", $stateLastRead);
			} else {
				$txt = self::i18n("Dernière lecture : {0} ; le cron est arrêté", $stateLastRead);
				$waitNext = false;
			}
		} else if ( $stateIsRunning ) {
			$txt = self::i18n('Lecture en cours...');
			$waitNext = false;
		} else if ( $stateCnxLost != '' ) {
			$txt = self::i18n("La connexion a été perdue avec la passerelle le {0},<br/>les valeurs ou état affichés sont ceux d'avant la perte de connexion", $stateCnxLost);
			$waitNext = false;
		} else if ( $stateIsAccurate ) {
			$txt = self::i18n("Dernière lecture : {0}", $stateLastRead);
		} else {
			$txt = self::i18n("Dernière lecture : {0} (mode précis indisponible)", $stateLastRead);
		}
		if ( $waitNext ) {
			$addTime = getCacheRemaining(self::CACHE_CRON_TIMER);
			if ( $addTime <= 5 ) $addTime = self::getLoadingInterval();
			$tsNext = time() + $addTime;
			if ( evoGetParam(self::CFG_LOADING_SYNC,0) == 1 ) {
				// adjust fine time :
				$intvl = self::getLoadingInterval();
				$min = intVal(strftime("%M",$tsNext));
				if ( ($mod=$min % $intvl) != 0 ) {
					// intvl - (mn % intvl) ) * 60 ; mn=24 ; intvl=10 => 10 - (24 % 10) => 10 - 4 = 6
					$tsNext += 60 * ($intvl - $mod);
				}
			}
			$txt .= " (" . self::i18n("prochaine lecture") . " ~" . strftime('%H:%M',$tsNext) . ")";
		}
		$replace['#iazLastRead#'] = $txt;

		$replace['#evoMsgInfo#'] = str_replace("'", "\'", $msgInfo);

		$html = template_replace($replace, getTemplate('core', $version, 'evohome', __CLASS__));
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		//logDebug("<<OUT - toHtml");
		return $html;
	}

	/* Called when the  evoHistoryRetention is saved from the configuration panel */
	public static function postConfig_evoHistoryRetention() {
		logDebug('IN>> - postConfig_evoHistoryRetention');
		$hr = evoGetParam(self::CFG_HISTORY_RETENTION);
		foreach (self::getEquipments() as $eqLogic) {
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if ( $cmd->getIsHistorized() ) {
					$cmd->setConfiguration('historyPurge',$hr);
					$cmd->save();
				}
			}
			$eqLogic->iRefreshComponent();
		}
		logDebug('<<OUT - postConfig_evoHistoryRetention');
	}

	public static function getActionSaveId($locId) {
		$console = self::getConsole($locId);
		foreach ($console->getCmd('action') as $cmd) {
			if ( $cmd->getLogicalId() === 'save' ) {
				return $cmd->getId();
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

	static function getSchedule($locId,$fileId,$dateTime=0,$doRefresh=false) {
		if ( isDebug() ) logDebug("IN>> - getSchedule($locId,$fileId)");
		if ( $fileId == self::CURRENT_SCHEDULE_ID ) {
			$schedule = getCacheData(self::CACHE_CURRENT_SCHEDULE.$locId);
			if ( !is_array($schedule) ) {
				$infosZones = self::getInformationsAllZonesE2($locId,$doRefresh);
				if ( !is_array($infosZones) ) {
					if ( isDebug() ) logDebug('<<OUT - getSchedule(' . self::CURRENT_SCHEDULE_ID . ') - error while getInformationsAllZonesE2 (see above)');
					// avoid request again when we know requesting does not work
					setCacheData(self::CACHE_CURRENT_SCHEDULE, array('dummy','1'), self::CACHE_STATES_DURATION, $locId);
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
				setCacheData(self::CACHE_CURRENT_SCHEDULE, $schedule, self::CACHE_STATES_DURATION, $locId);
			} else {
				logDebug('got getSchedule(0) from cache');
			}
			logDebug('<<OUT - getSchedule(0)');
			return $schedule;
		}
		$fileInfos = self::getFileInfosById($locId,$fileId);
		if ( $fileInfos != null ) {
			$fileContent = file_get_contents($fileInfos['fullPath']);
			if ( isDebug() ) logDebug('getSchedule from ' . $fileInfos['fullPath']);
			$fileContentDecoded = json_decode($fileContent, true);
			if ( isDebug() ) logDebug("<<OUT - getSchedule($fileId)");
			return $fileContentDecoded;
		}
		if ( isDebug() ) logDebug("<<OUT - getSchedule($fileId) non trouvé");
		return null;
	}

	static function getSchedulePath() {
		$record_dir = dirname(__FILE__) . '/../../data/';
		if (!file_exists($record_dir)) {
			mkdir($record_dir, 0777, true);
		}
		return $record_dir;
	}

	static public function cmpName($a,$b) {
		return strcmp($a['name'], $b['name']);
	}
	static public function getHebdoNames($locId) {
		//logDebug("getHebdoNames($locId)...");
		$list = array();
		$schedulePath = self::getSchedulePath();
		foreach (ls($schedulePath, '*') as $file) {
			$parts = explode('_', $file);
			if ( $locId == null || $parts[0] == $locId ) {
				$list[] = array('id' => $parts[1],
								'name' => $parts[2],
								'fullPath' => $schedulePath . $file);
			}
		}
		if ( count($list) == 0 ) {
			$list[] = array('id' => 0,
							'name' => self::i18n('vide'),
							'fullPath' => '');
		} else {
			usort($list, "evohome::cmpName");
		}
		//logDebug("getHebdoNames($locId) : " . json_encode($list));
		return $list;
	}

	static function getFileInfosById($locId,$fileId) {
		foreach ( self::getHebdoNames($locId) as $item ) {
			if ( $item['id'] == $fileId ) return $item;
		}
		return null;
	}

	/*************** Statics about AJAX calls ********************/

	static function ajaxChangeStatScope($locId,$newStatScope) {
		$console = self::getConsole($locId);
		if ( $console != null ) {
			$cmdStatistics = $console->getCmd(null,self::CMD_STATISTICS_ID);
			if ( is_object($cmdStatistics) ) {
				$cmdStatistics->event($newStatScope);
				self::refreshAllForLoc($locId,self::getInformationsAllZonesE2($locId));
			}
		}
	}

	static function ajaxReloadLocations() {
		logDebug("IN>> - ajaxReloadLocations");
		//doCacheRemove(self::CACHE_INFOS_API);	// remove session
		$loc = self::listLocations(true);
		logDebug("<<OUT - ajaxReloadLocations");
		return $loc;
	}

	static function adaptSavedSchedules($locations) {
		$schedulePath = self::getSchedulePath();
		$files = ls($schedulePath, '*');
		if ( count($files) > 0 ) {
			$zonesLoc = array();
			foreach ($locations as $loc) {
				foreach ($loc['zones'] as $zone ) {
					$zonesLoc[$zone['id']] = $loc['locationId'];
				}
			}
			foreach ($files as $file) {
				$parts = explode('_', $file);
				if ( count($parts) == 2 ) {
					$fileContent = file_get_contents($schedulePath . $file);
					$fileContentDecoded = json_decode($fileContent, true);
					$locAssociated = $zonesLoc[$fileContentDecoded['zones'][0]['zoneId']];
					if ( isDebug() ) logDebug("adaptSavedSchedules $file associated with loc=$locAssociated");
					rename($schedulePath . $file, $schedulePath . $locAssociated . "_" . $file);
				}
			}
		}
	}

	static function ajaxSynchronizeTH($prefix,$resizeWhenSynchronize) {
		$execUnitId = rand(0,10000);
		if ( isDebug() ) logDebug("IN>> - ajaxSynchronizeTH($prefix,$resizeWhenSynchronize,EUI=$execUnitId)");
		// 0.4.1 - now, a single request is authorized in a delay of 1mn (cache auto-removed after 1 minute, and of course in this function ending)
		$prevExecUnitId = getCacheData(self::CACHE_SYNCHRO_RUNNING);
		if ( $prevExecUnitId != '' && $prevExecUnitId > 0 ) {
			if ( isDebug() ) logDebug("OUT<< - ajaxSynchronizeTH(EUI=$execUnitId) - request rejected due to potentially another one still running (EUI=$prevExecUnitId)");
			return null;
		}
		setCacheData(self::CACHE_SYNCHRO_RUNNING, $execUnitId, 60);
		lockCron();
		$locations = self::ajaxReloadLocations();
		if ( $locations == null ) {
			doCacheRemove(self::CACHE_SYNCHRO_RUNNING);
			unlockCron();
			return array("success"=>false, "message"=>self::i18n("Erreur en lecture des localisations"));
		}

		$syncVirtual = false;	// isVirtualAvailable();
		$nbAdded = 0;
		foreach ( $locations as $loc ) {
			$locId = $loc['locationId'];
			$zones = $loc['zones'];
			$zones[] = array("typeEqu"=>self::TYPE_EQU_CONSOLE, "id"=>$locId, "name"=>self::i18n("Console")." ".$loc['name']);
			foreach ($zones as $zone) {
				if ( isDebug() ) logDebug("Check for " . $zone["name"] . "/" . $zone["id"]);
				$todo = true;
				$eqLogic = null;
				foreach (self::getEquipments() as $tmp) {
					// 2nd part for compatibility (and upgrade) between 0.3.x and 0.4.0
					// 0.4.2 - now, we use LogicalId (check previous getConfiguration(self::CONF_ZONE_ID) for compatibility between 0.x.x and 0.4.2)
					$prevZoneId = $tmp->getLogicalId();;
					if ( $prevZoneId == null || $prevZoneId == '' || $prevZoneId == 0 ) $prevZoneId = $tmp->getConfiguration(self::CONF_ZONE_ID);
					if ( $prevZoneId == $zone["id"] ||
					     ($zone['typeEqu'] == self::TYPE_EQU_CONSOLE && $prevZoneId == self::OLD_ID_CONSOLE) ) {
						$eqLogic = $tmp;
						logDebug("-- refresh existing (cmds & size)");
						if ( $resizeWhenSynchronize ) {
							if ( $zone["typeEqu"] == self::TYPE_EQU_CONSOLE ) {
								$eqLogic->setDisplay("height", "162px");
								$eqLogic->setDisplay("width", "176px");
							} else {
								$eqLogic->setDisplay("height", "120px");
								$eqLogic->setDisplay("width", "210px");
							}
						}
						$eqLogic->setConfiguration(self::CONF_LOC_ID, $locId);
						// 0.4.2 - ZONE_ID now in LogicalID
						$eqLogic->setConfiguration(self::CONF_ZONE_ID, null);
						$eqLogic->setConfiguration("zone", null);	// from one previous version

						// TYPE_EQU_CONSOLE(C)/TYPE_EQU_THERMOSTAT(TH) :
						$eqLogic->setConfiguration(self::CONF_TYPE_EQU, $zone["typeEqu"]);
						// MODEL_TYPE_HEATING_ZONE(HeatingZone) / MODEL_TYPE_ROUND_WIRELESS(RoundWireless) :
						$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, $loc['modelType']);
						if ( $zone["typeEqu"] == self::TYPE_EQU_CONSOLE ) {
							if ( $prevZoneId == self::OLD_ID_CONSOLE ) {
								// 0.4.0 - migrate saved schedules
								self::adaptSavedSchedules($locations);
							}
							// 0.4.0 - update zoneId (== locId for Console)
							//$eqLogic->setConfiguration(self::CONF_ZONE_ID, $locId);	// here, 'zoneId' == $locId
							// 0.4.2 - ZONE_ID now in LogicalID
							$eqLogic->setLogicalId($locId);	// here, 'zoneId' == $locId
							// 0.4.0 -inject allowed system modes
							$eqLogic->setAllowedSystemModes($loc['asm']);
							// 0.4.0 - migrate General Parameter Schedule ID to localized
							if ( ($sId = evoGetParam(self::iCFG_SCHEDULE_ID, -10)) != -10) {
								evoSetParam(self::iCFG_SCHEDULE_ID, $sId, $locId);
								config::remove(self::iCFG_SCHEDULE_ID, __CLASS__);
							}
						}
						else {
							// 0.4.0 - 2019-06-29 - new : alert settings
							logDebug("-- alert settings..");
							$temp = $eqLogic->getCmd(null,self::CMD_TEMPERATURE_ID);
							// $temp->setAlert("warningif", "#value# >= 26");
							// $temp->setAlert("dangerif", "#value# >= 28");
							// 0.4.1 - 2019-07-22 - restore empty settings
							$temp->setAlert("warningif", "");
							$temp->setAlert("dangerif", "");
							logDebug("..done");
							// 0.4.2 - ZONE_ID now in LogicalID
							$eqLogic->setLogicalId($zone["id"]);
						}
						$eqLogic->save();
						$todo = false;
						break;
					}
				}
				if ($todo) {
					logDebug("-- create");
					$eqLogic = new evohome();
					$eqLogic->setEqType_name(__CLASS__);
					$eqLogic->setLogicalId($zone["id"]);	// will be undefined (useless in our case) - should be zoneId instead of Configuration prop ;)
					$zName = str_replace("'", "", $zone["name"]);
					$eqLogic->setName(($zone["typeEqu"] == self::TYPE_EQU_CONSOLE ? '' : $prefix) . $zName);
					$eqLogic->setIsVisible(1);
					$eqLogic->setIsEnable(1);
					$eqLogic->setCategory("heating", 1);
					$eqLogic->setConfiguration(self::CONF_LOC_ID, $locId);
					//$eqLogic->setConfiguration(self::CONF_ZONE_ID, $zone["id"]);
					$eqLogic->setConfiguration(self::CONF_TYPE_EQU, $zone["typeEqu"]);
					$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, $loc['modelType']);
					foreach (jeeObject::all() as $obj) {
						if ( stripos($zName,$obj->getName()) !== false || stripos($obj->getName(),$zName) !== false ) {
							$sql = "select count(*) as cnt from eqLogic where name = '" . $zName . "' and object_id = " . $obj->getId();
							$dbResults = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
							if ( count($dbResults) == 0 || $dbResults[0]['cnt'] == 0 ) {
								$eqLogic->setObject_id($obj->getId());
								break;
							}
						}
					}
					if ( $zone["typeEqu"] == self::TYPE_EQU_CONSOLE ) {
						$eqLogic->setDisplay("height", "162px");
						$eqLogic->setDisplay("width", "176px");
						$eqLogic->setAllowedSystemModes($loc['asm']);
					} else {
						$eqLogic->setDisplay("height", "120px");
						$eqLogic->setDisplay("width", "210px");
					}
					$eqLogic->save();
					// 0.4.1 - useless now (see general configuration)
					/* if ( $zone["typeEqu"] != self::TYPE_EQU_CONSOLE ) {
						// 0.4.0 - 2019-06-29 - new : alert settings
						$temp = $eqLogic->getCmd(null,self::CMD_TEMPERATURE_ID);
						$temp->setAlert("warningif", "#value# >= 25");
						$temp->setAlert("dangerif", "#value# >= 28");
						$eqLogic->save();
					} */
					$nbAdded += 1;
					logDebug("-- done !");
				}
				
				if ( $syncVirtual && $zone["typeEqu"] != self::TYPE_EQU_CONSOLE ) {
					logDebug("-- check virtual");
					foreach (eqLogic::byType("virtual") as $tmp) {
						if ( $tmp->getLogicalId() == $zone["id"] ) {
							logDebug("-- remove existing");
							$tmp->remove();
							break;
						}
					}
					logDebug("-- create virtual");
					$virtual = new virtual();
					$virtual->setEqType_name("virtual");
					$virtual->setName("(v) " . $eqLogic->getName());
					//$virtual->setConfiguration(self::CONF_ZONE_ID, $zone["id"]);
					$virtual->setIsVisible(0);
					$virtual->setIsEnable(1);
					$virtual->setObject_id($eqLogic->getObject_id());
					/*$virtual->setDisplay("showOndashboard", "0");
					$virtual->setDisplay("showOnplan", "0");
					$virtual->setDisplay("showOnview", "0");
					$virtual->setDisplay("showOnmobile", "0");*/
					$virtual->save();	// used to generate the id
					$virtual->copyFromEqLogic($eqLogic->getId());
					//TODO ?? LogicalID change against origin
					$cmd = cmd::byTypeEqLogicNameCmdName('virtual', $virtual->getName(), self::i18n('Type Consigne'));
					if (is_object($cmd)) {
						logDebug("-- remove Type Consigne");
						$cmd->remove();
					}
					$cmd = cmd::byTypeEqLogicNameCmdName('virtual', $virtual->getName(), self::i18n('Set Consigne'));
					if (is_object($cmd)) {
						logDebug("-- remove Set Consigne");
						$cmd->remove();
					}
				}
			}
		}
		if ( isDebug() ) logDebug("<<OUT - ajaxSynchronizeTH(EUI=$execUnitId)");
		unlockCron();
		doCacheRemove(self::CACHE_SYNCHRO_RUNNING);
		return array("success"=>true, "added"=>$nbAdded);
	}

	/************************ Actions ****************************/

	function doCaseAction($paramAction, $parameters) {
		if ( isDebug() ) logDebug("doCaseAction($paramAction)");
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
		$locId = self::getLocationId($this);
		$codeMode = $parameters[self::ARG_CODE_MODE];
		if ( $codeMode === null || $codeMode === '' ) {
			logDebug('IN>><<OUT - setMode called without code');
			return;
		}
		if ( isDebug() ) logDebug("IN>> - setMode with code=$codeMode");
		$execUnitId = rand(0,10000);
		self::waitingIAZReentrance("SetMode-$execUnitId");
		lockCron();

		// Call python function
		logDebug('setMode : call python');
		$aRet = self::runPython("SetModeE2.py", "SetModeE2_$execUnitId",
			array("task"=>self::i18n("Bascule vers le mode '{0}'", self::getModeName($codeMode)), "zoneId"=>$locId, "taskIsRunning"=>true),
			$locId . " " . $codeMode);
		$success = false;
		if ( !is_array($aRet) ) {
			logError("Error while setMode : response was empty or malformed", $aRet);
			$msgInfo = self::i18n("Erreur en changement de mode");
		} else if ( !$aRet[self::PY_SUCCESS] ) {
			logError("Error while setMode", $aRet);
			$msgInfo = self::i18n("Erreur en changement de mode : {0} - {1}", [$aRet["code"], $aRet["message"]]);
		} else {
			self::refreshConsole($locId, "1".self::i18n("Rechargement des données en attente..."), true);
			sleep(10);	// wait a bit before loading new values
			$msgInfo = "1" . self::i18n("Le mode de présence a été correctement modifié");
			$success = true;
		}
		if ( $success ) self::getInformationsAllZonesE2($locId,true,true,"1".self::i18n('Rechargement des données en cours...'),true);
		self::refreshConsole($locId,$msgInfo);

		logDebug('<<OUT - setMode');
		unlockCron();
	}

	function saveSchedule($parameters) {
		$locId = self::getLocationId($this);
		$fileName = $parameters[self::ARG_FILE_NAME];
		$fileId = $parameters[self::ARG_FILE_ID];
		$commentary = $parameters[self::ARG_FILE_REM];
		$newSchedule = array_key_exists(self::ARG_FILE_NEW_SCHEDULE,$parameters) ? $parameters[self::ARG_FILE_NEW_SCHEDULE] : null;
		if ( isDebug() ) logDebug("IN>> - saveSchedule($fileName, $fileId, " . ($newSchedule == null ? '<currentSchedule>' : '<newSchedule>') . ')');
		//self::waitingIAZReentrance('SaveSChedule-' . rand(0,10000));
		//lockCron();
		$dateTime = time();
		if ( (int)$fileId == self::CURRENT_SCHEDULE_ID ) {
			$fileId = $dateTime;
			$filePath = self::getSchedulePath() . $locId . "_" . $fileId . "_" . $fileName;
		} else {
			$fileInfos = self::getFileInfosById($locId,(int)$fileId);
			$filePath = $fileInfos['fullPath'];
		}
		if ( isDebug() ) logDebug("launch save action with fileName='$filePath'");
		// force refresh inside getInformationsAllZonesE2
		if ( $newSchedule == null ) {
			$rbs = evoGetParam(self::CFG_REFRESH_BEFORE_SAVE,0);
			$schedule = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID,$dateTime,$rbs==1);
		} else {
			$schedule = array('datetime'=>$dateTime, 'zones'=>json_decode($newSchedule,true));
		}
		if ( $schedule == null ) {
			logDebug('<<OUT - saveSchedule - error while getSchedule (see above)');
			// this call used to remove the loading mask on the screen
			self::refreshConsole($locId);
		} else {
			$fp = fopen($filePath, 'w');
			$schedule['comment'] = $commentary;
			fwrite($fp, json_encode($schedule));
			fclose($fp);

			if ( $newSchedule == null ) {
				evoSetParam(self::iCFG_SCHEDULE_ID, $fileId, $locId);
				//self::updateScheduleFileId();
			}/* else {*/
				self::refreshAllForLoc($locId,self::getInformationsAllZonesE2($locId));
			/*}*/
			logDebug('<<OUT - saveSchedule');
		}
		self::getConsole($locId)->updateRestoreList($locId);
		//unlockCron();
	}

	function restoreSchedule($parameters) {
		$locId = self::getLocationId($this);
		$fileId = $parameters[self::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($locId,$fileId);
		if ( $fileInfos == null ) {
			logError('restoreSchedule on unknown ID=' . $fileId);
			return;
		}
		// Optimisation - retain only saved schedule/zone # CurrentSchedule/zone
		$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
		$scheduleSaved = self::getSchedule($locId,$fileId);
		$scheduleDelta = array("zones"=>array());
		foreach ( $scheduleSaved['zones'] as $schZoneCandidate ) {
			$jsCandidate = json_encode($schZoneCandidate['schedule']);
			foreach ( $scheduleCurrent['zones'] as $schZoneCurrent ) {
				if ( $schZoneCandidate['zoneId'] == $schZoneCurrent['zoneId'] ) {
					$jsCurrent = json_encode($schZoneCurrent['schedule']);
					if ( $jsCandidate != $jsCurrent ) {
						$schZoneCandidate = array("zoneId"=>$schZoneCandidate['zoneId'], "schedule"=>$schZoneCandidate['schedule']);
						$scheduleDelta['zones'][] = $schZoneCandidate;
					}
					break;
				}
			}
		}
		$nbSchedules = count($scheduleDelta['zones']);
		if ( $nbSchedules == 0 ) {
			if ( isDebug() ) logDebug("restoreSchedule on ID=$fileId : no change to send");
			evoSetParam(self::iCFG_SCHEDULE_ID, $fileId, $locId);
			self::refreshConsole($locId,"1".self::i18n("Aucun changement envoyé (tous les programmes identiques)"));
			return;
		}

		$execUnitId = rand(0,10000);
		self::waitingIAZReentrance("RestoreSchedule-$execUnitId");
		lockCron();

		if ( isDebug() ) logDebug("restoreSchedule on saving ID=$fileId, name=" . $fileInfos['name'] . ", nbSchedules=$nbSchedules");
		// Call python function
		//$td = time();
		$prevFileId = evoGetParam(self::iCFG_SCHEDULE_ID, 0, $locId);
		evoSetParam(self::iCFG_SCHEDULE_ID, $fileId, $locId);
		$taskName = self::i18n("Restauration depuis '{0}' ({1} zone(s))", [$fileInfos['name'], $nbSchedules]);
		$aRet = self::runPython("RestaureZonesE2b.py", "RestaureZonesE2b_$execUnitId",
			// zoneId=locId to retrieve the Console associated to this location :
			array("task"=>$taskName, "zoneId"=>$locId, "taskIsRunning"=>true),
			$locId . " " . str_replace('"', '\"', json_encode($scheduleDelta)));
			//'"' . $fileInfos['fullPath'] . '"'
		if ( !is_array($aRet) ) {
			logError("Error while restoreSchedule : response was empty or malformed", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			evoSetParam(self::iCFG_SCHEDULE_ID, $prevFileId, $locId);
			self::refreshConsole($locId,self::i18n("Erreur pendant l'envoi de la programmation") . ($aRet !== '' ? " : " . $aRet : ""));
		}
		else if ( !$aRet[self::PY_SUCCESS] ) {
			logError("Error while restoreSchedule", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			evoSetParam(self::iCFG_SCHEDULE_ID, $prevFileId, $locId);
			self::refreshConsole($locId,self::i18n("Erreur pendant l'envoi de la programmation : {0} : {1}", [$aRet["code"], $aRet["message"]]));
		} else {
			$fp = fopen($fileInfos['fullPath'], 'r');
			$fileContent = fread($fp,filesize($fileInfos['fullPath']));
			$schedule = evoJsonDecode($fileContent, 'restoreSchedule2');
			fclose($fp);
			// will read immediately the data, which are not necessary uptodate just now :(
			/*$rdarTask = new RefreshDataAfterRestore($fileId, "1".evohome::i18n("L'envoi de la programmation s'est correctement effectué"), $schedule);
			$rdarTask->start();*/
			$nb = 1;
			while ( true ) {
				set_time_limit(60);	// reset the time_limit (?)
				$msgInfo = "1".self::i18n("Rafraichissement des données, essai {0}...", $nb);
				//self::updateScheduleFileId($schedule, $msgInfo, true);
				$allInfos = self::getInformationsAllZonesE2($locId,true, true, $msgInfo, true);
				while ( true ) {	// waiting for refresh event triggers the toHtmlConsole..
					$sd = getCacheData(self::CACHE_SCHEDULE_DELTA);
					if ( $sd !== '' ) break;
					usleep(250000);
				}
				//logDebug("***** console scheduleDelta received = <$sd>");
				if ( $sd == "1" ) {
					$nb += 1;
					self::refreshConsole($locId,"1".self::i18n("Rafraichissement des données : attente 30 sec avant essai {0}", $nb), true);
					sleep(30);
				} else {
					break;
				}
			}
			self::refreshConsole($locId,"1".self::i18n("L'envoi de la programmation s'est correctement effectué"));
		}
		unlockCron();
	}

	function deleteSchedule($parameters) {
		$fileId = $parameters[self::ARG_FILE_ID];
		$locId = self::getLocationId($this);
		$fileInfos = self::getFileInfosById($locId,$fileId);
		$msgInfo = '';
		if ( $fileInfos == null ) {
			logError('deleteSchedule on unknown ID=' . $fileId);
			$msgInfo = self::i18n("Fichier introuvable");
		} else {
			$cmdRestoreId = self::getConsole($locId)->getCmd(null, self::CMD_RESTORE)->getId();
			$sql = "select count(*) as cnt from scenarioExpression where expression = '#" . $cmdRestoreId . "#' and options like '%\"select\":\"$fileId\"%'";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ( count($results) == 1 && $results[0]['cnt'] == 1 ) {
				if ( isDebug() ) logDebug("Schedule $fileId is used in Scenario !");
				$msgInfo = self::i18n("Le fichier '{0}' est utilisé dans un {1} Scenario(s)", [ $fileInfos['name'], $results[0]['cnt']]);
			} else {
				if ( isDebug() ) logDebug("deleteSchedule on ID=$fileId");
				unlink($fileInfos['fullPath']);
				self::getConsole($locId)->updateRestoreList($locId);
			}
		}
		self::refreshConsole($locId,$msgInfo);
	}

	function setConsigne($parameters) {
		$params = $parameters[self::ARG_CONSIGNES_DATA];
		if ( isDebug() ) logDebug("IN>> - setConsigne($params)");
		if ( $params == null || $params == "" ) {
			logError(self::i18n("Par Scénario : Set Consigne : paramètre reçu invalide (le choix 'Aucun' dans la liste déroulante ne peut pes être évité, mais il est inutile !)"));
			logDebug("<<OUT - setConsigne");
			return;
		}
		$params = explode('#', $params);
		$zoneId = $params[1];
		$byScenario = $params[0] == 'auto' ? self::i18n("Par Scénario") . " : " : "";

		$locId = self::getLocationId($this);
		$infosZones = self::getInformationsAllZonesE2($locId);
		if ( !is_array($infosZones) ) {
			logError($byScenario . "Set Consigne - error while getInformationsAllZonesE2");
			logDebug("<<OUT - setConsigne");
			return;
		}
		$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
		$cmdConsigneInfos = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
		if ( is_object($cmdConsigne) && is_object($cmdConsigneInfos) ) {
			$oldConsigne = $cmdConsigne->execCmd();	// btw, this value is against unit chosen
			$oldConsigneInfos = explode(';', $cmdConsigneInfos->execCmd());
			# $oldConsigneInfos[0] = FollowSchedule / PermanentOverride / TemporaryOverride
			# $oldConsigneInfos[1] = 2018-01-28T23:00:00Z / <empty>
			# $oldConsigneInfos[2] = Celsius/Fahrenheit
			# ...
			$oldStatus = $oldConsigneInfos[0];
			$oldUntil = $oldConsigneInfos[1];
			$deviceUnit = $oldConsigneInfos[2];
		} else {
			foreach ( $infosZones['zones'] as &$infosZone ) {
				if ( $infosZone['zoneId'] == $zoneId ) {
					$oldConsigne = $infosZone['setPoint'];
					$oldStatus = $infosZone['status'];
					$oldUntil = $infosZone['until'];
					$deviceUnit = $infosZone['units'];
				}
				break;
			}
		}

		$newValue = self::revertAdjustByUnit($params[2],$deviceUnit);	// 0.4.1 - convert if needed
		// 0.4.1 - auto-read the scheduled value (specific case from scenario, as JS send the value)
		if ( $params[3] == 0 ) {
			$tmp = $this->getCmd(null,self::CMD_SCH_CONSIGNE_ID);
			$params[3] = $tmp->execCmd();	// btw, this value is against unit chosen
		}
		$realValue = self::revertAdjustByUnit($params[3],$deviceUnit);

		// $data = 'manuel/auto # zoneId # value (nn.n or 0=reset) # realValue # until ('null' or 'timevalue'
		$data = array('mode'=>$params[0],
						 'zoneId'=>$zoneId,		// string or numeric
						 'value'=>$newValue,	// keep in string
						 'realValue'=>$realValue,
						 'until'=>$params[4]);	// (PermanentOverride when null)
		if ( $data['until'] == '' || $data['until'] == 'null' ) $data['until'] = null;
		if ( $data['until'] != null ) {
			$date = new DateTime();
			$hm = explode(":",$data['until']);
			$date->setTime((int)$hm[0],(int)$hm[1],0);
			$date->setTimezone(new DateTimeZone('UTC'));
			$data['until'] = $date->format('Y-m-d') . 'T' . $date->format('H:i:s') . 'Z';	// %Y-%m-%dT%H:%M:%SZ
			if ( isDebug() ) logDebug("until set to = " . $data['until']);
		}
		$newStatus = $data['value'] == 0 ? self::FollowSchedule : ($data['until'] == null ? self::PermanentOverride : self::TemporaryOverride);
		$newUntil = $data['until'] == null ? 'NA' : $data['until'];
		if ( isDebug() ) logDebug("consigne=$oldConsigne<>$params[3] ; status=$oldStatus<>$newStatus ; until=$oldUntil<>$newUntil");
		if ( $oldConsigne == $params[3] && $oldStatus == $newStatus && $oldUntil == $newUntil ) {
			$msgInfo = $byScenario . self::i18n("Set Consigne zone {0} : valeurs reçues identiques aux valeurs courantes (consigne, durée)", $zoneId);
			logError($msgInfo);
			if ( $byScenario == "" ) {
				$this->setMsgInfo($msgInfo);
				$this->iRefreshComponent($infosZones);
			}
			logDebug("<<OUT - setConsigne");
			return;
		}
		// -----

		if ( $data['mode'] == 'auto' ) {	// triggered by scenario
			if ( self::getStates($locId)[self::STATE_UNREAD] ) {
				logError($byScenario . self::i18n("Set Consigne est indisponible : API off"));
				logDebug("<<OUT - setConsigne");
				return;
			}
			$currentMode = self::getCurrentMode($locId);
			if ( $currentMode == self::MODE_OFF || $currentMode == self::MODE_AWAY ) {
				logError($byScenario . self::i18n("Set Consigne est indisponible : mode de présence incompatible"));
				logDebug("<<OUT - setConsigne");
				return;
			}
		}

		// ...appel python...
		$execUnitId = rand(0,10000);
		$zname = $this->getName();
		//self::waitingIAZReentrance("setConsigne-$execUnitId");
		$taskName = $byScenario . self::i18n("Set consigne {0}° sur {1} ({2})", [$params[3],$zname,$data['until'] == null ? 'permanent' : self::i18n("jusqu'à {0}", $data['until'])]);
		$cmdParam = str_replace('"', '\"', json_encode($data));
		if ( isDebug() ) logDebug("setConsigne with $cmdParam");
		$infos = self::runPython("SetTempE2.py", "SetTempE2_$execUnitId",
			array("task"=>$taskName, "zoneId"=>$zoneId, "taskIsRunning"=>true, "consigne"=>$params[3]),
			$locId . " " . $cmdParam);
		$updated = false;
		if ( !is_array($infos) ) {
			$msgInfo = $byScenario . "Set Consigne zone $zoneId - error while SetTempE2 : response was empty or malformed";
			logError($msgInfo, $infos);
			$this->setMsgInfo($msgInfo);
		} else if ( !$infos[self::PY_SUCCESS] ) {
			$msgInfo = $byScenario . "Set Consigne zone $zoneId - error while SetTempE2";
			logError($msgInfo, $infos);
			$this->setMsgInfo($msgInfo);
		} else {
			$msgInfo = null;
			// merge requested value into zone data :
			foreach ( $infosZones['zones'] as &$infosZone ) {
				if ( $infosZone['zoneId'] == $zoneId ) {
					$infosZone['setPoint'] = $data['realValue'];
					$infosZone['status'] = $newStatus;
					$infosZone['until'] = $newUntil;
					$msgInfo = "1" . self::i18n("La consigne de {0}° a été correctement envoyée vers : {1}", [$params[3],$zname]);
					setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION, $locId);
					$updated = true;
					break;
				}
			}

			$states = self::getStates($locId,$infosZones);
			$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
			$this->setToHtmlProperties($states,$scheduleCurrent,$msgInfo);
		}
		$this->iRefreshComponent($infosZones,$updated);
		logDebug('<<OUT - setConsigne');
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
