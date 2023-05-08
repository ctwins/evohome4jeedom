<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once 'honeyutils.php';
require_once 'structures.php';
require_once 'modules/console.class.php';
require_once 'modules/thermostat.class.php';
require_once 'modules/schedule.class.php';

/**
 * This class appears with version 0.5.0, and content is large parts from original evohome.class.php
 * It is the parent of evohome.class.php (still the entry point from jeedom, and lyric.php)
 * 
 * @author ctwins95
 *
 */
abstract class honeywell extends eqLogic {
    const PLUGIN_NAME = "evohome";
    const RELOAD = true;

	const PYTHON = 'python3';
    const PYTHON_VERSION = 3;

	const CONF_HNW_SYSTEM = "hnwSystem";
	const SYSTEM_EVOHOME = 'EVOHOME';
	const SYSTEM_LYRIC = 'LYRIC';

	const CONF_TYPE_EQU = 'typeEqu';
	const TYPE_EQU_CONSOLE = 'C';
	const TYPE_EQU_THERMOSTAT = 'TH';
	const CONF_LOC_ID = 'locationId';
	const CFG_LOCATION_DEFAULT_ID = -1;
	const iCFG_SCHEDULE_ID = 'intScheduleFileId';

	const CONF_MODEL_TYPE = 'modelType';
	const CONF_ALLOWED_SYSTEM_MODE = 'allowedSystemMode';
	const ID_NO_ZONE = -2;

	const CFG_LOADING_INTERVAL  = 'evoLoadingInterval';
	const CFG_LOADING_SYNC  = 'evoLoadingSync';
	const CFG_SCENARIO = 'evoScenario';
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

	const CFG_ACCURACY  = 'evoDecimalsNumber';
	const CFG_ACC_HNW  = 1;
	const CFG_ACC_05  = 2;
	const CFG_ACC_005  = 3;
	const CFG_ACC_NATIVE  = 4;
	const CFG_TEMP_UNIT = 'evoTempUnit';
	const CFG_UNIT_CELSIUS = 'C';
	const CFG_UNIT_FAHRENHEIT = 'F';

	// Caches names
	const CACHE_CRON_TIMER = 'cronTimer';
	const CACHE_CRON_ACTIF = 'cronActive';
	const CACHE_IAZ = 'evohomegetInformationsAllZonesE2';
	const CACHE_INFOS_API = 'evohomeInfosApi';
	const CACHE_IAZ_DURATION = 86400;
	const CACHE_IAZ_RUNNING = 'getInformationsAllZonesE2Running';
	const CACHE_STATES_DURATION = 30;
	const CACHE_STATES = 'evohomeStates';
	//const CACHE_CURRENT_SCHEDULE = 'evohomeCurrentSchedule';
	const CACHE_PYTHON_RUNNING = 'PYTHON_RUNNING';
	const CACHE_STAT_PREV_VISIBLE = 'STAT_PREV_VISIBLE';
	const CACHE_SCHEDULE_DELTA = 'SCHEDULE_DELTA';
	const SCHEDULE_DELTA_ON = 'SCHEDULE_DELTA_ON';
	const SCHEDULE_DELTA_OFF = 'SCHEDULE_DELTA_OFF';
	const CACHE_SYNCHRO_RUNNING = "SYNCHRO_RUNNING";
	const SUCCESS = 'success';
	
	# -- infosAPI (common) :
	const IZ_TIMESTAMP = 'timestamp';
	const IZ_API_V1 = 'apiV1';
	const IZ_GATEWAY_CNX_LOST = 'cnxLost';

	const ARG_LOC_ID = 'locId';
	const ARG_CODE_MODE = 'select';	// 0.2.1 : 'codeMode' replaced by 'select' for compatibility with parameter of scenario
	const ARG_FILE_NAME = 'fileName';
	const ARG_FILE_ID = 'select';	// 0.3.2 : fix (was fileId) for compatibility with scenario select field
	const ARG_FILE_REM = 'remark';
	const ARG_ZONE_ID = 'zoneId';
	const ARG_FILE_NEW_SCHEDULE = 'scheduleData';
	const ARG_CONSIGNES_DATA = 'select';

