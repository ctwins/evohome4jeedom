<?php
require_once 'honeywell.class.php';

class evohome extends honeywell {
	//public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

	const CFG_USER_NAME = 'evoUserName';
	const CFG_PASSWORD = 'evoPassword';

	const MODEL_TYPE_HEATING_ZONE = 'HeatingZone';
	const MODEL_TYPE_ROUND_WIRELESS = 'RoundWireless';

	// 0.4.2 - Deprecated
	const CONF_ZONE_ID = 'zoneId';
	const FollowSchedule = 'FollowSchedule';
	const PermanentOverride = 'PermanentOverride';
	const TemporaryOverride = 'TemporaryOverride';
	//const CMD_SET_CONSIGNE_ID = 'setConsigne';
	const OLD_ID_CONSOLE = -1;
	//const CURRENT_SCHEDULE_ID = 0;
	const LOG_INFO_ZONES = false;
	# -- infosAPI :
	const IZ_SESSION_ID_V1 = 'session_id_v1';
	const IZ_USER_ID_V1 = 'user_id_v1';
	const IZ_SESSION_STATE_V1 = 'session_state_v1';
	const IZ_SESSION_ID_V2 = 'access_token';
	const IZ_SESSION_EXPIRES_V2 = 'access_token_expires';
	const IZ_SESSION_STATE_V2 = 'token_state';

	const CACHE_LIST_LOCATIONS = 'evohomeListLocations';

	// Codes selon WebAPI/emea/api/v1/temperatureControlSystem/%s/mode
	const CODE_MODE_AUTO = 0;
	const CODE_MODE_OFF = 1;
	const CODE_MODE_ECO = 2;
	const CODE_MODE_AWAY = 3;
	const CODE_MODE_DAYOFF = 4;
	const CODE_MODE_CUSTOM = 6;

	/************************ Static methods *****************************/

