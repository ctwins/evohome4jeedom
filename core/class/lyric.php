<?php
require_once 'honeywell.class.php';

/**
 * This class appears with version 0.5.0
 * @author ctwins95
 *
 */
class lyric extends honeywell {
	const HNW_DOMAIN = "https://api.honeywell.com/";
	const CFG_APP_NAME = "lyricAppName";
	const CFG_CONS_KEY = "lyricConsKey";
	const CFG_SECRET_KEY = "lyricSecretKey";
	const CFG_CALLBACK_URL = "lyricCallbackURL";
	const CALLBACK_LIMIT_TIME = 120;
	const CALLBACK_STATE = "lyricCallbackState";
	const HNW_DT_Thermostat = "Thermostat";
	const AUTH_DATA = "authData";

	const MODEL_TYPE_LYRIC_CONSOLE = "LyricConsole";

	const CACHE_LIST_LOCATIONS = 'lyricListLocations';
	
	// - codes mode
	const CODE_MODE_HEAT = 0;			// == Auto, follow weekly scheduling (no geofence)
	const CODE_MODE_OFF = 1;			// == Off
	const CODE_VMODE_DAYOFF = 4;		// == DayOff
	const CODE_VMODE_GEOFENCE = 101;	// specific case
	const MODE_OFF = "Off";
	const VMODE_DAYOFF = "Vacation";
	const VMODE_GEOFENCE = "Geofence";
	
	const TemporaryHold = "TemporaryHold";	// == TemporaryOverride (1)
	const HoldUntil = "HoldUntil";			// == TemporaryOverride (2)
	const PermanentHold = "PermanentHold";	// == PermanentOverride
	const NoHold = "NoHold";				// == FollowSchedule
	const VacationHold = "VacationHold";	// ??? when Vacation is enabled (and no override)


	/* ******************** API functions ******************** */

	static function apiGetRedirectUrl() {
	    return urlencode(honeyutils::getParam(self::CFG_CALLBACK_URL));	// see honeywell.ajax.php / initCallbackURL
	}

	static function getApiKey() {
		return honeyutils::getParam(self::CFG_CONS_KEY);
	}

	static function apiGetBasic() {
		return base64_encode(self::getApiKey().":".honeyutils::getParam(self::CFG_SECRET_KEY));
	}

	static function activateAPIReentrance($delay) {
		honeyutils::setCacheData("API", "true", $delay);
	}
	static function waitingAPIReentrance() {
 		while ( honeyutils::getCacheData("API") != '' ) {
			if ( honeyutils::isDebug() ) honeyutils::logDebug("waitingAPIReentrance() 2sec");
			sleep(2);
		}
	}
	static function deactivateAPIReentrance() {
		honeyutils::doCacheRemove("API");
	}