	const BG = array('#247eb2', '#2e9985', '#fa9e2d', '#ff5b1a', '#f21f1f');
	const C2BG = array(25=>'#f21f1f',
					   22=>'#ff5b1a',
					   19=>'#fa9e2d',
					   16=>'#2e9985',
					    0=>self::BG[0]);
	const F2BG = array((25* 9/5 + 32)=>'#f21f1f',
					   (22* 9/5 + 32)=>'#ff5b1a',
					   (19* 9/5 + 32)=>'#fa9e2d',
					   (16* 9/5 + 32)=>'#2e9985',
					                0=>'#247eb2');

	static function i18n($txt, $arg=null) {
	    return honeyutils::i18n($txt, "plugins/".self::PLUGIN_NAME."/core/class/evohome.class.php", $arg);
	}

	static function setCron($cron) {
		$cron->setClass(self::PLUGIN_NAME);
		$cron->setFunction('main_refresh');
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule("*/20 * * * * *");
		$cron->save();
	}

	static function hnw_install() {
	    $cron = cron::byClassAndFunction(self::PLUGIN_NAME, 'main_refresh');
		if (!is_object($cron)) {
			$cron = new cron();
			self::setCron($cron);
		}
	}
	static function hnw_update() {
	    foreach (self::getEquipments() as $eqLogic) {
	        // When HNW_SYSTEM is unset, that means it's an old installation which concern Evohome system only
	        if ( $eqLogic->getConfiguration(honeywell::CONF_HNW_SYSTEM,'unset') == 'unset') {
	            $eqLogic->setConfiguration(honeywell::CONF_HNW_SYSTEM, honeywell::SYSTEM_EVOHOME);
	            $eqLogic->save();
	        }
	    }
	    $cron = cron::byClassAndFunction(self::PLUGIN_NAME, 'main_refresh');
	    if (!is_object($cron)) {
	        $cron = new cron();
	        self::setCron($cron);
	    }
	}
	static function hnw_remove() {
	    $cron = cron::byClassAndFunction(self::PLUGIN_NAME, 'main_refresh');
		if (is_object($cron)) {
			$cron->stop();
			$cron->remove();
		}
	}