	public static function main_refresh() {
		self::honeywell_refresh();
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		$script = dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance';
		return array('script' => $script, 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	static function i18n($txt, $arg=null) {
		return honeyutils::i18n($txt, __FILE__, $arg);
	}

	static function isPythonRunning() {
		// 0.4.0 - change the way
		/*$nb = exec("ps -ef | grep 'python /var/www/html/plugins/evohome/' | wc -l");
		if ( honeyutils::isDebug() ) honeyutils::logDebug('running python process : nb=' . ($nb/2 - 1));
		return $nb != 2;*/
		$out = "";
		evohome::__executePHP("ps -ef | grep 'python /var/www/html/plugins/".honeywell::PLUGIN_NAME."'", null, $out, 0, 1);
		$tmp = explode("www-data ", $out);
		$parts = array();
		foreach ( $tmp as $line ) if ( $line != '' && stripos($line,"grep") === false ) $parts[] = trim($line);
		$nbPython = count($parts);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("running python process : nb=$nbPython");
		return $nbPython != 0;
	}
	
	static function __executePHP($cmd, $data, &$stdout, $timeout=0, $depth=0) {
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
						. " (".honeyutils::tsToLocalMS(time()-$td) . ")");
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
			self::__executePHP("ps -ef | grep 'python /var/www/html/plugins/".honeywell::PLUGIN_NAME."'", null, $out, 0, 1);
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
			$prevTask = honeyutils::getCacheData(self::CACHE_PYTHON_RUNNING);
			if ( $prevTask === '' ) break;	// 0.4.0 - prevent some cases
			if ( honeyutils::isDebug() ) honeyutils::logDebug("another runPython ($prevTask) is running (a), wait 5sec before launching a new one ($taskName)");
			if ( time() - $td > 250 ) {
				honeyutils::logDebug("runPython : Timeout while waiting another python task ($prevTask) to end");
				return "Timeout while waiting another python task ($prevTask) to end";
			}
			set_time_limit(60);	// 0.4.0 - prevent losing control ; any value (0 could be a bad idea)
			sleep(5);
		}
		$password = str_replace('"','\"',str_replace('\\','\\\\',honeyutils::getParam(self::CFG_PASSWORD,'')));
		$credentials = honeyutils::getParam(self::CFG_USER_NAME,'') . ' "' . $password . '"';
		if ( $credentials === ' ""' ) {
			honeyutils::logDebug("runPython too early : account is not set yet");
			return "runPython too early : account is not set yet";
		}

		if ( $data != null && $data['task'] != null ) {
			self::refreshComponent($data, "1".$data['task']." : ".self::i18n("démarrage"));
		}
		self::setPythonRunning($taskName);
		$cmd = 'python ' . dirname(__FILE__) . '/../../resources/' . $prgName . ' ' . $credentials;

		// -- inject access_token/session from cachedInfosAPI
		$cachedInfosAPI = honeyutils::getCacheData(self::CACHE_INFOS_API);
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
		$cmd .= ' ' . (honeyutils::isDebug() ? '1' : '0');
		// -- inject parameters if any (auto insert locationId before)
		if ( $parameters !== null ) {
			//$cmd .= ' ' . $this->getLocationId() . ' ' . $parameters;
			// 0.4.0 : $parameters contains locId in first position
			$cmd .= ' ' . $parameters;
		}
		try {
			honeyutils::logDebug("Launching $prgName");
			//$json = trim(shell_exec($cmd));
			$json = '';
			// timeout=1mn40 max (2mn could be too much against max_execution_time, and in this case, all is lost..)
			// see also : https://stackoverflow.com/questions/6861033/how-to-catch-the-fatal-error-maximum-execution-time-of-30-seconds-exceeded-in-p
			$ret = self::__executePHP($cmd, $data, $json, 310);
			if ( honeyutils::isDebug() && $ret != 0 ) honeyutils::logDebug("Error while __executePHP ($ret) : <$json>");
			$json = trim($json);
		} catch (Exception $e) {
			honeyutils::logError("Exception while running python part");
			$json = '';
		}

		// cache API infos (access_token/session)
		$aData = honeyutils::jsonDecode($json, 'runPython('. $prgName . ')');
		if ( is_null($aData) ) {
			$aData = $json;	// will be not an array, and will be treated as an error (log report this content)
		} else {
			$updated = false;
			if ( array_key_exists(self::IZ_SESSION_ID_V1,$aData) && array_key_exists(self::IZ_USER_ID_V1,$aData) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V1] = $aData[self::IZ_SESSION_ID_V1];
				$cachedInfosAPI[self::IZ_USER_ID_V1] = $aData[self::IZ_USER_ID_V1];
				$updated = true;
				if ( honeyutils::isDebug() ) honeyutils::logDebug('runPython : session_v1 state=' . array('undefined', 'same', 'new', 'toBeRemoved')[$aData[self::IZ_SESSION_STATE_V1]]);
			}
			if ( array_key_exists(self::IZ_SESSION_ID_V2,$aData) && array_key_exists(self::IZ_SESSION_EXPIRES_V2,$aData) ) {
				$cachedInfosAPI[self::IZ_SESSION_ID_V2] = $aData[self::IZ_SESSION_ID_V2];
				$cachedInfosAPI[self::IZ_SESSION_EXPIRES_V2] = $aData[self::IZ_SESSION_EXPIRES_V2];
				$updated = true;
				if ( honeyutils::isDebug() ) honeyutils::logDebug('runPython : access_token state=' . array('undefined', 'same', 'new')[$aData[self::IZ_SESSION_STATE_V2]]);
			}
			if ( $updated ) {
				honeyutils::setCacheData(self::CACHE_INFOS_API, $cachedInfosAPI, self::CACHE_IAZ_DURATION);
				// IZ_SESSION_ID_V1 is the first key of the API session infos bloc (even when IZ_SESSION_ID_V2 is present)
				$pos = array_search(self::IZ_SESSION_ID_V1, array_keys($aData));
				if ( is_numeric($pos) ) {
					array_splice($aData, $pos);
				} else {
					// if IZ_SESSION_ID_V1 not here, IZ_SESSION_ID_V2 could be
					$pos = array_search(self::IZ_SESSION_ID_V2, array_keys($aData));
					if ( is_numeric($pos) ) array_splice($aData, $pos);
				}
			} else {
				honeyutils::logDebug('runPython : WARNING : no token nor sessionId received');
			}
		}