	static function xRequest($type,$uri,$data,$header,$retry=false) {
		if ( !$retry ) {
			self::waitingAPIReentrance();
			self::activateAPIReentrance(60);
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("$type $uri, " . json_encode($header) . ", " . json_encode($data));
		$curl = curl_init();
		$url = self::HNW_DOMAIN . $uri;
		if ( $type == "GET" ) {
			if ( $data ) $url .= "?" . http_build_query($data);
		} else {
			curl_setopt($curl, $type == "POST" ? CURLOPT_POST : CURLOPT_PUT, 1);
			if ( $data ) {
				if ( is_array($data) ) $data = json_encode($data);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($curl);
		// https://www.php.net/manual/fr/function.curl-getinfo.php
		$httpCode = curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
		curl_close($curl);

		if ( $httpCode != 200 ) {
			if ( $httpCode == 500 ) {
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Error : $ret");
				// exemple : $ret = {"fault":{"faultstring":"invalid_request","detail":{"errorcode":"invalid_request"}}}
				$aerror = json_decode($ret,true);
				$aret = array(self::SUCCESS=>false, "code"=>500, "message"=>$aerror["fault"]["faultstring"]);
			} else {
				$aerror = json_decode($ret,true);
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Error : hcode=$httpCode, ret=$ret, message=" . $aerror["message"]);
				if ( $httpCode == 401 && $uri != "oauth2/token" && !$retry ) {
					honeyutils::logDebug("-- attempt to refresh token (if expired)");
					$authData = self::apiTokenRefresh(true);
					honeyutils::logDebug("-- then retry the request");
					if ( $authData != null ) {
						$header[0] = 'Authorization: Bearer ' . $authData["access_token"];
						return self::xRequest($type,$uri,$data,$header,true);
					}
				}
				$aret = array(self::SUCCESS=>false, "code"=>$httpCode, "message"=>$ret);
			}
		} else {
			$aret = array(self::SUCCESS=>true, "data"=>json_decode($ret,true));
			if ( honeyutils::isDebug() ) honeyutils::logDebug("<<< xRequest = " . json_encode($aret));
		}
		self::deactivateAPIReentrance();
		return $aret;
	}
	static function getRequest($uri,$header,$params=false) {
		// GET request : https://weichie.com/blog/curl-api-calls-with-php/
		return self::xRequest("GET",$uri,$params,$header);
	}
	static function postRequest($uri,$header,$data,$retry=false) {
		// POST request : https://weichie.com/blog/curl-api-calls-with-php/
		return self::xRequest("POST",$uri,$data,$header,$retry);
	}
	static function putRequest($uri,$header,$data) {
		// PUT request
		return self::xRequest("PUT",$uri,$data,$header);
	}

	static function callBack() {
		// Step 2
		honeyutils::logDebug(">>> Lyric : callBack");
		if ( $_GET["state"] != honeyutils::getCacheData(lyric::CALLBACK_STATE) ) {
			honeyutils::logDebug("Error : state value is incorrect : '" . $_GET["state"] . "'");
			return array(self::SUCCESS=>false, "code"=>403, "message"=>self::i18n("Appel non autorisé ou délai ({0}sec) dépassé", lyric::CALLBACK_LIMIT_TIME));
		}

		// Step 3
		$code = $_GET["code"];		// code (new), scope (empty), state (as sent)

		// POST request : https://weichie.com/blog/curl-api-calls-with-php/
		$uri = "oauth2/token";
		$header = array(
		  'Authorization: Basic ' . self::apiGetBasic(),
		  'Content-Type: application/x-www-form-urlencoded',
		  'Accept: application/json'
		);
		$data = "grant_type=authorization_code";
		$data .= "&code=" . $code;
		$data .= "&redirect_uri=" . self::apiGetRedirectUrl();
		$ret = self::postRequest($uri, $header, $data);
		if ( $ret[self::SUCCESS] ) {
			self::saveAuthData($ret["data"]);
		}
		honeyutils::logDebug("<<< Lyric : callBack");
		return $ret;
	}

	// Called by cron / honeywell::honeywell_refresh et xRequest
	public static function apiTokenRefresh($retry=false) {
		honeyutils::logDebug(">>> Lyric : apiTokenRefresh");
		$authData = self::readAuthData();
		if ( $authData == null ) {
			honeyutils::logError("Lyric token unexists, go to the General conf and register");
			return null;
		}
		$uri = "oauth2/token";
		$header = array(
		  'Authorization: Basic ' . self::apiGetBasic(),
		  'Content-Type: application/x-www-form-urlencoded',
		  'Accept: application/json'
		);
		$data = "grant_type=refresh_token&refresh_token=" . $authData["refresh_token"];
		$ret = self::postRequest($uri, $header, $data, $retry);
		if ( !$ret[self::SUCCESS] ) {
			return null;
		}
		$result = $ret["data"];
		self::saveAuthData($result);
		return $result;
	}

	static function apiListLocations($enforce=false) {
		$locations = honeyutils::getCacheData(self::CACHE_LIST_LOCATIONS);
		if ( $enforce || $locations == '') {
			$authData = self::readAuthData();
			$uri = "v2/locations";
			$header = array('Authorization: Bearer ' . $authData["access_token"],
							'Accept: application/json');
			$params = array("apikey" => self::getApiKey());
			$data = self::getRequest($uri, $header, $params);
			if ( !$data[self::SUCCESS] ) {
				return null;
			}
			$locations = $data["data"];
			foreach ( $locations as &$loc ) {
				// add attribute with cases for compatibility :
				$loc["locationId"] = $loc["locationID"];
			}
			honeyutils::setCacheData(self::CACHE_LIST_LOCATIONS, $locations);
		}
		return $locations;
	}
	
	static function _getLocation($locId) {
		$locations = self::apiListLocations(true);
		if ( $locations == null ) return null;
		foreach ( $locations as $loc ) {
			if ( $loc["locationId"] == $locId ) {
				return $loc;
			}
		}
		return null;
	}

	static function apiReadThermostats($locId,$readSchedule) {
		$infosZones = array();
		$infosZones[self::SUCCESS] = true;
		$infosZones["apiV1"] = true;
		$infosZones["currentMode"] = "...";
		$infosZones["permanentMode"] = true;
		$infosZones["untilMode"] = "NA";
		$zones = array();

		$location = self::_getLocation($locId,true);
		$authData = self::readAuthData();

		if ( $location == null ) {
			$infosZones[self::SUCCESS] = false;
		} else {
			$devices = $location["devices"];
			foreach ( $devices as $device ) {
				if ( $device["changeableValues"]["mode"] == self::MODE_OFF ) {
					$infosZones["currentMode"] = self::MODE_OFF;
				} else if ( $device["vacationHold"]["enabled"] && $device["changeableValues"]["thermostatSetpointStatus"] == "VacationHold" ) {
					$infosZones["currentMode"] = self::VMODE_DAYOFF;	// Vacation
					$infosZones["permanentMode"] = false;
					$infosZones["untilMode"] = $device["vacationHold"]["vacationEnd"];
				} else if ( $device["scheduleType"]["scheduleType"] == self::VMODE_GEOFENCE ) {
					$infosZones["currentMode"] = self::VMODE_GEOFENCE;
					// Check location/geoFences data to check the Presence status
					$infosZones["inside"] = $location["geoFences"][0]["geoOccupancy"]["withinFence"] > 0;
				} else {
					// Heat means for the plugin : follow the schedule (so, mode schedule, not geofence)
					$infosZones["currentMode"] = $device["changeableValues"]["mode"];
				}
				$zoneInfos = array();
				$zoneInfos["zoneId"] = $device["deviceID"];
				$zoneInfos["name"] = $device["name"];
				//if ( !$device["isAlive"] ) {
					// GMT datetime for compatibility (will be revert to local time for display !)
					// $zoneInfos[self::IZ_GATEWAY_CNX_LOST] = honeyutils::localDateTimeToGmtZ(new DateTime());
				//}
				$zoneInfos["temperature"] = $device["indoorTemperature"];
				$zoneInfos["units"] = substr($device["units"],0,1);
				$zoneInfos["setPointCapabilities"] = array(
					"resolution"=>($zoneInfos["units"] == self::CFG_UNIT_CELSIUS ? 0.5 : 1),	// fixed as no returned (or 0.5 for °C, 1 for °F ?)
					"minHeat"=>$device["minHeatSetpoint"],	// 5
					"maxHeat"=>$device["maxHeatSetpoint"]);	// 35
				// 0.5.6 - schedule limits :	
				$zoneInfos["scheduleCapabilities"] = array(
					"minPerDay"=>1,
					"maxPerDay"=>6,
					"timeInterval"=>$device["allowedTimeIncrements"]);
				
				$zoneInfos["setPoint"] = $device["changeableValues"]["heatSetpoint"];	// 19
				//$zoneInfos["status"] = $device["scheduleStatus"];	// current status of schedule : "Resume" ??
				// current setpoint state : TemporaryHold(+nextPeriodTime), HoldUntil(??), PermanentHold(??), VacationHold(-), NoHold(-) :
				$zoneInfos["status"] = $device["changeableValues"]["thermostatSetpointStatus"];
				if ( $zoneInfos["status"] == self::TemporaryHold ) {
					$hms = $device["changeableValues"]["nextPeriodTime"];	// HH:MM:SS - local time
					$zoneInfos["until"] = honeyutils::localDateTimeToGmtZ(new DateTime($hms));	// convert to GMT
					$zoneInfos["endHeatSetpoint"] = $device["changeableValues"]["endHeatSetpoint"];
				} else if ( $zoneInfos["status"] == self::HoldUntil ) {
					$zoneInfos["until"] = $device["changeableValues"]["nextPeriodTime"];	// format ??
					$zoneInfos["endHeatSetpoint"] = $device["changeableValues"]["endHeatSetpoint"];
				} else {
					$zoneInfos["until"] = "NA";
				}
				if ( $readSchedule ) {
					if ( $infosZones["currentMode"] != self::VMODE_DAYOFF ) {
						$data = self::apiReadSchedule($authData,$locId,$device["deviceID"]);
						$infosZones["scheduleType"] = $data['type'];	// should be similar as $infosZones["currentMode"], but specialized here for schedule datas
						$zoneInfos["schedule"] = $data['data'];
					} else {
						$infosZones["scheduleType"] = HeatMode::SCHEDULE_TYPE_VACANCY;
						$zoneInfos["schedule"] = array("VacationSchedule"=>array(
							"start" => $device["vacationHold"]["vacationStart"],
							"end" => $device["vacationHold"]["vacationEnd"],
							"heatSetPoint" => $device["vacationHold"]["heatSetpoint"]	// in °F ; use honeyutils::adjustByUnit(xx,F)
							)
						);
					}
				} else {
					$zoneInfos["schedule"] = null;
				}
				// add infos :
				$zoneInfos["heating"] = $device["operationStatus"]["mode"] == "Heat" ? '1' : '0';
				$zoneInfos["outdoorTemperature"] = $device["outdoorTemperature"];
				//$zoneInfos["displayedOutdoorHumidity"] = $device["displayedOutdoorHumidity"];
				/*$zoneInfos["scheduleType"] = array(
					"avlTypes"=>$device["scheduleCapabilities"]["availableScheduleTypes"],	// is an array, like None,Geofenced,TimedEmea/TimedNorthAmerica
					"currentType"=>$device["scheduleType"]["scheduleType"]					// Geofence
				);*/
				$zones[] = $zoneInfos;
			}
		}
		$infosZones["zones"] = $zones;
		$infosZones["timestamp"] = time();
		$infosZones["access_token"] = $authData["access_token"];
		$infosZones["access_token_expires"] = $authData["expires_in"];

		if ( honeyutils::isDebug() ) honeyutils::logDebug("infosZones = " . json_encode($infosZones));
		return $infosZones;
	}

	static function apiReadSchedule($authData, $locId, $deviceId) {
		// https://developer.honeywellhome.com/lyric/apis/get/devices/schedule/%7BdeviceId%7D
		// curl -X GET -H 'Authorization: Bearer XXXXX' -H 'Accept: application/json' "https://api.honeywell.com/v2/devices/schedule/{deviceId}?apikey={ApiKey}&locationId={locId}&type=regular"
		$uri = "v2/devices/schedule/" . $deviceId;
		$header = array('Authorization: Bearer ' . $authData["access_token"],
						'Accept: application/json');
		$params = array("apikey" => self::getApiKey(), "locationId" => $locId, "type"=>"regular");
		$data = self::getRequest($uri, $header, $params);
		if ( !$data[self::SUCCESS] ) return null;

		$schedule = $data["data"];
		if ( $schedule["scheduleType"] == self::VMODE_GEOFENCE ) {
			/*{	"deviceID": "{deviceId}",
				"scheduleType": "Geofence",
				"geoFenceSchedule": {
					"homePeriod": { "heatSetPoint": 66.2, "coolSetPoint": 77.9 },
					"awayPeriod": { "heatSetPoint": 64.4, "coolSetPoint": 85.1 },
					"sleepMode":  { "startTime": "22:00:00", "endTime": "07:00:00", "heatSetPoint": 64.4, "coolSetPoint": 82.4 }
				}
			} 0.5.1 - alternate for sleepMode, seen around 2020-11 :
					"sleepMode": { "triggers": [{
								"startTime": "22:00:00", "endTime": "07:00:00",
								"days": ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"] } ],
						"heatSetPoint": 64.4, "coolSetPoint": 82.4 } */
			// 0.5.1 - convert when there is triggers with a single choice of the 7 week days ;)
			if ( $schedule['geoFenceSchedule']['sleepMode']['triggers'] ) {
				$t0 = $schedule['geoFenceSchedule']['sleepMode']['triggers'][0];
				if ( count($t0['days']) == 7 ) {
					honeyutils::logDebug("geoFenceSchedule defined with triggers with full week set on first, what is useless : convert to schedule without trigger");
					$newSleepMode = array("startTime"=>$t0["startTime"],
										  "endTime"=>$t0["endTime"],
										  "heatSetPoint"=>$schedule['geoFenceSchedule']['sleepMode']['heatSetPoint'],
										  "coolSetPoint"=>$schedule['geoFenceSchedule']['sleepMode']['coolSetPoint']);
					$schedule['geoFenceSchedule']['sleepMode'] = $newSleepMode;
				}
			}
			return array("type"=>HeatMode::SCHEDULE_TYPE_GEOFENCE,"data"=>array("GeofenceSchedule"=>$schedule["geoFenceSchedule"]));
		}

		/* Transform data to be compatible with Evohome management:
		"DailySchedules": [{ "DayOfWeek": 0, "Switchpoints": [{ "heatSetpoint": 18, "TimeOfDay": "08:00:00" }, {...
		*/
		$evoSchedule = array();
		foreach ( $schedule["timedSchedule"]["days"] as $data ) {
			$points = array();
			foreach ( $data["periods"] as $period ) {
				$points[] = array(
					"heatSetpoint"=>honeyutils::F2C($period["heatSetPoint"]),
					//"coolSetpoint"=>$periods["coolSetPoint"],
					"TimeOfDay"=>$period["startTime"]
				);
			}
			$evoSchedule[] = array(
				"DayOfWeek"=>honeyutils::getNumDayFromECDay($data["day"]),
				"Switchpoints"=>$points);
		}

		return array("type"=>HeatMode::SCHEDULE_TYPE_TIME,"data"=>array("DailySchedules"=>$evoSchedule));
	}


	/* ******************** Statics ******************** */

	static function i18n($txt, $arg=null) {
		return honeyutils::i18n($txt, __FILE__, $arg);
	}

	// called by programmatic cron via honeywell::honeywell_refresh
	static function lyric_refresh() {
		self::apiTokenRefresh();
	}

	static function saveAuthData($authData) {
		$authData["timestamp"] = honeyutils::tsToLocalDateTime(time());
		honeyutils::setParam(self::AUTH_DATA,$authData);
	}

	static function readAuthData() {
		$content = honeyutils::getParam(self::AUTH_DATA,"");
		$ret = $content === "" ? array() : (is_array($content) ? $content : honeyutils::jsonDecode($content, 'lyric::readAuthData'));
		return $ret;
	}

	static function syncTH($prefix,$resizeWhenSynchronize) {
		self::deactivateIAZReentrance();
		$locations = self::apiListLocations();
		if ( $locations == null ) {
			return array(self::SUCCESS=>false, "message"=>self::i18n("Erreur en lecture des localisations"));
		}

		$nbModified = 0;
		$nbAdded = 0;
		foreach ( $locations as $loc ) {
			$locId = $loc['locationId'];
			$devices = $loc['devices'];
			$devices[] = array("deviceType"=>self::TYPE_EQU_CONSOLE, "deviceID"=>$locId, "name"=>self::i18n("Console")." ".$loc['name']);
			$allowedModes = null;
			foreach ($devices as $device) {
				$type = $device["deviceType"];
				if ( $type == self::HNW_DT_Thermostat ) $type = self::TYPE_EQU_THERMOSTAT;
				if ( $type != self::TYPE_EQU_CONSOLE && $type != self::TYPE_EQU_THERMOSTAT ) {
					if ( honeyutils::isDebug() ) honeyutils::logDebug("Lyric device '$type' not supported");
					continue;
				}
				$ID = $device["deviceID"];
				$todo = true;
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Check for " . $device["name"] . "/" . $ID);
				foreach (self::getEquipments() as $tmp) {
					if ( $tmp->getLogicalId() == $ID ) {
						$eqLogic = $tmp;
						if ( honeyutils::isDebug() ) honeyutils::logDebug("-- refresh existing (cmds & size) for " . get_class($tmp));
						if ( $resizeWhenSynchronize ) {
							if ( $type == self::TYPE_EQU_CONSOLE ) {
								$eqLogic->setDisplay("height", "162px");
								$eqLogic->setDisplay("width", "176px");
							} else {
								$eqLogic->setDisplay("height", "120px");
								$eqLogic->setDisplay("width", "210px");
							}
						}
						$eqLogic->setConfiguration(self::CONF_LOC_ID, $locId);
						$eqLogic->setLogicalId($ID);
						$eqLogic->setConfiguration(self::CONF_HNW_SYSTEM, self::SYSTEM_LYRIC);
						// TYPE_EQU_CONSOLE(C) / TYPE_EQU_THERMOSTAT(TH) :
						$eqLogic->setConfiguration(self::CONF_TYPE_EQU, $type);
						
						if ( $type == self::TYPE_EQU_CONSOLE ) {
							$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, self::MODEL_TYPE_LYRIC_CONSOLE);
							$eqLogic->setAllowedSystemModes($allowedModes);
						} else {
							// example "T5-T6" for TH ; T9-T10 ? ; D6 ? / Round ??
							$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, $device['deviceModel']);
							// AllowedSystemModes : à renvoyer vers la Console (console->setConfiguration..)
							// Example 1 : "EmergencyHeat", "Heat", "Off", "Cool", "Auto"
							// Example 2 : "Heat", "Off"
							// Example 3 : "Heat", "Off", "Cool"
							$allowedModes = $device['allowedModes'];
							$allowedModes[] = self::VMODE_DAYOFF;
							$allowedModes[] = self::VMODE_GEOFENCE;
							// $device["scheduleCapabilities"]["availableScheduleTypes"] : None, Geofenced, TimedEmea/TimedNorthAmerica
						}
						$eqLogic->save();
						$nbModified += 1;
						$todo = false;
						break;
					}
				}
				if ($todo) {
					$zName = str_replace("'", "", $device["name"]);
					if ( honeyutils::isDebug() ) honeyutils::logDebug("-- create on locId=$locId, typeEqu=$type, logId=$ID, name=$zName");
					$eqLogic = new lyric();	// use the child class !
					$eqLogic->setEqType_name(honeywell::PLUGIN_NAME);
					$eqLogic->setLogicalId($ID);
					$eqLogic->setName(($type == self::TYPE_EQU_CONSOLE ? '' : $prefix) . $zName);
					$eqLogic->setIsVisible(1);
					$eqLogic->setIsEnable(1);
					$eqLogic->setCategory("heating", 1);
					$eqLogic->setConfiguration(self::CONF_LOC_ID, $locId);
					$eqLogic->setConfiguration(self::CONF_HNW_SYSTEM, self::SYSTEM_LYRIC);
					$eqLogic->setConfiguration(self::CONF_TYPE_EQU, $type);
					$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, $device['deviceModel']);
					/*foreach (jeeObject::all() as $obj) {
						if ( stripos($zName,$obj->getName()) !== false || stripos($obj->getName(),$zName) !== false ) {
							$sql = "select count(*) as cnt from eqLogic where name = '" . $zName . "' and object_id = " . $obj->getId();
							$dbResults = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
							if ( count($dbResults) == 0 || $dbResults[0]['cnt'] == 0 ) {
								$eqLogic->setObject_id($obj->getId());
								break;
							}
						}
					}*/
					if ( $type == self::TYPE_EQU_CONSOLE ) {
						$eqLogic->setDisplay("height", "162px");
						$eqLogic->setDisplay("width", "176px");
						$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, self::MODEL_TYPE_LYRIC_CONSOLE);
						$eqLogic->setAllowedSystemModes($allowedModes);
					} else {
						$eqLogic->setDisplay("height", "120px");
						$eqLogic->setDisplay("width", "210px");
						$eqLogic->setConfiguration(self::CONF_MODEL_TYPE, $device['deviceModel']);	// by example "T5-T6" for TH
						// Example 1 : "EmergencyHeat", "Heat", "Off", "Cool", "Auto"
						// Example 2 : "Heat", "Off"...
						// Example 3 : "Heat", "Off", "Cool"
						$allowedModes = $device['allowedModes'];
						$allowedModes[] = self::VMODE_DAYOFF;
						$allowedModes[] = self::VMODE_GEOFENCE;
					}
					$eqLogic->save();
					$nbAdded += 1;
					honeyutils::logDebug("-- done !");
				}
			}
			// 'postSave'
			self::iGetInformations($locId,true);
		}
		return array(self::SUCCESS=>true, "added"=>$nbAdded, "modified"=>$nbModified);
	}


	/*********************** Méthodes d'instance **************************/

	function _postSaveTH() {
	}


	/*********************** Méthodes re-routées **************************/

	function iGetModesArray() {
		return array(self::CODE_MODE_OFF=>new HeatMode(self::MODE_OFF,'Arrêt','i_off',false,null,HeatMode::SMD_NONE,true),
					 self::CODE_MODE_HEAT=>new HeatMode('Heat','Planning','i_calendar',true,HeatMode::SCHEDULE_TYPE_TIME,HeatMode::SMD_NONE,true),
					 self::CODE_VMODE_GEOFENCE=>new HeatMode(self::VMODE_GEOFENCE,'Détection','i_geofence',false,HeatMode::SCHEDULE_TYPE_GEOFENCE,HeatMode::SMD_NONE,true),
					 self::CODE_VMODE_DAYOFF=>new HeatMode(self::VMODE_DAYOFF,'Congés','i_away',false,null/*HeatMode::SCHEDULE_TYPE_VACANCY*/,HeatMode::SMD_NONE,false)
					 );
	}

	static function iGetInformations($locId, $forceRefresh=false, $readSchedule=true, $msgInfo='', $taskIsRunning=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug(">>> Lyric::iGetInformations(locId=$locId,forceRefresh=$forceRefresh,readSchedule=$readSchedule,taskIsRunning=$taskIsRunning)");
		try {
			$execUnitId = rand(0,10000);
			if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - Lyric::iGetInformations[$execUnitId,$locId]");
			$infosZones = honeyutils::getCacheData(self::CACHE_IAZ,$locId);
			$useCachedData = true;
			$infosZonesBefore = null;
			if ( !is_array($infosZones) || $forceRefresh ) {
				if ( self::waitingIAZReentrance("GETINFO_".$execUnitId) ) {
					$infosZones = honeyutils::getCacheData(self::CACHE_IAZ,$locId);
					// a reading has just been done
				} else {
					self::activateIAZReentrance(15*60);
					if ( is_array($infosZones) && $infosZones[self::SUCCESS] ) {
						$infosZonesBefore = $infosZones;
						if ( !$taskIsRunning ) {
							self::refreshAllForLoc($locId,$infosZonesBefore);
						} else {
							self::refreshAllForLoc($locId,$infosZonesBefore, false, $msgInfo, $taskIsRunning);
						}
					}
					// ************************************************************
					try {
						$td = time();
						$infosZones = self::apiReadThermostats($locId, $readSchedule);
						$delay = time() - $td;
						if ( $delay < 3 ) sleep(3-$delay);	// time to see the rolling picture ;)
					} catch (Exception $e) {
						honeyutils::logError("Exception : " . json_encode($e));
					}
					// ************************************************************
					self::deactivateIAZReentrance($locId);
					if ( !is_array($infosZones) ) {
						honeyutils::logError('Error while Lyric::iGetInformations : response was empty of malformed', $infosZones);
						if ( $infosZonesBefore != null ) {
							if ( $taskIsRunning ) {
								self::refreshAllForLoc($locId,$infosZonesBefore);
							} else {
								self::refreshAllForLoc($locId,$infosZonesBefore,false,$msgInfo);
							}
						}
					} else if ( !$infosZones[self::SUCCESS] ) {
						honeyutils::logError('Error while Lyric::iGetInformations', $infosZones);
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
				if ( honeyutils::isDebug() ) honeyutils::logDebug("got Lyric::iGetInformations[$execUnitId] from cache (rest to live=" . honeyutils::getCacheRemaining(self::CACHE_IAZ,$locId) . ')');
				if ( $infosZonesBefore != null ) $infosZones = $infosZonesBefore;
			}
			if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT Lyric::iGetInformations[' . $execUnitId . ']');
			return $infosZones;
		} catch (Exception $e) {
			honeyutils::logError("Exception while Lyric::iGetInformations");
			return null;
		}

		honeyutils::logDebug("<<< Lyric::iGetInformations");
	}

	function iSetHtmlConsole(&$replace,$state,$currentMode) {
		// Off;1 ; "Heat";1 / Geofence;?? ; DayOff;0;D
		// with 1=True ; 0=False ; is the permanentMode flag
		// if False, until part is added : Xxx;False;2018-01-29T20:34:00Z, with H for hours, D for days
		$replace['#etatModeImgDisplay#'] = 'none';
		$replace['#etatUntilDisplay#'] = 'none';
		honeyutils::logDebug("currentMode = $currentMode, state = " . json_encode($state));
		# permanent$$
		/*if ( $state->permanent == State::MODE_PERMANENT_ON && $currentMode != self::CODE_MODE_HEAT && $currentMode != self::CODE_VMODE_GEOFENCE ) {
			$replace['#etatModeImg#'] = 'override-active.png';
			$replace['#etatModeImgDisplay#'] = 'inline';
		}
		# delay
		else*/ if ( $state->permanent == State::MODE_PERMANENT_OFF ) {
			$replace['#etatModeImg#'] = 'temp-override.svg';
			$replace['#etatModeImgDisplay#'] = 'inline';
			// example : $aEtat[2] = "2018-01-28T23:00:00Z"
			$replace['#etatUntil#'] = $currentMode == self::CODE_VMODE_DAYOFF ? honeyutils::gmtToLocalDate($state->until) : honeyutils::gmtToLocalHM($state->until);
			$replace['#etatUntilFull#'] = honeyutils::gmtToLocalDateHMS($state->until);
			$replace['#etatUntilDisplay#'] = 'inline';
		}
		else {
			$replace['#etatModeImg#'] = 'empty.svg';
		}
	}

	function iGetThModes($currentMode,$scheduleType,$consigneInfos) {
		return array(
			"isOff" => $currentMode == self::CODE_MODE_OFF,
			"isEco" => false,
			"isAway" => false,
			"isDayOff" => $currentMode == self::CODE_VMODE_DAYOFF,
			"isCustom" => false,

		    "follow" => $consigneInfos == null ? false : $consigneInfos->status == self::NoHold,
		    "temporary" => $consigneInfos == null ? false : ($consigneInfos->status == self::TemporaryHold || $consigneInfos->status == self::HoldUntil),
		    "permanent" => $consigneInfos == null ? false : $consigneInfos->status == self::PermanentHold,

			"scheduling" => $currentMode == self::CODE_MODE_HEAT,

			"setThModes" => array(TH::SET_TH_MODE_PERMANENT => self::i18n("de façon permanente"),
								  TH::SET_TH_MODE_UNTIL_CURRENT_SCH => self::i18n("jusqu'à la fin de la programmation courante, soit {0}")
								  //,self::SET_TH_MODE_UNTIL_END_OF_PERIOD => self::i18n("jusqu'à la fin de la période courante")
								  ),

			"lblSchedule" => $scheduleType == HeatMode::SCHEDULE_TYPE_TIME ? self::i18n("Programmes hebdo.") :
				($scheduleType == HeatMode::SCHEDULE_TYPE_GEOFENCE ? self::i18n("Programmes geofence") : self::i18n("Programmes congés"))
			);
	}


	/* ******************** AJAX calls ******************** */

	static function ajaxSynchronizeTH($prefix,$resizeWhenSynchronize) {
		$execUnitId = rand(0,10000);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - Lyric::ajaxSynchronizeTH($prefix,$resizeWhenSynchronize,EUI=$execUnitId)");
		// 0.4.1 - now, a single request is authorized in a delay of 1mn (cache auto-removed after 1 minute, and of course in this function ending)
		$prevExecUnitId = honeyutils::getCacheData(self::CACHE_SYNCHRO_RUNNING);
		if ( $prevExecUnitId != '' && $prevExecUnitId > 0 ) {
			if ( honeyutils::isDebug() ) honeyutils::logDebug("OUT<< - Lyric::ajaxSynchronizeTH(EUI=$execUnitId) - request rejected due to potentially another one still running (EUI=$prevExecUnitId)");
			return null;
		}
		honeyutils::setCacheData(self::CACHE_SYNCHRO_RUNNING, $execUnitId, 60);

		$ret = lyric::syncTH($prefix,$resizeWhenSynchronize);
		
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - Lyric::ajaxSynchronizeTH(EUI=$execUnitId)");
		honeyutils::doCacheRemove(self::CACHE_SYNCHRO_RUNNING);
		return $ret;
	}


	/************************ Actions ****************************/

	function iSetMode($execUnitId,$locId,$codePair) {
		$codeParts = explode('§', $codePair);
		$codeMode = $codeParts[0];
		$fileId = $codeParts[1];
		$deviceId = self::getFirstEqu($locId)->getLogicalId();
		if ( honeyutils::isDebug() ) honeyutils::logDebug(">> Lyric->iSetMode($locId,$deviceId,$codeMode)...");
		// https://developer.honeywellhome.com/lyric/apis/post/devices/thermostats/%7BdeviceId%7D
		// curl -X POST -H 'Authorization: Bearer XXXXX' -H 'Content-type: application/json' -d 'xxxxx' "https://api.honeywell.com/v2/devices/thermostats/{deviceId}?apikey={ApiKey}&locationId={locId}"
		$authData = self::readAuthData();
		$header = array('Authorization: Bearer ' . $authData["access_token"],
						'Content-type: application/json');
		$uri = "v2/devices/thermostats/$deviceId?apikey=" . self::getApiKey() . "&locationId=$locId";

		if ( $codeMode == self::CODE_MODE_OFF ) {
			$data = array("mode" => "Off", "thermostatSetpointStatus"=>self::PermanentHold, "heatSetpoint"=>15, "coolSetpoint"=>15);
			$ret = self::postRequest($uri, $header, $data);
		} else if ( $codeMode == self::CODE_VMODE_GEOFENCE ) {
			$data = array("mode" => "Heat", "thermostatSetpointStatus"=>self::NoHold, "heatSetpoint"=>20, "coolSetpoint"=>20);
			$ret = self::postRequest($uri, $header, $data);
			if ( $ret[self::SUCCESS] ) {
				$success = Schedule::actionRestoreSchedule($this,$locId,array(self::ARG_FILE_ID=>$fileId),true);
				$ret = array(self::SUCCESS=>$success);
			}
		} else if ( $codeMode == self::CODE_MODE_HEAT ) {
			$data = array("mode" => "Heat", "thermostatSetpointStatus"=>self::NoHold, "heatSetpoint"=>20, "coolSetpoint"=>20);
			$ret = self::postRequest($uri, $header, $data);
			if ( $ret[self::SUCCESS] ) {
				$success = Schedule::actionRestoreSchedule($this,$locId,array(self::ARG_FILE_ID=>$fileId),true);
				$ret = array(self::SUCCESS=>$success);
			}
		} else {
			//$data = array("mode"=>"Heat", "thermostatSetpointStatus"=>self::VacationHold, "heatSetpoint"=>13, "coolSetpoint"=>13);
			//$ret = self::postRequest($uri, $header, $data);

			// https://lyric.alarmnet.com ou https://lyriccloud.alarmnet.com 
			// ThermostatVacationSetting = coolSetpoint=x,heatSetpoint=y,units=z
			// VacationNetworkManager.putVacationHoldV2
			// 		PUT api/v2/locations/%s/vacationHold ou api/locations/%s/vacationHold
			// 		Enabled=false[/true, VacationStart=oStringUTC(yyyy-MM-dd'T'HH:mm:ss), VacationEnd=oStringUTC(yyyy-MM-dd'T'HH:mm:ss),] vacationHoldSettings ou
			//			thermostatVacationHoldSettings={[ThermostatVacationSetting]}
			// OU
			// VacationNetworkManager.putVacationHoldForDeviceV2
			// 		PUT api/v2/locations/%s/devices/%s/vacationHold
			// 		Enabled=false[/true, VacationStart=toStringUTC(yyyy-MM-dd'T'HH:mm:ss), VacationEnd=toStringUTC(yyyy-MM-dd'T'HH:mm:ss),
			//			thermostatVacationHoldSetting={ThermostatVacationSetting}]
			$uri = "v2/locations/$locId/devices/$deviceId/vacationHold?apikey=" . self::getApiKey();
			$data = array("Enabled"=>true, "VacationStart"=>"2020-04-10'T'10:01:11", "VacationEnd"=>"2020-04-12'T'20:02:22",
						  "thermostatVacationHoldSetting"=>array("coolSetpoint"=>15, "heatSetpoint"=>16, "units"=>"C")
						);
			$ret = self::putRequest($uri, $header, $data);
			// OU
			// VacationNetworkManager.putTCCVacationHold
			// 		PUT api/locations/%s/devices/%s/TCCEndVacation
			// 		PutTCCEndVacationRequest :
			// 		mode=Off/Heat/Cool, heatSetPoint=x, coolSetPoint=y, thermostatSetpointStatus=NoHold,
			//			endTime=toStringUTC(yyyy-MM-dd'T'HH:mm:ss), vacationHoldDays=0
		}
		$ret["system"] = self::SYSTEM_LYRIC;
		
		honeyutils::logDebug("<< Lyric->iSetMode");
		return $ret;
	}

	function iRestoreSchedule($execUnitId,$locId,$scheduleToSend,$taskName) {
		honeyutils::logDebug("Lyric->iRestoreSchedule...");
		if ( count($scheduleToSend["zones"]) > 1 ) {
			return array(self::SUCCESS=>false, "code"=>2, "message"=>"Error : only one zone settable for Lyric");
		}
		$schedule = $scheduleToSend["zones"][0];
		$zoneId = $schedule["zoneId"];
		
		if ( $scheduleToSend["scheduleType"] == HeatMode::SCHEDULE_TYPE_GEOFENCE ) {
			$data = array("deviceID"=>$zoneId,
						  "scheduleType"=>"Geofence",
						  "geoFenceSchedule"=>$schedule["schedule"]["GeofenceSchedule"]
						  );
		} else if ( $scheduleToSend["scheduleType"] == HeatMode::SCHEDULE_TYPE_TIME ) {
			$days = array();
			foreach ( $schedule["schedule"]["DailySchedules"] as $data ) {
				$slices = array();
				foreach ( $data["Switchpoints"] as $slice ) {
					$slices[] = array("startTime"=>$slice["TimeOfDay"],
									  "heatSetPoint"=>honeyutils::C2F($slice["heatSetpoint"]),
									  "coolSetPoint"=>honeyutils::C2F($slice["heatSetpoint"]));
				}
				$days[] = array(
					"day"=>honeyutils::getECDayFromNumDay($data["DayOfWeek"]),
					"periods"=>$slices
					);
			}
			$data = array("deviceID"=>$zoneId,
						  "scheduleType"=>"Timed",
						  "timedSchedule"=>array("days"=>$days)
						  );
			honeyutils::logDebug(">>>> Schedule To Send " . json_encode($data));
		} else {	// SCHEDULE_TYPE_VACANCY
			return array(self::SUCCESS=>false, "code"=>3, "message"=>"Error : schedule type not suppported yet");
		}
		
		// https://developer.honeywellhome.com/lyric/apis/post/devices/schedule/%7BdeviceId%7D
		// curl -X POST -H 'Authorization: Bearer XXXXX' -H 'Content-type: application/json' -d 'xxxxx' "https://api.honeywell.com/v2//devices/schedule/{deviceId}?apikey={ApiKey}&locationId={locId}"
		$authData = self::readAuthData();
		$header = array('Authorization: Bearer ' . $authData["access_token"],
						'Content-type: application/json');
		$uri = "v2/devices/schedule/$zoneId?apikey=" . self::getApiKey() . "&locationId=$locId&type=regular";
		$ret = self::postRequest($uri, $header, $data);
		$ret["system"] = self::SYSTEM_LYRIC;
		return $ret;
	}

	function iGetFPT() {
		return array(
			"follow"=>self::NoHold,
			"permanent"=>self::PermanentHold,
			"temporary"=>self::TemporaryHold
		);
	}

	function iSetConsigne($execUnitId,$locId,$zoneId,$parameters,$taskName) {
		honeyutils::logDebug("Lyric->iSetConsigne...");
		// https://developer.honeywellhome.com/lyric/apis/post/devices/thermostats/%7BdeviceId%7D
		// curl -X POST -H 'Authorization: Bearer XXXXX' -H 'Content-type: application/json' -d 'xxxxx' "https://api.honeywell.com/v2/devices/thermostats/{deviceId}?apikey={ApiKey}&locationId={locId}"
		$authData = self::readAuthData();
		$header = array('Authorization: Bearer ' . $authData["access_token"],
						'Content-type: application/json');
		$uri = "v2/devices/thermostats/$zoneId?apikey=" . self::getApiKey() . "&locationId=$locId";

		// $parameters['mode'] = mode : 'auto' ; 'manuel'
		// $parameters['zoneId'] = zoneId (always String)
		// $parameters['value'] = value to set ; beware of 0 as means : return to scheduled value
		// $parameters['realValue'] = for display, or, if newValue=0 is not recognized, to replace this one
		// $parameters['until'] = HH:MM:SS or null (NB not 'null') if PermanentOverride
		// $parameters['status'] = NoHold, PermanentHold, TemporaryHold (iGetFPT)
		//$tss = 'NoHold';
		//$parameters['value'] = 0;
		$tss = $parameters['status'];
		/* thermostatSetpointStatus :
		"NoHold" 		will return to schedule
		"TemporaryHold" will hold the set temperature until "nextPeriodTime" (vu dans les tests)
			OR No "nextPeriodTime" is required, the thermostat will hold the temperature until the next schedule period change.
		"PermanentHold" will hold the setpoint until user requests another change
		"HoldUntil" - Requires a "nextPeriodTime" value, the thermostat will hold the requested setpoint(s) until that time.
		*/
		$data = array("mode" => "Heat", "thermostatSetpointStatus" => $tss, "heatSetpoint"=>$parameters['value'], "coolSetpoint"=>15);
		if ( $tss == self::TemporaryHold ) {
			$data['nextPeriodTime'] = $parameters['until'];
		}
		return self::postRequest($uri, $header, $data);
	}

}