	function createOrUpdateCmd($order, $logicalId, $name, $type, $subType, $isVisible, $isHistorized) {
		$cmd = $this->getCmd(null, $logicalId);
		$created = false;
		if (is_object($cmd) && $cmd->getSubType() != $subType) {
			// 0.2.1 : createOrUpdateCmd replace MODE/RESTORE cmd
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
		if ( $logicalId == TH::CMD_SET_CONSIGNE_ID ) {
			// 0.4.1 - become a default setting, before reading real values of min/max with "injectInformationsFromZone"
			TH::fillSetConsigneData($cmd,$this->getLogicalId(),self::adjustbyUnit(5,self::CFG_UNIT_CELSIUS),self::adjustbyUnit(25,self::CFG_UNIT_CELSIUS));
		// 0.4.2 - previous infos was not "appli mobile" compliant
		} else if ( $logicalId == TH::CMD_TEMPERATURE_ID ) {
			//$cmd->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
			$cmd->setDisplay('generic_type', 'TEMPERATURE');
			$cmd->setGeneric_type('TEMPERATURE');
			$cmd->setUnite("°");
		} else if ( $logicalId == TH::CMD_CONSIGNE_ID || $logicalId == TH::CMD_SCH_CONSIGNE_ID ) {
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
		// flags for the honeywell.js
		$cmd->setConfiguration('canBeVisible', $isVisible);
		$cmd->setConfiguration('canBeHistorize', $isHistorized);
		$cmd->save();

		return $created;
	}

	public function preUpdate() {
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			Console::preUpdate($this);
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
		honeyutils::logDebug("postSave");
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			Console::postSave($this);
		} else /*if ($this->getLogicalId() > 0)*/ {
			TH::postSave($this);
		}

		$infosZones = $this->getInformations();
		$this->injectInformationsFromZone($infosZones);

		if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT - postSave(' . $this->getLogicalId() . ')'); 

		return true;
	}

	/*********************** Méthodes d'instance **************************/

	public function postRemove() {
	}

	function getScheduleAndStates($locId) {
		$cachedData = honeyutils::getCacheData(self::CACHE_STATES,$locId);
		$refreshCache = false;
		$scheduleCurrent = $this->getToHtmlProperty("scheduleCurrent");
		//honeyutils::logDebug("-- private scheduleCurrent ? " . (is_array($scheduleCurrent) ? "yes" : "no"));
		if ( !is_array($scheduleCurrent) ) {
			if ( is_array($cachedData) && array_key_exists('scheduleCurrent',$cachedData) ) {
				//honeyutils::logDebug("use cachedData for scheduleCurrent");
				$scheduleCurrent = $cachedData['scheduleCurrent'];
			}
			if ( !is_array($scheduleCurrent) ) {
			    $scheduleCurrent = Schedule::getSchedule($locId);
			    $refreshCache = true;
			}
		}

		// settings depending of the "states" vars :
		$aStates = $this->getToHtmlProperty("states");
		//honeyutils::logDebug("-- private states ? " . (is_array($aStates) ? "yes" : "no"));
		if ( !is_array($aStates) ) {
			if ( is_array($cachedData) && array_key_exists('states',$cachedData) ) {
				//honeyutils::logDebug("use cachedData for states");
				$aStates = $cachedData['states'];
			}
			if ( !is_array($aStates) ) {
			    $aStates = ReadStates::getStates($locId,$this->getInformations());
			    $refreshCache = true;
			}
		}
		
		$data = array("states"=>$aStates, "scheduleCurrent"=>$scheduleCurrent);
		if ( $refreshCache ) {
			honeyutils::setCacheData(self::CACHE_STATES, $data, self::CACHE_STATES_DURATION, $locId);
		}
		return $data;
	}
	
 	public function toHtml($pVersion='dashboard') {
		honeyutils::doCacheRemove('evohomeWidget' . $pVersion . $this->getId());
		$locId = $this->getLocationId();
		$typeEqu = $this->getConfiguration(self::CONF_TYPE_EQU);
		//$zoneId = $this->getLogicalId();
		//honeyutils::logDebug("IN>> toHtml($pVersion) lid=$locId, zid= $zoneId (" . $this->getName() . ")");

		$replace = $this->preToHtml($pVersion);
		if (!is_array($replace)) return $replace;

		$version = jeedom::versionAlias($pVersion);

		$tmp = $this->getScheduleAndStates($locId);
		$scheduleCurrent = $tmp['scheduleCurrent'];
		$aStates = $tmp['states'];

		$msgInfo = $this->getToHtmlProperty("msgInfo");

		$taskIsRunning = $this->getToHtmlProperty("taskIsRunning");
		if ( is_null($taskIsRunning) ) $taskIsRunning = false;

		if ( $typeEqu == self::TYPE_EQU_CONSOLE ) {
			Console::toHtml($this,$pVersion,$version,$replace,$scheduleCurrent);
		}
		else {
			// TH WIDGET
			TH::toHtml($this,$pVersion,$version,$replace,$scheduleCurrent);
		}

		// single usage :
		$this->removeToHtmlProperties();

		$replace['#taskIsRunning#'] = $taskIsRunning ? "true" : "false";
		$replace['#evoBackgroundColor#'] = '#F6F6FF';
		$replace['#evoCmdBackgroundColor#'] = '#3498db';
		if ( jeedom::version() < 4 ) {
			$replace['#new-background-color#'] = 'background-color:#F6F6FF !important;';
		}

		$stateUnread = $aStates[ReadStates::STATE_UNREAD];
		$stateCnxLost = $aStates[ReadStates::STATE_CNX_LOST];
		$stateIsRunning = $aStates[ReadStates::STATE_IS_RUNNING];
		$stateLastRead = $aStates[ReadStates::STATE_LAST_READ];
		$stateIsAccurate = $aStates[ReadStates::STATE_IS_ACCURATE];
		$stateCronActive = $aStates[ReadStates::STATE_CRON_ACTIVE];
		$replace['#apiAvailable#'] = !$stateUnread && $stateCnxLost == '' ? "true" : "false";
		$replace['#msgApiUnavailable#'] = self::i18n("Fonction indisponible (erreur en API)");
		$replace['#evoTemperatureColor#'] = $stateUnread ? 'gray' : 'black';
		$replace['#evoConsigneColor#'] = $stateUnread ? 'lightgray' : 'white';
		$replace['#iazColorState#'] = $stateIsRunning ? 'crimson' : ($stateUnread || $stateCnxLost != '' ? 'red' : ($stateIsAccurate || honeyutils::getParam(self::CFG_ACCURACY,self::CFG_ACC_HNW) == self::CFG_ACC_HNW ? 'lightgreen' : 'coral'));
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
			$addTime = honeyutils::getCacheRemaining(self::CACHE_CRON_TIMER);
			if ( $addTime <= 5 ) $addTime = self::getLoadingInterval() * 60;
			$tsNext = time() + $addTime;
			if ( honeyutils::getParam(self::CFG_LOADING_SYNC,0) == 1 ) {
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

		$replace['#pluginName#'] = self::PLUGIN_NAME;
		$replace['#dashboard_min_js_size#'] = filemtime(dirname(__FILE__) . '/../template/dashboard/dashboard_min.js');
		$replace['#dashboard_css_size#'] = filemtime(dirname(__FILE__) . '/../template/dashboard/dashboard.css');

		$html = template_replace($replace, getTemplate('core', $version, 'honeywell', self::PLUGIN_NAME));
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		//honeyutils::logDebug("<<OUT - toHtml");
		return $html;
	}

	function getLocationId() {
		return $this->getConfiguration(self::CONF_LOC_ID, self::CFG_LOCATION_DEFAULT_ID);
	}

	function iRefreshComponent($infosZones=null,$inject=false) {
		honeyutils::logDebug("IN>> refreshComponent");
		if ( is_array($infosZones) && $inject ) {
			$this->injectInformationsFromZone($infosZones);
		}
		$this->refreshWidget();	// does the toHtml by event (in another Thread, so the cache usage)
		honeyutils::logDebug("<<OUT refreshComponent");
	}

	function setAllowedSystemModes($asmList) {
		$allowedModes = array();
		foreach ($asmList as $asm) {
			if ( ($code = $this->getModeFromHName($asm)) !== null ) {
				$allowedModes[] = $code;
			}
		}
		$this->setConfiguration(self::CONF_ALLOWED_SYSTEM_MODE, $allowedModes);
	}

	function getModeFromHName($hName) {
		$modesArray = $this->getModesArray();
		foreach ( $modesArray as $code=>$heatMode ) {
			if ( $heatMode->mode == $hName ) return $code;
		}
		// NB : 'AutoWithReset' seems == Auto (sent with SetModeE2, data retrieve = Auto)
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getModeFromHName($hName) not found => null");
		return null;
	}

	function getModeName($etatCode,$full=false) {
		//honeyutils::logDebug("getModeName($etatCode)");
		$modesArray = $this->getModesArray();
		return $modesArray[$etatCode]->modeSettable || $full ? $modesArray[$etatCode]->label : null;
	}
	
	function getEtatImg($etatCode) {
		$modesArray = $this->getModesArray();
		return $modesArray[$etatCode]->img;
	}

	function getJSModesArray($currentMode,$availableTypeSchedule) {
		$ma = array();
		foreach ( $this->getModesArray() as $code=>$heatMode ) {
			if ( $heatMode->scheduleType == null || $code == $currentMode || array_key_exists($heatMode->scheduleType,$availableTypeSchedule) ) {
				$ma[] = array($code, $heatMode->img, $heatMode->label, $heatMode->scheduleType, $heatMode->modeSettable);
			}
		}
		//honeyutils::logDebug("getJSModesArray($currentMode) = " . json_encode($ma));
		return $ma;
	}

	// $razMinMax = true for a manual command (RUF)
	function injectInformationsFromZone($infosZones, $razMinMax=false) {
		if ( !is_array($infosZones) ) {
			return;
		}
		$zoneId = $this->getLogicalId();
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - injectInformationsFromZone on zone $zoneId");
		if ( $zoneId == self::ID_NO_ZONE ) {
			honeyutils::logError("<<OUT - injectInformationsFromZone - zone undefined ; nothing to do");
			return;
		}
		$locId = $this->getLocationId();
		if ( $this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE ) {
			Console::injectInformations($this,$infosZones);
		} else {
			TH::injectInformations($this,$infosZones,$zoneId);
		}
		honeyutils::logDebug("<<OUT - injectInformationsFromZone");
	}

	function getToHtmlDataKey() {
	    return 'toHtmlData_' . $this->getLocationId() . "_" . $this->getLogicalId();
	}

	function setToHtmlProperties($pStates,$pScheduleCurrent,$pMsgInfo,$pTaskIsRunning=false,$pConsigne=null) {
		honeyutils::setCacheData($this->getToHtmlDataKey(),
			array("states"=>$pStates,
			      "scheduleCurrent"=>$pScheduleCurrent,
			      "msgInfo"=>$pMsgInfo,
			      "taskIsRunning"=>$pTaskIsRunning,
			      "forcedConsigne"=>$pConsigne)
		    );
	}

	function setMsgInfo($msgInfo) {
		$key = $this->getToHtmlDataKey();
		$zData = honeyutils::getCacheData($key);
		if ( is_array($zData) /*&& array_key_exists("xx",$zData)*/ ) {
		    $zData["msgInfo"] = $msgInfo;
			honeyutils::setCacheData($key, $zData);
		}
	}
	
	function getToHtmlProperty($name) {
		$zData = honeyutils::getCacheData($this->getToHtmlDataKey());
		$ret = (!is_array($zData) || !array_key_exists($name,$zData)) ? null : $zData[$name];
		if ( $ret != null && $name == 'scheduleCurrent' ) {
			$ret = Schedule::adaptScheduleZoneIdToString($ret);
		}
		return $ret;
	}
	
	function removeToHtmlProperties() {
		$lId = $this->getLocationId();
		$zId = $this->getLogicalId();
		honeyutils::doCacheRemove('toHtmlData_'.$lId."_".$zId);
	}


	/* ******************** Static ******************** */

	static public function cmpName($a,$b) {
		return strcmp($a['name'], $b['name']);
	}

	static function isModelEvohome($modelType) {
		return $modelType == self::MODEL_TYPE_HEATING_ZONE || $modelType == self::MODEL_TYPE_ROUND_WIRELESS;
	}

	static function adjustByUnit($temp, $unitsFrom, $delta=false) {
		return self::adjustByUnit2($temp, $unitsFrom, honeyutils::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS), $delta);
	}
	
	static function adjustByUnit2($temp, $unitsFrom, $unitsTo, $delta=false) {
		if ( $temp == null ) return null;
		$unitsFrom = substr($unitsFrom,0,1);
		$unitsTo = substr($unitsTo,0,1);
		if ( $unitsFrom == $unitsTo ) return $temp;
		// >> Celsius > Fahrenheit
		if ( $unitsFrom == self::CFG_UNIT_CELSIUS ) return honeyutils::C2F($temp,$delta);
		// >> Fahrenheit > Celsius
		if ( $unitsFrom == self::CFG_UNIT_FAHRENHEIT ) return honeyutils::F2C($temp,$delta);
	}
	
	// revert conversion : used by Set Consigne, as this function receive converted values
	static function revertAdjustByUnit($temp, $unitsFrom) {
		if ( $temp == null ) return null;
		$unitsFrom = substr($unitsFrom,0,1);
		if ( $unitsFrom == honeyutils::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) ) return $temp;
		// >> Fahrenheit > Celsius
		if ( $unitsFrom == self::CFG_UNIT_CELSIUS ) return honeyutils::F2C($temp);
		// >> Celsius > Fahrenheit
		if ( $unitsFrom == self::CFG_UNIT_FAHRENHEIT ) return honeyutils::C2F($temp);
	}