		self::razPythonRunning();
		if ( honeyutils::isDebug() ) honeyutils::logDebug("python.$taskName done in " . (time() - $td) . "sec", $aData);
		return $aData;
	}

	/*
	 * Read all Locations attached to the account (directly called, or from ajax call)
	 */
	public static function apiListLocations($enforce=false) {
		honeyutils::logDebug('IN>> - apiListLocations');
		$locations = honeyutils::getCacheData(self::CACHE_LIST_LOCATIONS);
		if ( $enforce || $locations == '') {
			$td = time();
			$locations = self::runPython("LocationsInfosE2.py","LocationsInfosE2_$td");
			if ( !is_array($locations)  ) {
				honeyutils::logError('Erreur while LocationsInfosE2 : response was empty or malformed', $locations);
				$locations = null;
			} else if ( !$locations[self::SUCCESS] ) {
				honeyutils::logError('Erreur while LocationsInfosE2', $locations);
				$locations = null;
			} else {
				$locations = $locations['locations'];
				honeyutils::setCacheData(self::CACHE_LIST_LOCATIONS, $locations);
			}
			if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT - apiListLocations from python');
		} else {
			honeyutils::logDebug('<<OUT - apiListLocations from cache');
		}
		return $locations;
	}

	/* Called when the evoHistoryRetention is saved from the configuration panel */
	public static function postConfig_evoHistoryRetention() {
		honeyutils::logDebug('IN>> - postConfig_evoHistoryRetention');
		$hr = honeyutils::getParam(self::CFG_HISTORY_RETENTION);
		foreach (self::getEquipments() as $eqLogic) {
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if ( $cmd->getIsHistorized() ) {
					$cmd->setConfiguration('historyPurge',$hr);
					$cmd->save();
				}
			}
			$eqLogic->iRefreshComponent();
		}
		honeyutils::logDebug('<<OUT - postConfig_evoHistoryRetention');
	}

	/*
	* Non obligatoire : permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {
	}
	*/

	static function evohomeReloadLocations() {
		honeyutils::logDebug("IN>> - evohomeReloadLocations");
		//honeyutils::doCacheRemove(self::CACHE_INFOS_API);	// remove session
		$loc = self::apiListLocations(true);
		honeyutils::logDebug("<<OUT - evohomeReloadLocations");
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
					if ( honeyutils::isDebug() ) honeyutils::logDebug("adaptSavedSchedules $file associated with loc=$locAssociated");
					rename($schedulePath . $file, $schedulePath . $locAssociated . "_" . $file);
				}
			}
		}
	}

	static function syncTH($prefix,$resizeWhenSynchronize) {
		$locations = self::evohomeReloadLocations();
		if ( $locations == null ) {
			return array(self::SUCCESS=>false, "message"=>self::i18n("Erreur en lecture des localisations"));
		}

		$nbAdded = 0;
		foreach ( $locations as $loc ) {
			$locId = $loc['locationId'];
			$zones = $loc['zones'];
			$zones[] = array("typeEqu"=>self::TYPE_EQU_CONSOLE, "id"=>$locId.'', "name"=>self::i18n("Console")." ".$loc['name']);
			foreach ($zones as $zone) {
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Check for " . $zone["name"] . "/" . $zone["id"]);
				$todo = true;
				$eqLogic = null;
				foreach (self::getEquipments() as $tmp) {
					// 2nd part for compatibility (and upgrade) between 0.3.x and 0.4.0
					// 0.4.2 - now, we use LogicalId (check previous getConfiguration(self::CONF_ZONE_ID) for compatibility between 0.x.x and 0.4.2)
					$prevZoneId = $tmp->getLogicalId();
					if ( $prevZoneId == null || $prevZoneId == '' || $prevZoneId == 0 ) $prevZoneId = $tmp->getConfiguration(self::CONF_ZONE_ID);
					if ( $prevZoneId == $zone["id"] ||
					     ($zone['typeEqu'] == self::TYPE_EQU_CONSOLE && $prevZoneId == self::OLD_ID_CONSOLE) ) {
						$eqLogic = $tmp;
						honeyutils::logDebug("-- refresh existing (cmds & size)");
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
						$eqLogic->setConfiguration(self::CONF_HNW_SYSTEM, self::SYSTEM_EVOHOME);
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
							if ( ($sId = honeyutils::getParam(self::iCFG_SCHEDULE_ID, -10)) != -10) {
								honeyutils::setParam(self::iCFG_SCHEDULE_ID, $sId, $locId);
								config::remove(self::iCFG_SCHEDULE_ID, __CLASS__);
							}
						}
						else {
							// 0.4.0 - 2019-06-29 - new : alert settings
							honeyutils::logDebug("-- alert settings..");
							$temp = $eqLogic->getCmd(null,self::CMD_TEMPERATURE_ID);
							// $temp->setAlert("warningif", "#value# >= 26");
							// $temp->setAlert("dangerif", "#value# >= 28");
							// 0.4.1 - 2019-07-22 - restore empty settings
							$temp->setAlert("warningif", "");
							$temp->setAlert("dangerif", "");
							honeyutils::logDebug("..done");
							// 0.4.2 - ZONE_ID now in LogicalID
							$eqLogic->setLogicalId($zone["id"]);
						}
						$eqLogic->save();
						$todo = false;
						break;
					}
				}
				if ($todo) {
					honeyutils::logDebug("-- create");
					$eqLogic = new evohome();
					$eqLogic->setEqType_name(honeywell::PLUGIN_NAME);
					$eqLogic->setLogicalId($zone["id"]);
					$zName = str_replace("'", "", $zone["name"]);
					$eqLogic->setName(($zone["typeEqu"] == self::TYPE_EQU_CONSOLE ? '' : $prefix) . $zName);
					$eqLogic->setIsVisible(1);
					$eqLogic->setIsEnable(1);
					$eqLogic->setCategory("heating", 1);
					$eqLogic->setConfiguration(self::CONF_LOC_ID, $locId);
					$eqLogic->setConfiguration(self::CONF_HNW_SYSTEM, self::SYSTEM_EVOHOME);
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
					honeyutils::logDebug("-- done !");
				}
			}
		}
		return array(self::SUCCESS=>true, "added"=>$nbAdded);
	}


	/*********************** Méthodes re-routées **************************/

	function __iGetInformations($locId, $forceRefresh=false, $readSchedule=true, $msgInfo='', $taskIsRunning=false) {
		try {
			$execUnitId = rand(0,10000);
			if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - __iGetInformations[$execUnitId,$locId]");
			$infosZones = honeyutils::getCacheData(self::CACHE_IAZ,$locId);
			$useCachedData = true;
			$infosZonesBefore = null;
			if ( !is_array($infosZones) || $forceRefresh ) {
				if ( self::waitingIAZReentrance("GETINFO_".$execUnitId) ) {
					$infosZones = honeyutils::getCacheData(self::CACHE_IAZ,$locId);
					// a reading has just been done
				} else {
					// Wait if another python is running
					$tdw = time();
					while ( self::isPythonRunning() ) {
						$prev = honeyutils::getCacheData(self::CACHE_PYTHON_RUNNING);
						if ( $prev === '' ) break;	// 0.4.0 - prevent some cases
						if ( honeyutils::isDebug() ) honeyutils::logDebug("another runPython ($prev) is running (b), wait 5sec before launching a new one (InfosZonesE2_$execUnitId)");
						if ( time() - $tdw > 250 ) {
							honeyutils::logError("Previous call to python ($prev) is blocking other requests");
							return null;
						}
						set_time_limit(60);	// 0.4.0 - prevent losing control ; any value (0 could be a bad idea)
						sleep(5);
					}
					self::activateIAZReentrance(15*60);	// was 120 - now 15mn against cloud freezing
					if ( is_array($infosZones) && $infosZones[self::SUCCESS] ) {
						$infosZonesBefore = $infosZones;
						if ( !$taskIsRunning ) {
							self::refreshAllForLoc($locId,$infosZonesBefore);
						} else {
							self::refreshAllForLoc($locId,$infosZonesBefore, false, $msgInfo, $taskIsRunning);
						}
					}
					$infosZones = self::runPython('InfosZonesE2.py', "InfosZonesE2_$execUnitId", null, $locId . " " . ($readSchedule ? "1" : "0"));
					self::deactivateIAZReentrance($locId);
					if ( !is_array($infosZones) ) {
						honeyutils::logError('Error while __iGetInformations : response was empty of malformed', $infosZones);
						if ( $infosZonesBefore != null ) {
							if ( $taskIsRunning ) {
								self::refreshAllForLoc($locId,$infosZonesBefore);
							} else {
								self::refreshAllForLoc($locId,$infosZonesBefore,false,$msgInfo);
							}
						}
					} else if ( !$infosZones[self::SUCCESS] ) {
						honeyutils::logError('Error while __iGetInformations', $infosZones);
						if ( $infosZonesBefore != null ) {
							if ( $taskIsRunning ) {
								self::refreshAllForLoc($locId,$infosZonesBefore);
							} else {
								self::refreshAllForLoc($locId,$infosZonesBefore,false,$msgInfo);
							}
						}
					} else {
						honeyutils::setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION, $locId);
						// refresh if needed
						if ( $readSchedule ) self::refreshAllForLoc($locId,$infosZones,true,$msgInfo,$taskIsRunning);
					}
					$useCachedData = false;
				}
			}
			if ( $useCachedData ) {
				if ( honeyutils::isDebug() ) honeyutils::logDebug('got __iGetInformations[' . $execUnitId . '] from cache (rest to live=' . honeyutils::getCacheRemaining(self::CACHE_IAZ,$locId) . ')');
				if ( $infosZonesBefore != null ) $infosZones = $infosZonesBefore;
			}
			if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT __iGetInformations[' . $execUnitId . ']');
			return $infosZones;
		} catch (Exception $e) {
			honeyutils::logError("Exception while __iGetInformations");
			return null;
		}
	}

	function __iGetModesArray() {
		return array(self::CODE_MODE_AUTO=>new HeatMode('Auto','Planning','i_calendar',true,null,HeatMode::SMD_NONE,true),
					 self::CODE_MODE_ECO=>new HeatMode('AutoWithEco','Economie','i_economy',true,null,HeatMode::SMD_PERM_DURING,true),
					 self::CODE_MODE_AWAY=>new HeatMode('Away','Innocupé','i_away',false,null,HeatMode::SMD_PERM_UNTIL,true),
					 self::CODE_MODE_DAYOFF=>new HeatMode('DayOff','Congé','i_dayoff',true,null,HeatMode::SMD_PERM_UNTIL,true),
					 self::CODE_MODE_CUSTOM=>new HeatMode('Custom','Personnalisé','i_custom',true,null,HeatMode::SMD_PERM_UNTIL,true),
					 self::CODE_MODE_OFF=>new HeatMode('HeatingOff','Arrêt','i_off',false,null,HeatMode::SMD_NONE,true)
					 );
	}

	function __iSetHtmlConsole(&$replace,$aEtat,$currentMode) {
		// $aEtat : "Auto";1 / "AutoWithEco";1/0;H / Away;1/0;D / DayOff;1/0;D / Custom;1/0;D / HeatingOff;1
		// with 1=True ; 0=False ; is the permanentMonde flag
		// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
		# permanent
		if ( $aEtat[1] == self::MODE_PERMANENT_ON && $currentMode != self::CODE_MODE_AUTO ) {
			$replace['#etatUntilImg#'] = 'override-active.png';
			$replace['#etatUntilDisplay#'] = 'none';
		}
		# delay
		else if ( $aEtat[1] == self::MODE_PERMANENT_OFF ) {
			$replace['#etatUntilImg#'] = 'temp-override-black.svg';
			// example : $aEtat[2] = "2018-01-28T23:00:00Z"
			$replace['#etatUntil#'] = $currentMode == self::CODE_MODE_ECO ? honeyutils::gmtToLocalHM($aEtat[2]) : honeyutils::gmtToLocalDate($aEtat[2]);
			$replace['#etatUntilFull#'] = $aEtat[2];
			$replace['#etatUntilDisplay#'] = 'inline';
		}
		else {
			$replace['#etatUntilImg#'] = 'empty.svg';
			$replace['#etatUntilDisplay#'] = 'none';
		}
	}

	function __iGetThModes($currentMode,$scheduleType_Unused,$consigneInfos) {
		return array (
			"isOff" => $currentMode == self::CODE_MODE_OFF,
			"isEco" => $currentMode == self::CODE_MODE_ECO,
			"isAway" => $currentMode == self::CODE_MODE_AWAY,
			"isDayOff" => $currentMode == self::CODE_MODE_DAYOFF,
			"isCustom" => $currentMode == self::CODE_MODE_CUSTOM,
			
			"follow" => $consigneInfos == null ? false : $consigneInfos[0] == self::FollowSchedule,
			"temporary" => $consigneInfos == null ? false : $consigneInfos[0] == self::TemporaryOverride,
			"permanent" => $consigneInfos == null ? false : $consigneInfos[0] == self::PermanentOverride,
			
			"scheduling" => true,
			
			"setThModes" => array(self::SET_TH_MODE_PERMANENT => self::i18n("de façon permanente"),					// until = null
								  self::SET_TH_MODE_UNTIL_CURRENT_SCH => self::i18n("jusqu'à la fin de la programmation courante, soit {0}"),
								  self::SET_TH_MODE_UNTIL_HHMM => self::i18n("jusqu'à"),							// until = <input HH:MM>
								  self::SET_TH_MODE_UNTIL_END_OF_DAY => self::i18n("jusqu'à la fin de la journée")),	// until=00:00

			"lblSchedule" => self::i18n("Programmes hebdo.")
			);
	}

	/*********************** Méthodes d'instance **************************/

	public function _postSaveTH() {
		honeyutils::logDebug(">>> evohome::_postSave");
		$infosZones = $this->iGetInformations();
		$this->injectInformationsFromZone($infosZones);
		honeyutils::logDebug("<<< evohome::_postSave");
	}


	/*************** Statics about AJAX calls ********************/

	static function ajaxSynchronizeTH($prefix,$resizeWhenSynchronize) {
		$execUnitId = rand(0,10000);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - Evohome::ajaxSynchronizeTH($prefix,$resizeWhenSynchronize,EUI=$execUnitId)");
		// 0.4.1 - now, a single request is authorized in a delay of 1mn (cache auto-removed after 1 minute, and of course in this function ending)
		$prevExecUnitId = honeyutils::getCacheData(self::CACHE_SYNCHRO_RUNNING);
		if ( $prevExecUnitId != '' && $prevExecUnitId > 0 ) {
			if ( honeyutils::isDebug() ) honeyutils::logDebug("OUT<< - Evohome::ajaxSynchronizeTH(EUI=$execUnitId) - request rejected due to potentially another one still running (EUI=$prevExecUnitId)");
			return null;
		}
		honeyutils::setCacheData(self::CACHE_SYNCHRO_RUNNING, $execUnitId, 60);

		honeyutils::lockCron();
		$ret = self::syncTH($prefix,$resizeWhenSynchronize);
		honeyutils::unlockCron();
		
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - Evohome::ajaxSynchronizeTH(EUI=$execUnitId)");
		honeyutils::doCacheRemove(self::CACHE_SYNCHRO_RUNNING);
		return $ret;
	}

	/************************ Actions ****************************/

	function __iSetMode($execUnitId,$locId,$codeMode) {
		// ...appel python...
		if ( honeyutils::isDebug() ) honeyutils::logDebug("__iSetMode($locId,$codeMode) : call python");
		$aRet = self::runPython("SetModeE2.py", "SetModeE2_$execUnitId",
			array("task"=>self::i18n("Bascule vers le mode '{0}'", $this->getModeName($codeMode)),
				  "zoneId"=>$locId,
				  "taskIsRunning"=>true),
			$locId . " " . $codeMode);
		$aRet["system"] = self::SYSTEM_EVOHOME;
		return $aRet;
	}

	function __iRestoreSchedule($execUnitId,$locId,$data,$taskName) {
		// ...appel python...
		$aRet = self::runPython("RestaureZonesE2b.py", "RestaureZonesE2b_$execUnitId",
								// zoneId=locId to retrieve the Console associated to this location :
								array("task"=>$taskName,
									  "zoneId"=>$locId,
									  "taskIsRunning"=>true),
								$locId . " " . str_replace('"', '\"', json_encode($data)));
			//'"' . $fileInfos['fullPath'] . '"'
		$aRet["system"] = self::SYSTEM_EVOHOME;
		return $aRet;
	}
	
	function __iTransformUntil($hm) {
		$hm = explode(":",$hm);
		$date = new DateTime();
		$date->setTime((int)$hm[0],(int)$hm[1],0);
		$ret = honeyutils::localDateTimeToGmtZ($date);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("until set to = " . $ret);
		return $ret;
	}

	function __iGetFPT() {
		return array(
			"follow"=>self::FollowSchedule,
			"permanent"=>self::PermanentOverride,
			"temporary"=>self::TemporaryOverride
		);
	}

	function __iSetConsigne($execUnitId,$locId,$zoneId,$data,$consigne,$taskName) {
		$cmdParam = str_replace('"', '\"', json_encode($data));
		if ( honeyutils::isDebug() ) honeyutils::logDebug("__iSetConsigne with $cmdParam");
		// ...appel python...
		return self::runPython("SetTempE2.py", "SetTempE2_$execUnitId",
								array("task"=>$taskName,
									  "zoneId"=>$zoneId,
									  "taskIsRunning"=>true,
									  "consigne"=>$consigne),
								$locId . " " . $cmdParam);
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