	static function applyRounding($temperatureNative,$mode=null) {
		$valRound = round($temperatureNative*100)/100;
		switch ( $mode == null ? honeyutils::getParam(self::CFG_ACCURACY,self::CFG_ACC_HNW) : $mode ) {
			case self::CFG_ACC_HNW:
			list($entier, $decRound) = explode('.', number_format($valRound,2));
			// ceil to 0.5 (EvoHome native computation)
			//if ( $decRound >= 50 ) $dec50 = 0.5; else $dec50 = 0;
			if ( $decRound >= 75 ) {
				$dec50 = 0;
				$entier++;
			} else if ( $decRound < 50 ) {
				$dec50 = 0;
			} else {
				$dec50 = 0.5;
			}
			return number_format($entier + $dec50, 1);

			case self::CFG_ACC_05:
			// classical round to 0.5
			return number_format(round($temperatureNative*2)/2,1);

			case self::CFG_ACC_005:
			// classical round to 0.05
			return number_format(round($temperatureNative*20)/20,2);

			//case self::CFG_ACC_NATIVE:
		}
		return number_format($valRound,2);
	}

	static function activateIAZReentrance($delay) {
		honeyutils::setCacheData(self::CACHE_IAZ_RUNNING, "true", $delay);
	}
	static function isIAZrunning() {
		return honeyutils::getCacheData(self::CACHE_IAZ_RUNNING) != '';
	}
	static function deactivateIAZReentrance($locId='') {
		honeyutils::doCacheRemove(self::CACHE_IAZ_RUNNING);
		honeyutils::doCacheRemove(self::CACHE_STATES,$locId);
	}
	static function waitingIAZReentrance($caller) {
		$isRunning = false;
 		while ( self::isIAZrunning() ) {
			if ( honeyutils::isDebug() ) honeyutils::logDebug("waitingIAZReentrance($caller) 5sec");
			sleep(5);
			$isRunning = true;
		}
		return $isRunning;
	}

	public static function dependancy_info() {
		$ret = array();
		$ret['log'] =  'evohome_update';
		$ret['state'] = 'ok';
		$ret['progress_file'] = jeedom::getTmpFolder(self::PLUGIN_NAME) . '/dependance';

		// 0.4.2 - change dependency check
		// 0.4.3 - change 2>nul >> 2>dev/null (thanks github/titidnh)
        // 0.5.7 - check against python version
        $pythonRequests = self::PYTHON_VERSION == 2 ? 'python-requests' : 'python3-requests';
		if ( !self::checkDependancy('dpkg-query --show ' . $pythonRequests . ' 2>/dev/null') ) {
			$ret['state'] = 'nok';
		}
		if ( !self::checkDependancy(system::get('cmd_check') . ' gd | grep php') ) {
			$ret['state'] = 'nok';
		}

		return $ret;
	}

    static function checkDependancy($dep) {
      $cmd = system::getCmdSudo() . $dep;
      $r = exec($cmd);
	  if ( honeyutils::isDebug() ) honeyutils::logDebug("checkDependancy [$cmd] = [$r]");
      return $r != '';
    }

	static function setPythonRunning($name) {
		honeyutils::setCacheData(self::CACHE_PYTHON_RUNNING, $name, 86400);
	}
	static function razPythonRunning() {
		honeyutils::doCacheRemove(self::CACHE_PYTHON_RUNNING);
	}

	static function getEquipments() {
	    return eqLogic::byType(self::PLUGIN_NAME);
	}

	static function getEquipmentsForLoc($locId) {
	    $equipments = array();
	    foreach (self::getEquipments() as $equ) {
	        if ( $equ->getLocationId() == $locId) {
	            $equipments[] = $equ;
	        }
	    }
	    return $equipments;
	}

	static function getOneEquByLocation() {
		$locIdAndEqus = array();
		foreach (self::getEquipments() as $equ) {
			if ( $equ->getIsVisible() && $equ->getIsEnable() ) {
				$locId = $equ->getLocationId();
				// don't keep console equ (for Lyric.setMode)
              	if ( $equ->getLogicalId() != $locId && !array_key_exists($locId,$locIdAndEqus) ) {
					$locIdAndEqus[$locId] = $equ;
                }
			}
		}
		//honeyutils::logDebug("getOneEquByLocation = " . json_encode($locIdAndEqus));
		return $locIdAndEqus;
	}
	
	static function getFirstEqu($locId) {
		$ret = self::getOneEquByLocation()[$locId];
		honeyutils::logDebug("getFirstEqu($locId) = " . $ret->getName());
		return $ret;
	}

	static function getComponent($zoneOrLocId) {
		foreach (self::getEquipments() as $equ) {
			// NB : zoneOrLocId ==> zoneId for a TH component ; locationId for a Console component
			if ( $equ->getLogicalId() == $zoneOrLocId ) {
				return $equ;
			}
		}
		return null;
	}
	
	public static function getEquNamesAndId($locId) {
		$table = array();
		foreach (self::getEquipmentsForLoc($locId) as $equ) {
			$table[$equ->getLogicalId()] = $equ->getName();
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getEquNamesAndId($locId) : " . json_encode($table));
		return $table;
	}

	static function getLoadingInterval() {
		return intVal(honeyutils::getParam(self::CFG_LOADING_INTERVAL,10));
	}

	static function isCronActive() {
		return /*config::byKey('functionality::cron::enable', 'evohome', 1) == 1 &&*/ honeyutils::getCacheData(self::CACHE_CRON_ACTIF) == "1";
	}
	
	public static function cron() {
		honeyutils::logDebug('IN>> - cron : ' . cache::byKey('plugin::cron::inprogress')->getValue(0));
		try {
			honeyutils::setCacheData(self::CACHE_CRON_ACTIF, "1", 62);
			$forcage = false;	// for tests only
			if ( $forcage ) {
				self::deactivateIAZReentrance();
				self::razPythonRunning();
			}
			if ( self::isIAZrunning() ) {
				honeyutils::logDebug('<<OUT - cron - reading still running. exit now');
				return;
			}
			$mark = honeyutils::getCacheData(self::CACHE_CRON_TIMER);
			$tsRemain = honeyutils::getCacheRemaining(self::CACHE_CRON_TIMER);
			if ( $forcage || $mark == '' || $tsRemain <= 5 ) {
				if ( !$forcage && honeyutils::getParam(self::CFG_LOADING_SYNC,0) == 1 ) {
					// adjust fine time :
					$interval = self::getLoadingInterval();
					$min = intVal(date("i"));
					if ( $min % $interval != 0 ) {
						// 10 = 0/10/20/30/40/50
						// 15 = 0/15/30/45
						// 20 = 0/20/40
						// 30 = 0/30
						// So, we adjust by checking : currentMin % interval == 0
						honeyutils::logDebug("<<OUT - cron - synchronize interval ($interval) on time (current $min)");
						return;
					}
					honeyutils::logDebug("synchronize time is requested and was right ;)");
				}
				$di = self::dependancy_info();
				if ( $di['state'] != 'ok' ) {
					honeyutils::logDebug('<<OUT - cron - plugin not ready (dependency_info=NOK)');
				} else {
					$oneEquByLoc = self::getOneEquByLocation();
					if ( count($oneEquByLoc) == 0 ) {
						honeyutils::logDebug('<<OUT - cron - warning, no equipment activated/visible');
					} else {
						$td = time();
						foreach ( $oneEquByLoc as $locId=>$equ ) {
							if ( honeyutils::isDebug() ) {
								// warning level to enforce reporting
								log::add('cron_execution', 'warning', "Launching getInformations with refresh on locId=$locId");
							}
							$equ->getInformations(true);
						}
						$delay = time() - $td;
						self::launchScenarioPostMeasure();
					}
					$cacheDuration = self::getLoadingInterval()*60 - $delay - 2;
					honeyutils::setCacheData(self::CACHE_CRON_TIMER, "dummy", $cacheDuration);
				}
			} else if ( honeyutils::isDebug() ) {
				honeyutils::logDebug("cron : wait for $tsRemain sec.");
			}
		} catch (Exception $e ) {
			honeyutils::logError('Exception while cron');
		}
		honeyutils::logDebug('<<OUT - cron');
	}

	static function refreshAllForLoc($locId,$infosZones,$inject=false,$msgInfo='',$taskIsRunning=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - refreshAllForLoc($locId)");
		$aStates = ReadStates::getStates($locId,$infosZones);
		$scheduleCurrent = Schedule::getSchedule($locId);
		foreach (self::getEquipmentsForLoc($locId) as $equ) {
			// NB : $taskIsRunning should be set on console only
			$equ->setToHtmlProperties($aStates,$scheduleCurrent,$msgInfo,$taskIsRunning && $equ->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE);
			$msgInfo = '';	// set only on the first equ
			$equ->iRefreshComponent($infosZones,$inject);
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - refreshAllForLoc($locId)");
	}

	static function getInformationsForLoc($locId,$doRefresh=false) {
		$equ = Console::getConsole($locId);
		return $equ->getInformations($doRefresh);
	}

	public static function getBackColorForTemp($temp,$isOff=false) {
		if ( $temp == null ) return 'lightgray';
		if ( $isOff ) return 'black';
		$X2BG = honeyutils::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) == self::CFG_UNIT_CELSIUS ? self::C2BG : self::F2BG;
		foreach ( $X2BG as $ref=>$bgRef ) {
			if ($temp >= $ref) {
				$bg = $bgRef;
				break;
			}
		}
		return $bg;
	}

	static function refreshComponent($data, $msgInfo='') {
		$zoneId = $data['zoneId'];
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - refreshComponent($zoneId)");
		$comp = self::getComponent($zoneId);
		if ( $comp != null ) {
			$locId = $comp->getLocationId();
			$aState = ReadStates::getStates($locId,$comp->getInformations());
			$scheduleCurrent = Schedule::getSchedule($locId);
			$consigne = array_key_exists('consigne',$data) ? $data['consigne'] : null;
			$comp->setToHtmlProperties($aState,$scheduleCurrent,$msgInfo,$data['taskIsRunning'],$consigne);
			$comp->iRefreshComponent();
		}
		honeyutils::logDebug('<<OUT - refreshComponent');
	}


	/* ******************** ROUTAGE via méthode instance / hnwSystem / static vers les classes idoines ******************** */

	function getHnwSystem() {
		return $this->getConfiguration(self::CONF_HNW_SYSTEM,self::SYSTEM_EVOHOME);
	}
	function isEvohome() {
		return $this->getHnwSystem() == self::SYSTEM_EVOHOME;
	}
	function isLyric() {
		return $this->getHnwSystem() == self::SYSTEM_LYRIC;
	}
	function iGetInstance() {
		// NB : as $this is mostly an instance of 'evohome' (can only be 'lyric' when creating component),
		// so, we use the configuration parameter to determine the hnwSystem :
		$hnwSystem = $this->getHnwSystem();
		if ( $hnwSystem == self::SYSTEM_EVOHOME ) {
			require_once 'evohome.class.php';
			return new evohome();
		}
		if ( $hnwSystem == self::SYSTEM_LYRIC ) {
		    require_once 'lyric.php';
			return new lyric();
		}
	}

	function getInformations($doRefresh=false, $readSchedule=true, $msgInfo='', $taskIsRunning=false) {
		return ($this->iGetInstance())->iGetInformations($this->getLocationId(),$doRefresh, $readSchedule, $msgInfo, $taskIsRunning);
	}

	function getModesArray() {
		return ($this->iGetInstance())->iGetModesArray();
	}

	function getThModes($currentMode,$scheduleType,$consigneInfos=null) {
		return ($this->iGetInstance())->iGetThModes($currentMode,$scheduleType,$consigneInfos);
	}


	/* ******************** AJAX calls ******************** */

	static function ajaxChangeStatScope($locId,$newStatScope) {
		$equ = Console::getConsole($locId);
		if ( $equ != null ) {
			$cmd = $equ->getCmd(null,Console::CMD_STATISTICS_ID);
			if ( is_object($cmd) ) {
				$cmd->event($newStatScope);
				self::refreshAllForLoc($locId,$equ->getInformations());
			}
		}
	}

	static function ajaxRefresh($locId) {
		$equ = Console::getConsole($locId);
		if ( $equ != null ) {
			$equ->getInformations(true);
		}
	}


	/* ******************** Scenarios ******************** */

	static function getAllScenarios() {
		$list = array();
		foreach (scenario::all() as $scenar) {
			$list[] = array("id"=>$scenar->getId(), "name"=>$scenar->getName());
		}
		usort($list, "honeywell::cmpName");
		return $list;
	}
	
	static function launchScenarioPostMeasure() {
		$id = honeyutils::getParam(self::CFG_SCENARIO,0);
		if ( $id > 0 ) {
			self::launchScenario($id);
		}
	}
	
	static function launchScenario($id) {
		$scenario = scenario::byId($id);
		if (!is_object($scenario)) {
			throw new Exception(self::i18n("Aucun scénario d'ID : {0}", $id));
		}
		$msg = self::i18n(">>> Lancement du scénario : {0}", $scenario->getName());
		honeyutils::logDebug($msg);
		$ret = $scenario->launch('api', self::i18n("Lancement du scénario {0} par le plugin {1}", $id, self::PLUGIN_NAME));
		if ( !is_string($ret) ) {
			$ret = json_encode($ret);
		}
		honeyutils::logDebug("<<< Résultat scénario : $ret");
	}


	/* ******************** Actions ******************** */

	function doCaseAction($paramAction, $parameters) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("doCaseAction($paramAction," . json_encode($parameters) . ")");
		$locId = $this->getLocationId();
		return Schedule::doAction($this,$locId,$paramAction,$parameters) ||
			   Console::doAction($this,$locId,$paramAction,$parameters) ||
			   TH::doAction($this,$locId,$paramAction,$parameters);
	}


	/*	* ************************* CRON parts ****************************** */

	/* see hnw_install() above ; Called by programmatic cron via evohome.class.php/main_refresh */
	/* NB : will be called directly when plugin will be renamed */
	public static function honeywell_refresh() {
		if ( honeyutils::getParam(self::CONF_HNW_SYSTEM,'') == self::SYSTEM_LYRIC ) {
		    require_once 'lyric.php';
			lyric::lyric_refresh();
		}
	}

}