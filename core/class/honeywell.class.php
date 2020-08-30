<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once 'honeyutils.php';
require_once 'HeatMode.php';

abstract class honeywell extends eqLogic {
    const PLUGIN_NAME = "evohome";
    const RELOAD = true;

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
	const MODE_PERMANENT_ON = '1';
	const MODE_PERMANENT_OFF = '0';
	const PRESENCE_UNDEFINED = '0';
	const PRESENCE_OUTSIDE = '1';
	const PRESENCE_INSIDE = '2';
	const SET_TH_MODE_PERMANENT = 'STM_1';
	const SET_TH_MODE_UNTIL_CURRENT_SCH = 'STM_2';
	const SET_TH_MODE_UNTIL_HHMM = 'STM_3';
	const SET_TH_MODE_UNTIL_END_OF_DAY = 'STM_4';
	const SET_TH_MODE_UNTIL_END_OF_PERIOD = 'STM_5';	// Lyric+Geofence case

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

	const CMD_STATE = 'etat';
	const CMD_SET_MODE = 'setmode';
	const CMD_SAVE = 'save';
	const CMD_RESTORE = 'restore';
	const CMD_DELETE = 'delete';
	const CMD_STATISTICS_ID = 'statistiquesInfos';
	const CMD_TEMPERATURE_ID = 'temperature';
	const CMD_CONSIGNE_ID = 'consigne';
	const CMD_SCH_CONSIGNE_ID = 'progConsigne';
	const CMD_CONSIGNE_TYPE_ID = 'consigneType';
	const CMD_SET_CONSIGNE_ID = 'setConsigne';

	const CFG_ACCURACY  = 'evoDecimalsNumber';
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
	const CACHE_SYNCHRO_RUNNING = "SYNCHRO_RUNNING";
	const SUCCESS = 'success';
	
	# -- infosAPI (common) :
	const IZ_TIMESTAMP = 'timestamp';
	const IZ_API_V1 = 'apiV1';
	const IZ_GATEWAY_CNX_LOST = 'cnxLost';

	const CURRENT_SCHEDULE_ID = 0;

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
	    return honeyutils::i18n($txt, "plugins/".honeywell::PLUGIN_NAME."/core/class/evohome.class.php", $arg);
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
			honeyutils::logDebug('0.2.1 : createOrUpdateCmd replace MODE/RESTORE cmd');
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
		if ( $logicalId == self::CMD_SET_CONSIGNE_ID ) {
			// 0.4.1 - become a default setting, before reading real values of min/max inside "injectInformationsFromZone"
			self::fillSetConsigneData($cmd,$this->getLogicalId(),self::adjustbyUnit(5,"C"),self::adjustbyUnit(25,"C"));
		}
		// 0.4.2 - les infos précédentes n'étaient pas compatibles "appli mobile"
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
		// flags for the honeywell.js
		$cmd->setConfiguration('canBeVisible', $isVisible);
		$cmd->setConfiguration('canBeHistorize', $isHistorized);
		$cmd->save();

		return $created;
	}

	public function preUpdate() {
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
			if ( is_object($cmd) ) {
				$v = $cmd->getIsVisible() ? '1' : '0';
				if ( honeyutils::isDebug() ) honeyutils::logDebug("preUpdate : visible STAT=$v");
				honeyutils::setCacheData(self::CACHE_STAT_PREV_VISIBLE, $v);
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
		honeyutils::logDebug("postSave");
		if ($this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE) {
			honeyutils::logDebug('postSave - create Console widget');
			$this->deleteCmd([self::CMD_TEMPERATURE_ID, self::CMD_CONSIGNE_ID, self::CMD_SCH_CONSIGNE_ID, self::CMD_CONSIGNE_TYPE_ID, self::CMD_SET_CONSIGNE_ID]);
			/*$created =*/ $this->createOrUpdateCmd(0, self::CMD_STATE, 'Etat', 'info', 'string', 1, 0);
			$this->createOrUpdateCmd(1, self::CMD_SET_MODE, 'Réglage mode', 'action', 'select', 1, 0);
			$this->createOrUpdateCmd(2, self::CMD_SAVE, 'Sauvegarder', 'action', 'other', 1, 0);
			$this->createOrUpdateCmd(3, self::CMD_RESTORE, 'Restaure', 'action', 'select', 1, 0);
			self::createOrUpdateCmd(4, self::CMD_DELETE, 'Supprimer', 'action', 'other', 1, 0);
			$this->createOrUpdateCmd(5, self::CMD_STATISTICS_ID, "Statistiques", 'info', 'numeric', 1, 0);

			$this->updateRestoreList($this->getLocationId());
			$this->updateSetModeList($this->getLocationId());
		}
		else /*if ($this->getLogicalId() > 0)*/ {
			honeyutils::logDebug('postSave - create TH widget');
			$this->deleteCmd([self::CMD_STATE, self::CMD_SET_MODE, self::CMD_SAVE, self::CMD_RESTORE, self::CMD_DELETE, self::CMD_STATISTICS_ID]);
			/*$created =*/ $this->createOrUpdateCmd(0, self::CMD_TEMPERATURE_ID, 'Température', 'info', 'numeric', 1, 1);
			$this->createOrUpdateCmd(1, self::CMD_CONSIGNE_ID, 'Consigne', 'info', 'numeric', 1, 1);
			$this->createOrUpdateCmd(2, self::CMD_SCH_CONSIGNE_ID, 'Consigne programmée', 'info', 'numeric', 0, 1);
			$this->createOrUpdateCmd(3, self::CMD_CONSIGNE_TYPE_ID, 'Type Consigne', 'info', 'string', 0, 0);	// 0.4.1 - no display usage
			$this->createOrUpdateCmd(4, self::CMD_SET_CONSIGNE_ID, 'Set Consigne', 'action', 'select', 1, 0);
			$this->_postSaveTH();
		}

		if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT - postSave(' . $this->getLogicalId() . ')'); 

		return true;
	}

	public function postRemove() {
	}

	function updateRestoreList($locId) {
		$hns = self::getScheduleNames($locId);
		$listValue = '';
		$idx = 1;
		foreach ( $hns as $hn ) {
			$listValue .= $hn['id'] . '|' . $hn['name'] . ($hn['type'] == null || $hn['type'] == HeatMode::SCHEDULE_TYPE_TIME ? '' : ' (' . $hn['type'] . ')');
			if ( $idx++ < count($hns) ) $listValue .= ';';
		}
		$cmd = $this->getCmd(null, self::CMD_RESTORE);
		$cmd->setConfiguration('listValue', $listValue);
		$cmd->save();
	}
	
	function updateSetModeList($locId) {
		$asmCodes = $this->getConfiguration(self::CONF_ALLOWED_SYSTEM_MODE);
		$list = '';
		if ( $asmCodes ) {
			$modesArray = $this->iGetModesArray();
			$hns = self::getScheduleNames($locId);
			foreach ($asmCodes as $code) {
				if ( $modesArray[$code]->modeSettable ) {
					if ( $modesArray[$code]->scheduleType == null ) {
						$list .= ($list != '' ? ';' : '') . $code . '|' . $modesArray[$code]->label;
					} else {
						// Lyric mode : combine with schedule file to extend the list :
						foreach ( $hns as $hn ) {
							if ( $hn['type'] == $modesArray[$code]->scheduleType ) {
								$list .= ($list != '' ? ';' : '') . $code . '§' . $hn['id'] . '|' . $modesArray[$code]->label . ' (' . $hn['name'] . ')';
							}
						}
					}
				}
			}
		}
		if ( $list == '' ) $list = '0|Unavailable';
		$cmd = $this->getCmd(null, self::CMD_SET_MODE);
		$cmd->setConfiguration('listValue', $list);
		$cmd->save();
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
			    $scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
			    $refreshCache = true;
			}
		}

		// settings depending of the "states" vars :
		$states = $this->getToHtmlProperty("states");
		//honeyutils::logDebug("-- private states ? " . (is_array($states) ? "yes" : "no"));
		if ( !is_array($states) ) {
			if ( is_array($cachedData) && array_key_exists('states',$cachedData) ) {
				//honeyutils::logDebug("use cachedData for states");
				$states = $cachedData['states'];
			}
			if ( !is_array($states) ) {
			    $states = self::getStates($locId,$this->iGetInformations());
			    $refreshCache = true;
			}
		}
		if ( $refreshCache ) {
			$cachedData = array("states"=>$states, "scheduleCurrent"=>$scheduleCurrent);
			honeyutils::setCacheData(self::CACHE_STATES, $cachedData, self::CACHE_STATES_DURATION, $locId);
		}

		$msgInfo = $this->getToHtmlProperty("msgInfo");

		$taskIsRunning = $this->getToHtmlProperty("taskIsRunning");
		if ( is_null($taskIsRunning) ) $taskIsRunning = false;

		if ( $typeEqu == self::TYPE_EQU_CONSOLE ) {
			//if ( honeyutils::isDebug() ) honeyutils::logDebug("-- toHtmlConsole msgInfo=$msgInfo");
			// CONSOLE
			$this->toHtmlConsole($pVersion,$version,$replace,$scheduleCurrent);
			$prevStatVisible = honeyutils::getCacheData(self::CACHE_STAT_PREV_VISIBLE);
			// got a STAT_PREV_VISIBLE, during console refresh
			honeyutils::doCacheRemove(self::CACHE_STAT_PREV_VISIBLE);
			if ( $prevStatVisible != '' ) {
				$cmd = $this->getCmd('info', self::CMD_STATISTICS_ID);
				if ( is_object($cmd) && ($cmd->getIsVisible() ? '1' : '0') != $prevStatVisible ) {
					honeyutils::logDebug("** during console refresh, detect change stat visible state, launch a full refesh...");
					self::refreshAllForLoc($locId,$this->iGetInformations());
				}
			}
		}
		else {
			// TH WIDGET
			$forcedConsigne = $this->getToHtmlProperty("forcedConsigne");
			$this->toHtmlTh($pVersion,$version,$replace,$scheduleCurrent,$forcedConsigne);
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
		$replace['#iazColorState#'] = $stateIsRunning ? 'crimson' : ($stateUnread || $stateCnxLost != '' ? 'red' : ($stateIsAccurate || honeyutils::getParam(self::CFG_ACCURACY,1) == 1 ? 'lightgreen' : 'coral'));
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

		$html = template_replace($replace, getTemplate('core', $version, 'honeywell', honeywell::PLUGIN_NAME));
		cache::set('evohomeWidget' . $version . $this->getId(), $html, 0);

		//honeyutils::logDebug("<<OUT - toHtml");
		return $html;
	}

	public function toHtmlConsole($pVersion,$version,&$replace,$scheduleCurrent) {
		$cmdEtat = $this->getCmd(null,self::CMD_STATE);
		if ( !is_object($cmdEtat) ) return;

		$replace_console = $this->preToHtml($pVersion);
		$locId = $this->getLocationId();
		$replace_console['#locId#'] = $locId;
		$replace_console['#argLocId#'] = self::ARG_LOC_ID;
		$replace_console['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

		$_etat = is_object($cmdEtat) ? $cmdEtat->execCmd() : '';
		$aEtat = explode(';',$_etat);
		$currentMode = $this->getModeFromHName($aEtat[0]);
		$replace_console['#etatImg#'] = $this->getEtatImg($currentMode);
		$replace_console['#etatCode#'] = $currentMode;

		// *******************************************************
		$this->iSetHtmlConsole($replace_console,$aEtat,$currentMode);
		// *******************************************************

		$scheduleType = self::getScheduleType($scheduleCurrent);
		$replace_console['#scheduleType#'] = $scheduleType;

		// **********************************************************************
		$thMode = $this->iGetThModes($currentMode,$scheduleType);
		// **********************************************************************

		$selectStyle = ' selected style="background-color:green !important;color:white !important;"';
		$unselectStyle = ' style="background-color:#efefef !important;color:black !important;"';
		$statCmd = $this->getCmd(null,self::CMD_STATISTICS_ID);
		$replace_console['#statDisplay#'] = (is_object($statCmd) && $statCmd->getIsVisible()) ? "block" : "none";
		if ( $replace_console['#statDisplay#'] == 'block') {
			$statScope = !is_object($statCmd) ? 1 : $statCmd->execCmd();
			if ( $statScope === '' ) $statScope = 0;
			$replace_console['#statTitle#'] = self::i18n('Statistiques');
			$replace_console['#statScope0#'] = $statScope == 0 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle0#'] = self::i18n('Désactivé');
			$replace_console['#statScope1#'] = $statScope == 1 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle1#'] = self::i18n('Jour');
			$replace_console['#statScope2#'] = $statScope == 2 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle2#'] = self::i18n('Semaine');
			$replace_console['#statScope3#'] = $statScope == 3 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle3#'] = self::i18n('Mois');
		}

		$options = '';
		$scheduleFileId = self::getCfgScheduleFileId($locId,$scheduleCurrent);
		$jsScheduleFileId = 0;
		$schedulesList = array();
		foreach ( self::getScheduleNames($locId) as $hn) {
			// $hn['type'] == null for empty list
			if ( $hn['type'] != null ) {
				if ( !array_key_exists($hn['type'],$schedulesList) ) {
					$schedulesList[$hn['type']] = array();
				}
				$schedulesList[$hn['type']][] = array($hn['id'], $hn['name']);
			}
			if ( $hn['type'] == null || $hn['type'] == $scheduleType ) {
				$options .= '<option value="' . $hn['id'] . '"';
				$options .= ( $hn['id'] == 0 || $hn['id'] == $scheduleFileId ) ? $selectStyle : $unselectStyle;
				if ( $hn['id'] == $scheduleFileId ) {
					$jsScheduleFileId = $scheduleFileId;
				}
				//$options .= '>' . ($hn['type'] != 'T' ? '(' . $hn['type'] . ') ' : '') . $hn['name'] . '</option>';
				$options .= '>' . $hn['name'] . '</option>';
				$empty = $hn['id'] == 0;
			}
		}
		$replace_console['#scheduleFileId#'] = $jsScheduleFileId;
		$replace_console['#scheduleOptions#'] = $options;

		// indicateur schedule modifié
		$saveColor = 'white';
		$canRestoreCurrent = 0;
		$saveTitle = self::i18n("Sauvegarde la programmation courante");
		$scheduleDelta = "0";
		if ( !$empty && $scheduleFileId != null ) {
			$scheduleSaved = self::getSchedule($locId,$scheduleFileId);
			if ( $scheduleSaved != null && $scheduleCurrent != null ) {
				$_scheduleSaved = json_encode($scheduleSaved['zones']);
				$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
				if ( $_scheduleSaved != $_scheduleCurrent ) {
					$saveColor = 'orange';
					$canRestoreCurrent = 1;
					$scheduleDelta = "1";
					$saveTitle .= ' (' . self::i18n("différente de la dernière programmation restaurée ou éditée") . ')';
				}
			}
		}
		honeyutils::setCacheData(self::CACHE_SCHEDULE_DELTA, $scheduleDelta, 2);
		$replace_console['#title.save#'] = $saveTitle;
		$replace_console['#canRestoreCurrent#'] = $canRestoreCurrent;
		$replace_console['#isAdmin#'] = honeyutils::isAdmin();
		$replace_console['#evoSaveColor#'] = $saveColor;

		foreach ($this->getCmd('action') as $cmd) {
			$replace_console['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		// arguments names
		$replace_console['#argCodeMode#'] = self::ARG_CODE_MODE;
		$replace_console['#argFileName#'] = self::ARG_FILE_NAME;
		$replace_console['#argFileId#'] = self::ARG_FILE_ID;
		$replace_console['#argZoneId#'] = self::ARG_ZONE_ID;
		$replace_console['#argFileRem#'] = self::ARG_FILE_REM;
		// codes mode allowed
		$replace_console['#codesAllowed#'] = json_encode($this->getConfiguration(self::CONF_ALLOWED_SYSTEM_MODE));
		// modes array - 28-03-2020 - Lyric needs : only modes available, ie: schefule file of type must exists, but for current mode, of course
		$replace_console['#modesArray#'] = json_encode($this->getJSModesArray($currentMode,$schedulesList));
		// Lyric needs : the popup for the modes should show the list of schedule files for the mode selected (and only then, accept the mode)
		$replace_console['#schedulesList#'] = $this->getHnwSystem() == self::SYSTEM_EVOHOME ? 'null' : json_encode($schedulesList);
		$replace_console['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_console['#displaySetModePopup#'] = honeyutils::getParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODE_CONSOLE) == self::CFG_SHOWING_MODE_POPUP ? "visible" : "none";
		$replace_console['#displaySetModeConsole#'] = honeyutils::getParam(self::CFG_SHOWING_MODES,self::CFG_SHOWING_MODE_CONSOLE) == self::CFG_SHOWING_MODE_CONSOLE ? "1" : "0";
		$replace_console['#evoDefaultShowingScheduleMode#'] = honeyutils::getParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

		// i18n
		$rbs = honeyutils::getParam(self::CFG_REFRESH_BEFORE_SAVE,0);
		$msg = array('scheduleTitle'=>$thMode['lblSchedule'],
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
		foreach ( $msg as $code=>$txt ) $replace_console["#$code#"] = self::i18n($txt);

		$replace_console['#console_min_js_size#'] = filemtime(dirname(__FILE__) . '/../template/dashboard/console_min.js');
		$replace['#consoleContent#'] = template_replace($replace_console, getTemplate('core', $version, 'console_content', honeywell::PLUGIN_NAME));
		$replace['#temperatureContent#'] = '';

		$replace['#batteryImgDisplay#'] = 'none';
		$replace['#batteryImg#'] = 'empty.svg';
		$replace['#batteryImgTitle#'] = '';
		
		$replace['#reloadDisplay#'] = self::RELOAD == true ? 'flex' : 'none';
	}

	public function toHtmlTh($pVersion,$version,&$replace,$scheduleCurrent,$forcedConsigne) {
		$zoneId = $this->getLogicalId();

		$replace_TH = $this->preToHtml($pVersion);
		$locId = $this->getLocationId();
		$replace_TH['#locId#'] = $locId;
		$replace_TH['#argLocId#'] = self::ARG_LOC_ID;
		$replace_TH['#zoneId#'] = $zoneId;
		$replace_TH['#fileId#'] = self::getCfgScheduleFileId($locId,$scheduleCurrent);

		// *** TEMPERATURE
		$replace_TH['#etatImg#'] = 'empty.svg';
		$replace_TH['#etatUntilImg#'] = 'empty.svg';

		$cmdTemperature = $this->getCmd(null,self::CMD_TEMPERATURE_ID);
		$cmdId = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
		$replace_TH['#temperatureId#'] = $cmdId;
		$replace_TH['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "block" : "none";
		$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
		if ( $temperatureNative == null ) {
			$temperature = 0;
			$replace_TH['#temperature#'] = '';
			$replace_TH['#temperatureDisplay2#'] = 'none';
		} else {
			$temperature = self::applyRounding($temperatureNative);
			$replace_TH['#temperature#'] = $temperature . '°';
			$replace_TH['#temperatureDisplay2#'] = 'table-cell';
		}

		// *** CONSIGNE
		$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
		$replace_TH['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
		$replace_TH['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "block" : "none";
		$consigne = $forcedConsigne != null ? $forcedConsigne : (is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0);
		$currentMode = self::getCurrentMode($locId);

		$cmdConsigneInfos = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
		//if ( is_object($cmdConsigneInfos) ) {	// 0.4.1 - remove the check of isVisible (useless and side effects in case of) ; 0.5 - remove check
			$consigneInfos = explode(';', $cmdConsigneInfos->execCmd());
			# $consigneInfos[0] = see __iSetHtmlTH
			# $consigneInfos[1] = 2018-01-28T23:00:00Z / <empty>
			# $consigneInfos[2] = Celsius/Fahrenheit
			# $consigneInfos[3] = step : 0.5 si °C ; 1 si °F					 	)
			# $consigneInfos[4] = 5 (min)  == self->getConfiguration('minHeat')		) 0.4.1 - these 3 values are now "adjusted by unit"
			# $consigneInfos[5] = 25 (max) == self->getConfiguration('maxHeat')		)
			# $consigneInfos[6] = delta previous measure (0/-1:+1)
			# $consigneInfos[7] = timeBattLow / <empty>
			# $consigneInfos[8] = timeCnxLost / <empty>
			# $consigneInfos[9] = Lyric : if TemporaryHold or HoldUntil, the endHeatSetpoint value
			# $consigneInfos[10] = Lyric : 0=heating off ; 1=heating on
		//}
		switch ( $temperatureNative == null ? 0 : ( ((count($consigneInfos)==11 and $consigneInfos[10] == '1') or $temperatureNative < $consigne) ? 2 : 1) ) {
			case 0 :
			$replace_TH['#temperatureImg#'] = 'batt-hs.png';
			$replace_TH['#temperatureImgStyle#'] = 'height:36px;width:36px;margin-top:2px;';
			$replace_TH['#temperatureDeltaDisplay#'] = 'none;';
			break;

			case 1:
			$replace_TH['#temperatureImg#'] = 'check-mark-md.png';
			$replace_TH['#temperatureImgStyle#'] = 'height:20px;';
			$replace_TH['#temperatureDeltaDisplay#'] = 'block';
			break;

			case 2:
			$replace_TH['#temperatureImg#'] = 'chauffage_on.gif';
			$replace_TH['#temperatureImgStyle#'] = 'height:15px;width:15px;';
			$replace_TH['#temperatureDeltaDisplay#'] = 'block';
		}
		// **********************************************************************
		$thMode = $this->iGetThModes($currentMode,null,$consigneInfos);
		// **********************************************************************
		$isOff = $thMode["isOff"];
		$isEco = $thMode["isEco"];
		$isAway = $thMode["isAway"];
		$isDayOff = $thMode["isDayOff"];
		$isCustom = $thMode["isCustom"];
		$isFollow = $thMode["follow"];
		$isTemporary = $thMode["temporary"];
		$isPermanent = $thMode["permanent"];
		$isScheduling = $thMode["scheduling"];
		
		$replace_TH['#displayScheduling#'] = $isScheduling ? "table-cell" : "none";
		$consignePair = self::getConsigneScheduled($locId,$scheduleCurrent,$zoneId);
		//if ( !$isScheduling ) {
			//$replace_TH['#currentConsigne#'] = 0;
			//$replace_TH['#currentConsigneUntil#'] = '';
			//$consigneScheduled = (count($consigneInfos) < 9 or $consigneInfos[9] == null) ? null : self::adjustByUnit( $consigneInfos[9],$consigneInfos[2]);
		//}
		$consigneScheduled = $consignePair == null ? null : self::adjustByUnit($consignePair['TH'],$consigneInfos[2]);	// 0.4.1 - adjust added
		//  additional infos
		$replace_TH['#currentConsigneUntil#'] = $consignePair == null ? '' : $consignePair['UNTIL'];
		$sConsigneScheduled = $consigneScheduled == null ? ("[".self::i18n("non déterminé")."]") : $consigneScheduled;
		$replace_TH['#currentConsigne#'] = $consigneScheduled == null ? 0 : $consigneScheduled;

		if ( $isOff ) $infoConsigne = 'OFF';
		else if ( $consigne == null ) $infoConsigne = '-';
		else $infoConsigne = $consigne . '°';
		$replace_TH['#consigne#'] = $infoConsigne;
		$replace_TH['#consigneNoUnit#'] = $consigne;
		$replace_TH['#consigneBG#'] = self::getBackColorForTemp($consigne,$isOff);

		$adjustAvailable = true;
		$consigneTypeImg = null;
		if ( $consigneInfos != null ) {
			# $consigneInfos[0] = FollowSchedule / PermanentOverride / TemporaryOverride

			$consigneTip = '';
			$consigneTypeUntil = '';
			$consigneTypeUntilFull = '';
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
				$consigneTypeUntilFull = $sConsigneScheduled == null ? self::i18n("Consigne forcée à {0}°", $consigne) : self::i18n("Consigne forcée à {0}° au lieu de {1}°", [$consigne, $sConsigneScheduled]);
				$consigneTypeImg = 'i_off_white.png';
				$adjustAvailable = false;
			} else if ( $isAway ) {
				$consigneTypeUntilFull = self::i18n("Mode inoccupé (remplace {0}°)", $sConsigneScheduled);
				$consigneTypeImg = 'i_away_white.png';
				$adjustAvailable = false;		// unavailable when AWAY mode
			} else if ( !$isEco &&!$isDayOff && !$isCustom && $isFollow ) {
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
							//$consigneTypeImg = 'down green.svg';
							$consigneTypeImg = 'down green.png';
						}
					} else if ( $consigne > $consigneScheduled ) {
						$consigneTypeUntilFull = self::i18n("Optimisation active : consigne supérieure à suivre active (remplace {0}°)", $consigneScheduled);
						$consigneTypeImg = 'up red.svg';
					}
				}
			} else if ( $isTemporary ) {
				$consigneTip = '';
				$consigneTypeImg = 'temp-override.svg';
				// example : $consigneInfos[1] = "2018-01-28T23:00:00Z"
				$time = honeyutils::gmtToLocalHM($consigneInfos[1]);
				$consigneTypeUntil = $time;
				$consigneTypeUntilFull = $sConsigneScheduled == null ? self::i18n("Forçage de la consigne programmée jusqu'à {0}", $time) : self::i18n("Forçage de la consigne programmée de {0}° jusqu'à {1}", [$sConsigneScheduled, $time]);
			} else if ( $isPermanent ) {
				$consigneTypeImg = 'override-active.png';
				$consigneTypeUntilFull = $sConsigneScheduled == null ? self::i18n("Forçage de la consigne programmée") : self::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			}
			$replace_TH['#consigneTypeUntil#'] = $consigneTypeUntil;
			$replace_TH['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
			$replace_TH['#consigneTip#'] = $consigneTip;
		}
		
		$cmdSetConsigne = $this->getCmd(null,self::CMD_SET_CONSIGNE_ID);
		if ( is_object($cmdSetConsigne) && !$cmdSetConsigne->getIsVisible() ) {
			$replace_TH['#setConsigneDisplayV1#'] = "none";
			$replace_TH['#setConsigneDisplayV2#'] = "none";
		} else {
			$typeAdjust = honeyutils::getParam(self::CFG_HP_SETTING_MODES,self::CFG_HP_SETTING_MODE_INTEGRATED) == self::CFG_HP_SETTING_MODE_INTEGRATED ? 1 : 2;
			$replace_TH['#setConsigneDisplayV1#'] = $typeAdjust == 1 ? "table-cell" : "none";
			$replace_TH['#setConsigneDisplayV2#'] = $typeAdjust == 2 ? "table-cell" : "none";
			// adjust temp infos
			$replace_TH['#adjustAvailable#'] = $adjustAvailable ? 'true' : 'false';
			$replace_TH['#msgAdjustConsigneUnavailable#'] = self::i18n("Le mode actif ne permet pas d'ajuster les consignes");
			$replace_TH['#msgEnforceConsigne#'] = self::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			$replace_TH['#adjustStep#'] = $consigneInfos[3];
			$replace_TH['#canReset#'] = $consigneScheduled == null || $consigneScheduled == $consigne ? 0 : 1;
			$replace_TH['#backScheduleTitle#'] = $consigneScheduled == null ? '' : self::i18n('Retour à la valeur programmée de {0}°', $consigneScheduled);
		}
		$replace_TH['#adjustLow#'] = $consigneInfos[4];
		$replace_TH['#adjustHigh#'] = $consigneInfos[5];
		$replace_TH['#consigneTypeImg#'] = $consigneTypeImg == null ? 'empty.svg' : $consigneTypeImg;
		$replace_TH['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'inline-block';
		// arguments names
		$replace_TH['#argFileId#'] = self::ARG_FILE_ID;
		$replace_TH['#argZoneId#'] = self::ARG_ZONE_ID;
		// codes
		$replace_TH['#showHorizontal#'] = self::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_TH['#evoDefaultShowingScheduleMode#'] = honeyutils::getParam(self::CFG_DEF_SHOW_SCHEDULE_MODE,self::CFG_SCH_MODE_HORIZONTAL);

		// Info Batterie (A)
		$replace_TH['#temperatureImgTitle#'] = $consigneInfos[8] == '' && $temperatureNative != null ? '' : ($temperatureNative == null  ? self::i18n("Connexion perdue (date inconnue), batterie HS") : self::i18n("Connexion perdue depuis {0}, batterie HS", honeyutils::gmtToLocalDateHMS($consigneInfos[8])));

		// fix 7 - error reported by TLoo - 2019-02-09 - btw, plugin without Console is uncomplete ;)
		$console = self::getConsole($locId);
		if ( $console != null ) {
			$cmdStatistics = $console->getCmd(null,self::CMD_STATISTICS_ID);
			$timeWindow = !is_object($cmdStatistics) || !$cmdStatistics->getIsVisible() || $cmdId == '' ? 0 : max($cmdStatistics->execCmd(), 0);
		}else {
			$timeWindow = 0;
		}
		$replace_TH['#minMaxDisplay#'] = $timeWindow == 0 ? "none" : "block";
		if ( $timeWindow == 0 ) {
			$replace_TH['#statDelta#'] = '&nbsp;';
			$replace_TH['#statDeltaTitle#'] = '';
			$replace_TH['#deltaDisplay#'] = 'none';
			$replace_TH['#deltaImg#'] = 'empty.svg';
		} else {
			$temperature = self::applyRounding($temperatureNative);
			$replace_TH['#statDelta#'] = $temperature == 0 ? '' : ($temperature > $consigne ? '+' : '') . round($temperature - $consigne,2) . '°';
			$replace_TH['#statDeltaTitle#'] = self::i18n("Ecart consigne");
			$delta = $consigneInfos[6] == 0 ? 0 : round($temperature - self::applyRounding($consigneInfos[6]),2);
			$replace_TH['#deltaDisplay#'] = $delta == 0 ? "none" : "inline-block";
			$replace_TH['#deltaValue#'] = ($delta > 0 ? "+" : "") . self::i18n("{0}° depuis la précédente mesure", $delta);
			$replace_TH['#deltaImg#'] = $delta > 0 ? 'green-up-anim.gif' : 'red-down-anim.gif';
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
			$sql = "select * from ("
				. "select datetime, value from historyArch where cmd_id=$cmdId and DATE_FORMAT(datetime,'$tw')=DATE_FORMAT(now(),'$tw')"
				. " union"
				. " select datetime, value from history where cmd_id=$cmdId and DATE_FORMAT(datetime,'$tw')=DATE_FORMAT(now(),'$tw')"
				. ") as x order by datetime";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ( count($results) == 0 ) {
				$replace_TH['#minMaxDisplay#'] = 'none';
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
				$replace_TH['#statTitle#'] = self::i18n($timeWindow == 1 ? "Statistiques du jour" : ($timeWindow == 2 ? "Statistiques de la semaine" : "Statistiques du mois"));
				$replace_TH['#statRazTimeTitle#'] = self::i18n("valeurs réinitialisées");
				$replace_TH['#statRazTime#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS(strtotime($results[0]['datetime'])) : $results[0]['datetime'];

				$replace_TH['#statLastReadTitle#'] = self::i18n("dernière lecture");
				$replace_TH['#statLastRead#'] = honeyutils::tsToLocalHMS(strtotime($results[count($results)-1]['datetime']));

				$replace_TH['#statMaxTitle#'] = self::i18n("max");
				$replace_TH['#statThMax#'] = self::applyRounding($max) . '°';
				$replace_TH['#statWhenMax#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS($dMax) : $sDateMax;
				$replace_TH['#statWhenMinus1#'] = $dMaxMinus1 == 0 ? '(' . self::i18n("pas encore") . ')' : honeyutils::tsToAbsoluteHM($dMaxMinus1);

				$replace_TH['#statAvgTitle#'] = self::i18n("moy");
				$replace_TH['#statThAvg#'] = self::applyRounding($avg) . '°';
				$replace_TH['#statNbPoints#'] = self::i18n("{0} points", count($results));

				$replace_TH['#statMinTitle#'] = self::i18n("min");
				$replace_TH['#statThMin#'] = self::applyRounding($min) . '°';
				$replace_TH['#statWhenMin#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS($dMin) : $sDateMin;
				$replace_TH['#statWhenPlus1#'] = $dMinPlus1 == 0 ? '(' . self::i18n("pas encore") . ')' : honeyutils::tsToAbsoluteHM($dMinPlus1);
			}
		}

		foreach ($this->getCmd('action') as $cmd) {
			$replace_TH['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		$replace['#consoleContent#'] = '';
		$replace_TH['#temperature_min_js_size#'] = filemtime(dirname(__FILE__) . '/../template/dashboard/temperature_min.js');
		$replace['#temperatureContent#'] = template_replace($replace_TH, getTemplate('core', $version, 'temperature_content', honeywell::PLUGIN_NAME));

		// Battery info (B)
		$replace['#batteryImgDisplay#'] = $consigneInfos[7] . $consigneInfos[8] === '' && $temperatureNative != null ? 'none' : 'flex';
		$replace['#batteryImg#'] = $consigneInfos[8] != '' || $temperatureNative == null ? 'batt-hs-small.png' : ($consigneInfos[7] != '' ? 'batt-low-small.png' : 'empty.svg');
		$replace['#batteryImgTitle#'] = $consigneInfos[8] != '' ? self::i18n("Batterie HS depuis {0}", honeyutils::gmtToLocalDateHMS($consigneInfos[8])) : ($temperatureNative == null ? self::i18n("Batterie HS (date inconnue)") : ($consigneInfos[7] != '' ? self::i18n("Batterie faible depuis {0}", honeyutils::gmtToLocalDateHMS($consigneInfos[7])) : ''));
		
		$replace['#reloadDisplay#'] = 'none';

		// new 0.4.1 - Adjust TH - labels go to i18n
		$replace['#lblAdjTHTitle1#'] = self::i18n("Modification de la consigne sur '{0}'");
		//$replace['#lblAdjTHTitle2#'] = self::i18n("La consigne de {0}° sera maintenue :");
		$replace['#setThModes#'] = json_encode($thMode['setThModes']);

		// new 0.4.1 - adjust background title of the widgets
		$bctMode = honeyutils::getParam(self::CFG_BACKCOLOR_TITLE_MODES,self::CFG_BCT_MODE_NONE);
		$THcolor = 'var(--link-color)';
		//honeyutils::logDebug("bctMode = $bctMode");
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
			if ( $temperature >= intval(honeyutils::getParam(self::CFG_BCT_2N_B,28)) ) {
				$tA = /*backgroundTopGradient ? "rgb(255,0,0,0)" :*/ "rgb(255,50,0,1)";
				$tB = "rgb(255,50,0,1)";
				$THcolor = '#fff';
			} else if ( $temperature >= intval(honeyutils::getParam(self::CFG_BCT_2N_A,26)) ) {
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

		// new 0.5.0
		$presence = self::getPresence($locId);
		//honeyutils::logDebug("Presence($locId) => $presence");
		$replace['#presenceDisplay#'] = $presence == self::PRESENCE_UNDEFINED ? 'none' : 'inline';
		$replace['#presenceImg#'] = $presence == self::PRESENCE_UNDEFINED ? 'empty.svg' : ($presence == self::PRESENCE_INSIDE ? 'inside.png' : 'outside.png');
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
		foreach ($asmList as $asm) if ( ($code = $this->getModeFromHName($asm)) !== null ) $allowedModes[] = $code;
		$this->setConfiguration(self::CONF_ALLOWED_SYSTEM_MODE, $allowedModes);
	}

	function getModeFromHName($hName) {
		$modesArray = $this->iGetModesArray();
		foreach ( $modesArray as $code=>$heatMode ) {
			if ( $heatMode->mode == $hName ) return $code;
		}
		// NB : 'AutoWithReset' seems == Auto (sent with SetModeE2, data retrieve = Auto)
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getModeFromHName($hName) not found => null");
		return null;
	}

	function getModeName($etatCode) {
		//honeyutils::logDebug("getModeName($etatCode)");
		$modesArray = $this->iGetModesArray();
		return $modesArray[$etatCode]->modeSettable ? $modesArray[$etatCode]->label : null;
	}
	
	function getEtatImg($etatCode) {
		$modesArray = $this->iGetModesArray();
		return $modesArray[$etatCode]->img;
	}

	function getJSModesArray($currentMode,$availableTypeSchedule) {
		$ma = array();
		foreach ( $this->iGetModesArray() as $code=>$heatMode ) {
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
		if ( $this->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE ) {
			$tmp = $this->getCmd(null,self::CMD_STATE);
			if(is_object($tmp)){
				$etat = $infosZones['currentMode']
					. ";" . ($infosZones['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
					. ";" . $infosZones['untilMode']
					. ";" . (array_key_exists('inside',$infosZones) ? ($infosZones['inside'] ? self::PRESENCE_INSIDE : self::PRESENCE_OUTSIDE) : self::PRESENCE_UNDEFINED);
				$tmp->event($etat);
			}

		} else /*if ( $zoneId > 0 || $zoneId !== '' )*/ {	// this check should be useless
			$infosZone = honeyutils::extractZone($infosZones,$zoneId);
			if ( $infosZone == null ) {
				honeyutils::logError("<<OUT - injectInformationsFromZone - no data found on zone $zoneId");
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
			    $consigneScheduled = self::getConsigneScheduledForZone($this->getLocationId(),$infosZone,$infosZone['units'],self::getScheduleType($infosZones))['TH'];
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
				. ";" . (array_key_exists('cnxLost',$infosZone) ? $infosZone['cnxLost'] : '')
				. ";" . (array_key_exists('endHeatSetpoint',$infosZone) ? $infosZone['endHeatSetpoint'] : '')
				. ";" . (array_key_exists('heating',$infosZone) ? $infosZone['heating'] : '0');
			$tmp = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
			if (is_object($tmp) ) {
				$tmp->event($consigneInfo);
			}
			// 0.4.1 - auto-adjust the list of available values for the SET_CONSIGNE action :
			$tmp = $this->getCmd(null,self::CMD_SET_CONSIGNE_ID);
			if (is_object($tmp) && (intval($tmp->getConfiguration('minHeat')) != $minHeat || intval($tmp->getConfiguration('maxHeat')) != $maxHeat) ) {
				self::fillSetConsigneData($tmp,$zoneId,$minHeat,$maxHeat,true);
			}

			if ( honeyutils::isDebug() ) {
				honeyutils::logDebug("zone$zoneId=" . $infosZone['name'] . " : temp = " . $infosZone['temperature'] . ", consigne = " . $infosZone['setPoint'] . ", type = $consigneInfo");
			}
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
			$ret = self::adaptScheduleZoneIdToString($ret);
		}
		return $ret;
	}
	
	function removeToHtmlProperties() {
		$lId = $this->getLocationId();
		$zId = $this->getLogicalId();
		honeyutils::doCacheRemove('toHtmlData_'.$lId."_".$zId);
	}


	/* ******************** Static ******************** */

	static function fillSetConsigneData($cmd,$zoneId,$minHeat,$maxHeat,$doSave=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("adjust min=$minHeat/max=$maxHeat of the SET_CONSIGNE command on the zone=$zoneId");
		// 0.4.1 - 1st choice to go back to the scheduled value
		// 0.4.3bis - separators become '§' as int values lower than 15 were converted with the '#' (#13 => 19) when transmitted between JS>PHP
		//		   WARNING ! Launch a Sync to re-generate these values inside each component/"Set Consigne"
		$list = "auto§" . $zoneId . "§0§0§null|" . self::i18n("Annulation (retour à la valeur programmée)") . ";";
		// 0.9 is the supposed value for the °F... (0.5 * 9/5)
		$step = honeyutils::getParam(self::CFG_TEMP_UNIT,self::CFG_UNIT_CELSIUS) == self::CFG_UNIT_CELSIUS ? 0.5 : 0.9;
		for( $t=$minHeat ; $t<=$maxHeat ; $t+=$step ) {
			// auto means the callback function must check availability of service (presence mode / api available)
			// 0.4.3bis - separators become '§'
			$list .= "auto§" . $zoneId . "§" . $t . "§" . $t . "§null|$t" . ($t < $maxHeat ? ';' : '');
		}
		$cmd->setConfiguration('listValue', $list);
		$cmd->setConfiguration('minHeat', $minHeat);
		$cmd->setConfiguration('maxHeat', $maxHeat);
		if ( $doSave )$cmd->save();
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
	static public function getScheduleNames($locId) {
		//honeyutils::logDebug("getScheduleNames($locId)...");
		$list = array();
		$schedulePath = self::getSchedulePath();
		foreach (ls($schedulePath, '*') as $file) {
			$parts = explode('_', $file);
			if ( count($parts) == 4 ) {
				$type = $parts[2];
				$parts[2] = $parts[3];
			} else {
				$type = HeatMode::SCHEDULE_TYPE_TIME;
			}
			if ( $locId == null || $parts[0] == $locId ) {
				$list[] = array('id' => $parts[1],
								'name' => $parts[2],
								'type' => $type,
								'fullPath' => $schedulePath . $file);
			}
		}
		if ( count($list) == 0 ) {
			$list[] = array('id' => 0,
							'name' => self::i18n('vide'),
							'type' => null,
							'fullPath' => '');
		} else {
			usort($list, "honeywell::cmpName");
		}
		return $list;
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

	static function applyRounding($temperatureNative) {
		$valRound = round($temperatureNative*100)/100;
		list($entier, $decRound) = explode('.', number_format($valRound,2));
		switch ( honeyutils::getParam(self::CFG_ACCURACY,1) ) {
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
		$ret['progress_file'] = jeedom::getTmpFolder(honeywell::PLUGIN_NAME) . '/dependance';

		// 0.4.2 - change dependency check
		// 0.4.3 - change 2>nul >> 2>dev/null (thanks github/titidnh)
		$x = system::getCmdSudo() . ' dpkg-query --show python-requests 2>/dev/null | wc -l';
		$r = exec($x);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("dependancy_info 1/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		$x = system::getCmdSudo() . system::get('cmd_check') . ' gd | grep php | wc -l';
		$r = exec($x);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("dependancy_info 2/2 [$x] = [$r]");
		if ($r == 0) {
			$ret['state'] = 'nok';
		}

		return $ret;
	}

	static function setPythonRunning($name) {
		honeyutils::setCacheData(self::CACHE_PYTHON_RUNNING, $name, 86400);
	}
	static function razPythonRunning() {
		honeyutils::doCacheRemove(self::CACHE_PYTHON_RUNNING);
	}

	static function getEquipments() {
	    return eqLogic::byType(honeywell::PLUGIN_NAME);
	}

	static function getOneEquByLocation() {
		$locIdAndEqus = array();
		foreach (self::getEquipments() as $eqLogic) {
			if ( $eqLogic->getIsVisible() && $eqLogic->getIsEnable() ) {
				$locId = $eqLogic->getLocationId();
				// don't keep console equ (for Lyric.setMode)
              	if ( $eqLogic->getLogicalId() != $locId && !array_key_exists($locId,$locIdAndEqus) ) {
                  $locIdAndEqus[$locId] = $eqLogic;
                }
			}
		}
		return $locIdAndEqus;
	}
	
	static function getFirstEqu($locId) {
		$ret = self::getOneEquByLocation()[$locId];
		honeyutils::logDebug("getFirstEqu($locId) = " . $ret->getName());
		return $ret;
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
	
	public static function getActionSaveId($locId) {
	    $console = self::getConsole($locId);
	    foreach ($console->getCmd('action') as $cmd) {
	        if ( $cmd->getLogicalId() === 'save' ) {
	            return $cmd->getId();
	        }
	    }
	    return null;
	}
	
	public static function getEquNamesAndId($locId) {
		$table = array();
		foreach (self::getEquipments() as $eqLogic) {
			if ( $eqLogic->getLocationId() == $locId ) {
				$table[$eqLogic->getLogicalId()] = $eqLogic->getName();
			}
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getEquNamesAndId($locId) : " . json_encode($table));
		return $table;
	}

	static function getPresence($locId) {
		$console = self::getConsole($locId);
		$cmdEtat = $console->getCmd(null,self::CMD_STATE);
		if ( is_object($cmdEtat) ) {
			$aEtat = explode(';',$cmdEtat->execCmd());
			if ( count($aEtat) == 4 ) {
				return $aEtat[3];
			}
		}
		return self::PRESENCE_UNDEFINED;
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
							$equ->iGetInformations(true);
						}
						$delay = time() - $td;
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

	const STATE_UNREAD = 'unread';
	const STATE_CRON_ACTIVE = 'cronActive';
	const STATE_IS_RUNNING = 'isRunning';
	const STATE_LAST_READ = 'lastRead';
	const STATE_IS_ACCURATE = 'isAccurate';
	const STATE_CNX_LOST = 'cnxLost';
	static function getStates($locId,$infosZones=null) {
		$states = array();
		$states[self::STATE_UNREAD] = (self::CACHE_IAZ_DURATION - honeyutils::getCacheRemaining(self::CACHE_IAZ,$locId)) > self::getLoadingInterval()*60;
		$states[self::STATE_CRON_ACTIVE] = self::isCronActive();
		$states[self::STATE_IS_RUNNING] = self::isIAZrunning();
		$states[self::STATE_LAST_READ] = !is_array($infosZones) || !array_key_exists(self::IZ_TIMESTAMP,$infosZones) ? 0 : honeyutils::tsToLocalDateTime($infosZones[self::IZ_TIMESTAMP]);
		// apiV1 available == accurate values available
		$states[self::STATE_IS_ACCURATE] = !is_array($infosZones) || !array_key_exists(self::IZ_API_V1,$infosZones) ? false : $infosZones[self::IZ_API_V1];
		$states[self::STATE_CNX_LOST] = !is_array($infosZones) || !array_key_exists(self::IZ_GATEWAY_CNX_LOST,$infosZones) ? '' : honeyutils::gmtToLocalDateHMS($infosZones[self::IZ_GATEWAY_CNX_LOST]);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getStates : " . json_encode($states));
		return $states;
	}

	static function refreshAllForLoc($locId,$infosZones,$inject=false,$msgInfo='',$taskIsRunning=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - refreshAllForLoc($locId)");
		$states = self::getStates($locId,$infosZones);
		$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
		foreach (self::getEquipments() as $equipment) {
			if ( $equipment->getLocationId() == $locId ) {
				// NB : $taskIsRunning should be set on console only
				$equipment->setToHtmlProperties($states,$scheduleCurrent,$msgInfo,$taskIsRunning && $equipment->getConfiguration(self::CONF_TYPE_EQU) == self::TYPE_EQU_CONSOLE);
				$msgInfo = '';	// set only on the first equipment
				$equipment->iRefreshComponent($infosZones,$inject);
			}
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - refreshAllForLoc($locId)");
	}

	static function getInformationsForLoc($locId,$doRefresh=false) {
		$eqLogic = self::getFirstEqu($locId);
		return $eqLogic->iGetInformations($doRefresh);
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
	
	static function getScheduleType($scheduleCurrent) {
		return array_key_exists('scheduleType',$scheduleCurrent) ? $scheduleCurrent["scheduleType"] : HeatMode::SCHEDULE_TYPE_TIME;
	}
	static function getCfgScheduleFileId($locId,$scheduleContent) {
		$scheduleType = self::getScheduleType($scheduleContent);
		$fileId = honeyutils::getParam(self::iCFG_SCHEDULE_ID, "none", $locId, $scheduleType);
		if ( $fileId == "none" ) {
			$fileId = honeyutils::getParam(self::iCFG_SCHEDULE_ID, "none", $locId);
			if ( $fileId == "none" ) return 0;
			honeyutils::setParam(self::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
		}
		return $fileId;
	}


	/*************** Statics about Scheduling ********************/

	static function getSchedule($locId,$fileId,$dateTime=0,$doRefresh=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - getSchedule($locId,$fileId)");
		if ( $fileId == self::CURRENT_SCHEDULE_ID ) {
			$schedule = null;//honeyutils::getCacheData(self::CACHE_CURRENT_SCHEDULE,$locId);
			if ( !is_array($schedule) || $doRefresh ) {
				$infosZones = self::getInformationsForLoc($locId,$doRefresh);
				if ( !is_array($infosZones) ) {
					if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT - getSchedule(' . self::CURRENT_SCHEDULE_ID . ') - error while getInformationsAllZonesE2 (see above)');
					//// avoid request again when we know requesting does not work
					//honeyutils::setCacheData(self::CACHE_CURRENT_SCHEDULE, array('dummy','1'), self::CACHE_STATES_DURATION, $locId);
					return null;
				}
				$schedule = array('datetime'=>$dateTime);
				$schedule['scheduleType'] = array_key_exists('scheduleType',$infosZones) ? $infosZones['scheduleType'] : HeatMode::SCHEDULE_TYPE_TIME;
				$schedule['units'] = array_key_exists('units',$infosZones['zones'][0]) ? $infosZones['zones'][0]['units'] : self::CFG_UNIT_CELSIUS;
				$scheduleByZone = array();
				foreach ( $infosZones['zones'] as $zone ) {
					$scheduleByZone[] = array(
						'zoneId' => $zone['zoneId'],
						'name' => $zone['name'],
						'schedule' => $zone['schedule']);
				}
				$schedule['zones'] = $scheduleByZone;
				//honeyutils::setCacheData(self::CACHE_CURRENT_SCHEDULE, $schedule, self::CACHE_STATES_DURATION, $locId);
			} else {
				honeyutils::logDebug('got getSchedule(0) from cache');
			}
			honeyutils::logDebug('<<OUT - getSchedule(0)');
			return self::adaptScheduleZoneIdToString($schedule);
		}
		$fileInfos = self::getFileInfosById($locId,$fileId);
		if ( $fileInfos != null ) {
			$fileContent = file_get_contents($fileInfos['fullPath']);
			if ( honeyutils::isDebug() ) honeyutils::logDebug('getSchedule from ' . $fileInfos['fullPath']);
			$fileContentDecoded = json_decode($fileContent, true);
			if ( !array_key_exists('scheduleType',$fileContentDecoded) ) {
				$fileContentDecoded['scheduleType'] = HeatMode::SCHEDULE_TYPE_TIME;
			}
			if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - getSchedule($fileId)");
			return self::adaptScheduleZoneIdToString($fileContentDecoded);
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - getSchedule($fileId) non trouvé");
		return null;
	}

	static function getFileInfosById($locId,$fileId) {
		foreach ( self::getScheduleNames($locId) as $item ) {
			if ( $item['id'] == $fileId ) return $item;
		}
		return null;
	}
	static function getCurrentMode($locId) {
		$console = self::getConsole($locId);
		$cmdEtat = $console->getCmd(null,self::CMD_STATE);
		if ( $cmdEtat != null && is_object($cmdEtat) ) {
			$etat = $cmdEtat->execCmd();
			if ( $etat != null ) {
				// self::CODE_MODE_AUTO and so on...
				return $console->getModeFromHName(explode(';', $etat)[0]);
			}
		}
		return null;
	}

	function isConsigneUnsettable($locId) {
		return !$this->iGetModesArray()[self::getCurrentMode($locId)]->consigneUnsettable;
	}
	
	static function adaptScheduleZoneIdToString($data) {
		//honeyutils::logDebug("adaptScheduleZoneIdToString before : " . json_encode($data));
		foreach ( $data["zones"] as &$zone ) {
			if ( is_string($zone["zoneId"]) ) break;;
			$zone["zoneId"] = $zone["zoneId"] . "";
		}
		//honeyutils::logDebug("adaptScheduleZoneIdToString after : " . json_encode($data));
		return $data;
	}
	
	static function getConsigneScheduled($locId,$scheduleCurrent,$zoneId) {
	    $infosSchedule = honeyutils::extractZone($scheduleCurrent, $zoneId);
	    if ( $infosSchedule == null || !is_array($infosSchedule) ) {
	        return null;
	    }
		$scheduleType = self::getScheduleType($scheduleCurrent);
	    return self::getConsigneScheduledForZone($locId,$infosSchedule,$scheduleCurrent['units'],$scheduleType);
	}

	static function getConsigneScheduledForZone($locId,$infos,$units,$scheduleType) {
	    $schedule = $infos['schedule'];
		if ( $scheduleType == HeatMode::SCHEDULE_TYPE_TIME ) {
			if ( count($schedule['DailySchedules']) == 0 ) return null;
			$currentDay = strftime('%u', time())-1;
			$currentTime = strftime('%H:%M', time());
			$dsSunday = $schedule['DailySchedules'][6];
			$lastTemp = $dsSunday['Switchpoints'][count($dsSunday['Switchpoints'])-1]['heatSetpoint'];
			foreach ( $schedule['DailySchedules'] as $ds ) {
				$mark = 0;
				if ( $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00' ) {
					array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'heatSetpoint'=>$lastTemp));
				}
				$nbPoints = count($ds['Switchpoints']);
				for ( $i=1 ; $i <= $nbPoints ; $i++) {
					$sp = $ds['Switchpoints'][$i-1];
					//$hm = substr($sp['TimeOfDay'],0,5);
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
						//honeyutils::logDebug("zoneId=".$infosZone['zoneId']. ", lastTemp=$lastTemp, TimeOfDay=$until");
						return array('TH'=>$lastTemp, 'UNTIL'=>$until);
					}
				}
			}
		} else if ( $scheduleType == HeatMode::SCHEDULE_TYPE_GEOFENCE ) {
			if ( self::getPresence($locId) == self::PRESENCE_OUTSIDE ) {
				$temp = $schedule['GeofenceSchedule']['awayPeriod']['heatSetPoint'];
				$ret = array('TH'=>$temp, 'UNTIL'=>'');
			} else {	// assume INSIDE
				$currentTime = (new DateTime())->format('H:i:s');
				$sleepData = $schedule['GeofenceSchedule']['sleepMode'];
				$sleepStart = $sleepData['startTime'];	// "22:00:00"
				$sleepEnd = $sleepData['endTime'];		// "07:00:00"
				if ( $currentTime >= $sleepStart or $currentTime <= $sleepEnd ) {
					$temp = $sleepData['heatSetPoint'];
					$ret = array('TH'=>$temp, 'UNTIL'=>$sleepEnd);
				} else {
					$temp = $schedule['GeofenceSchedule']['homePeriod']['heatSetPoint'];
					$ret = array('TH'=>$temp, 'UNTIL'=>$sleepStart);
				}
			}
			// values read in the GeofenceSchedule are °F, convert to 'common' units of the device
			$ret['TH'] = self::adjustByUnit2($ret['TH'], self::CFG_UNIT_FAHRENHEIT, $units);	
			return $ret;
		}
		return null;
	}

	static function refreshConsole($locId, $msgInfo='', $taskIsRunning=false) {
		self::refreshComponent(array("zoneId"=>$locId, "taskIsRunning"=>$taskIsRunning), $msgInfo);
	}
	static function refreshComponent($data, $msgInfo='') {
		$zoneId = $data['zoneId'];
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - refreshComponent($zoneId)");
		$comp = self::getComponent($zoneId);
		if ( $comp != null ) {
			$locId = $comp->getLocationId();
			$state = self::getStates($locId,$comp->iGetInformations());
			$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
			$consigne = array_key_exists('consigne',$data) ? $data['consigne'] : null;
			$comp->setToHtmlProperties($state,$scheduleCurrent,$msgInfo,$data['taskIsRunning'],$consigne);
			$comp->iRefreshComponent();
		}
		honeyutils::logDebug('<<OUT - refreshComponent');
	}

	public static function getScheduleSubTitle($id,$locId,$fileId,$scheduleType,$scheduleCurrent,$scheduleToShow,$targetOrientation,$zoneId,$scheduleSource,$isEdit=false) {
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
					$isDiff = json_encode(honeyutils::extractZone($scheduleToShow,$zoneId)) != json_encode(honeyutils::extractZone($scheduleCurrent,$zoneId));
				}
				if ( $isDiff ) {
					$infoDiff = self::i18n("différente de la programmation courante") . " *";
				} else {
					$infoDiff = self::i18n("identique à la programmation courante");
				}
			}
		}
		if ( $scheduleType == HeatMode::SCHEDULE_TYPE_TIME ) {
			if ( !$isEdit ) {
				if ( $zoneId == 0 ) {
					$ssf = "showScheduleCO($id,'$scheduleType','$scheduleSource',$fileId,'$targetOrientation');";
				} else {
					$ssf = "showScheduleTH($locId,$id,$zoneId,'$scheduleSource','$targetOrientation');";
				}
				$lbl = self::i18n($targetOrientation == self::CFG_SCH_MODE_VERTICAL ? "Vertical" : "Horizontal");
				$subTitle = "<a class='btn btn-success btn-sm tooltips' onclick=\\\"$ssf\\\">$lbl</a>&nbsp;$subTitle";
			} else {
				$infoDiff = self::i18n("Mode édition");
			}
		}
		$subTitle .= $infoDiff == '' ? '' : "<br/><i>$infoDiff</i>";
		return $subTitle;
	}


	/* ******************** ROUTAGE via méthode instance / hnwSystem / static vers les classes idoines ******************** */

	function getHnwSystem() {
		return $this->getConfiguration(self::CONF_HNW_SYSTEM,self::SYSTEM_EVOHOME);
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

	function iGetInformations($doRefresh=false, $readSchedule=true, $msgInfo='', $taskIsRunning=false) {
		$obj = $this->iGetInstance();
		return $obj->__iGetInformations($this->getLocationId(),$doRefresh, $readSchedule, $msgInfo, $taskIsRunning);
	}

	function iGetModesArray() {
		$obj = $this->iGetInstance();
		return $obj->__iGetModesArray();
	}

	function iSetHtmlConsole(&$replace,$aEtat,$etatCode) {
		$obj = $this->iGetInstance();
		$obj->__iSetHtmlConsole($replace,$aEtat,$etatCode);
	}

	function iGetThModes($currentMode,$scheduleType,$consigneInfos=null) {
		$obj = $this->iGetInstance();
		return $obj->__iGetThModes($currentMode,$scheduleType,$consigneInfos);
	}



	/* ******************** AJAX calls ******************** */

	static function ajaxChangeStatScope($locId,$newStatScope) {
		$console = self::getConsole($locId);
		if ( $console != null ) {
			$cmdStatistics = $console->getCmd(null,self::CMD_STATISTICS_ID);
			if ( is_object($cmdStatistics) ) {
				$cmdStatistics->event($newStatScope);
				self::refreshAllForLoc($locId,$console->iGetInformations());
			}
		}
	}

	static function ajaxRefresh($locId) {
		$console = self::getConsole($locId);
		if ( $console != null ) {
			$console->iGetInformations(true);
		}
	}


	/* ******************** Actions ******************** */

	function doCaseAction($paramAction, $parameters) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("doCaseAction($paramAction," . json_encode($parameters) . ")");
		$locId = $this->getLocationId();
		switch ($paramAction) {
			// -- Common functions
			case self::CMD_SAVE:	// Console
				$this->actionSaveSchedule($locId,$parameters);
				break;

			case self::CMD_DELETE:	// Console
				$this->actionDeleteSchedule($locId,$parameters);
				break;

			// -- Object dependent functions
			case self::CMD_RESTORE:	// Console
				$this->actionRestoreSchedule($locId,$parameters);
				break;

			case self::CMD_SET_MODE:	// Console
				$this->actionSetMode($locId,$parameters);
				break;

			case self::CMD_SET_CONSIGNE_ID:		// Temperature
				$this->actionSetConsigne($locId,$parameters);
				break;
		}
	}

	function actionSaveSchedule($locId,$parameters) {
		$fileName = $parameters[self::ARG_FILE_NAME];
		$fileId = $parameters[self::ARG_FILE_ID];
		$commentary = $parameters[self::ARG_FILE_REM];
		$newSchedule = array_key_exists(self::ARG_FILE_NEW_SCHEDULE,$parameters) ? $parameters[self::ARG_FILE_NEW_SCHEDULE] : null;
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSaveSchedule($fileName, $fileId, " . ($newSchedule == null ? '<currentSchedule>' : '<newSchedule>') . ')');
		//self::waitingIAZReentrance('SaveSChedule-' . rand(0,10000));
		//honeyutils::lockCron();
		$dateTime = time();
		// force refresh inside __iGetInformations
		if ( $newSchedule == null ) {
			$rbs = honeyutils::getParam(self::CFG_REFRESH_BEFORE_SAVE,0);
			$schedule = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID,$dateTime,$rbs==1);
		} else {
			// a received schedule (by content) is necessary of type SCHEDULE_TYPE_TIME at this time : 2020-03-03 (else, think to add parameter or include into newSchedule)
			$schedule = array('datetime'=>$dateTime, 'scheduleType'=>HeatMode::SCHEDULE_TYPE_TIME, 'zones'=>json_decode($newSchedule,true));
		}
		if ( $schedule == null ) {
			honeyutils::logDebug('<<OUT - actionSaveSchedule - error while getSchedule (see above)');
			// this call used to remove the loading mask on the screen
			self::refreshConsole($locId);
		} else {
			if ( (int)$fileId == self::CURRENT_SCHEDULE_ID ) {
				$fileId = $dateTime;
				$filePath = self::getSchedulePath() . $locId . "_" . $fileId . "_" . (array_key_exists('scheduleType',$schedule) ? $schedule['scheduleType'] . '_' : '') . $fileName;
			} else {
				$fileInfos = self::getFileInfosById($locId,(int)$fileId);
				$filePath = $fileInfos['fullPath'];
			}
			if ( honeyutils::isDebug() ) honeyutils::logDebug("launch save action with fileName='$filePath'");

			$fp = fopen($filePath, 'w');
			$schedule['comment'] = $commentary;
			fwrite($fp, json_encode($schedule));
			fclose($fp);

			if ( $newSchedule == null ) {
				honeyutils::setParam(self::iCFG_SCHEDULE_ID, $fileId, $locId, $schedule['scheduleType']);
				//self::updateScheduleFileId();
			}/* else {*/
				self::refreshAllForLoc($locId,$this->iGetInformations());
			/*}*/
			$console = self::getConsole($locId);
			$console->updateRestoreList($locId);
			$console->updateSetModeList($locId);
			honeyutils::logDebug('<<OUT - actionSaveSchedule');
		}
		//honeyutils::unlockCron();
	}

	function actionDeleteSchedule($locId,$parameters) {
		$fileId = $parameters[self::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($locId,$fileId);
		$msgInfo = '';
		if ( $fileInfos == null ) {
			honeyutils::logError('actionDeleteSchedule on unknown ID=' . $fileId);
			$msgInfo = self::i18n("Fichier introuvable");
		} else {
			$console = self::getConsole($locId);
			$cmdRestoreId = $console->getCmd(null, self::CMD_RESTORE)->getId();
			/* scenarioExpression => (id=206 /) scenarioSubElement_id=66
			   scenarioSubElement => id=66 / scenarioElement_id=44
			   scenario => scenarioElement ["44"] => name = "xxxxx" */
			// Lyric : add case for SetMode / fileId
			$cmdSetModeId = $console->getCmd(null, self::CMD_SET_MODE)->getId();
			$sql = " select cmdId, name from ("
				  . " select $cmdRestoreId as cmdId, s.name"
				  . " from scenarioExpression se"
				  . " inner join scenarioSubElement sse on sse.id = se.scenarioSubElement_id"
				  . " inner join scenario s on s.scenarioElement like concat('%\"',sse.scenarioElement_id,'\"%')"
				  . " where se.expression = '#$cmdRestoreId#' and se.options like '%$fileId%'"
				  . " union "
				  . " select $cmdSetModeId as cmdId, s.name"
				  . " from scenarioExpression se"
				  . " inner join scenarioSubElement sse on sse.id = se.scenarioSubElement_id"
				  . " inner join scenario s on s.scenarioElement like concat('%\"',sse.scenarioElement_id,'\"%')"
				  . " where se.expression = '#$cmdSetModeId#' and se.options like '%$fileId%'"
				  . ") x order by name";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ( count($results) > 0 ) {
				$list = '';
				foreach ( $results as $record ) {
					$list .= ($list != '' ? ', "' : '"') . $record['name'] . '" (' . ($record['cmdId'] == $cmdRestoreId ? self::i18n("Restauration") : self::i18n("SetMode+Restauration")) . ')';
				}
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Schedule $fileId is used by : $list !");
				if ( count($results) == 1 ) {
					$msgInfo = self::i18n("Suppression impossible du fichier '{0}' car il est utilisé dans le Scenario : {1}", [$fileInfos['name'],$list]);
				} else {
					$msgInfo = self::i18n("Suppression impossible du fichier '{0}' car il est utilisé dans les Scenarios : {1}", [$fileInfos['name'],$list]);
				}
			} else {
				if ( honeyutils::isDebug() ) honeyutils::logDebug("actionDeleteSchedule on ID=$fileId");
				unlink($fileInfos['fullPath']);
				$console->updateRestoreList($locId);
				$console->updateSetModeList($locId);
				$msgInfo = "1" . self::i18n("Fichier '{0}' supprimé", $fileInfos['name']);
			}
		}
		self::refreshConsole($locId,$msgInfo);
	}

	function actionRestoreSchedule($locId,$parameters,$obj=null) {
		$fileId = $parameters[self::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($locId,$fileId);
		if ( $fileInfos == null ) {
			honeyutils::logError("actionRestoreSchedule on unknown ID=$fileId");
			return;
		}
		$scheduleSaved = self::getSchedule($locId,$fileId);
		$scheduleType = self::getScheduleType($scheduleSaved);
		// Optimisation - retain only saved schedule/zone # CurrentSchedule/zone
		$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
		$scheduleToSend = array("scheduleType"=>$scheduleType, "zones"=>array());
		foreach ( $scheduleSaved['zones'] as $schZoneCandidate ) {
			$jsCandidate = json_encode($schZoneCandidate['schedule']);
			foreach ( $scheduleCurrent['zones'] as $schZoneCurrent ) {
				if ( $schZoneCandidate['zoneId'] == $schZoneCurrent['zoneId'] ) {
					$jsCurrent = json_encode($schZoneCurrent['schedule']);
					if ( $jsCandidate != $jsCurrent ) {
						$scheduleToSend['zones'][] = array("zoneId"=>$schZoneCandidate['zoneId'], "schedule"=>$schZoneCandidate['schedule']);
					}
					break;
				}
			}
		}
		$nbSchedules = count($scheduleToSend['zones']);
		if ( $nbSchedules == 0 ) {
			if ( honeyutils::isDebug() ) honeyutils::logDebug("actionRestoreSchedule on ID=$fileId : no change to send");
			honeyutils::setParam(self::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
			self::refreshConsole($locId,"1".self::i18n("Aucun changement envoyé (tous les programmes identiques)"));
			return true;
		}

		$execUnitId = rand(0,10000);
		self::waitingIAZReentrance("RestoreSchedule-$execUnitId");
		honeyutils::lockCron();

		if ( honeyutils::isDebug() ) honeyutils::logDebug("actionRestoreSchedule on saving ID=$fileId, name=" . $fileInfos['name'] . ", nbSchedules=$nbSchedules");
		$prevFileId = self::getCfgScheduleFileId($locId, $scheduleSaved);
		honeyutils::setParam(self::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
		$taskName = self::i18n("Restauration depuis '{0}' ({1} zone(s))", [$fileInfos['name'], $nbSchedules]);

		// **********************************************************************
		// Call Python or API function
		if ( $obj == null ) $obj = $this->iGetInstance();
		$aRet = $obj->__iRestoreSchedule($execUnitId,$locId,$scheduleToSend,$taskName);
		// **********************************************************************

		$retValue = true;
		if ( !is_array($aRet) ) {
			honeyutils::logError("Error while actionRestoreSchedule : response was empty or malformed", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			honeyutils::setParam(self::iCFG_SCHEDULE_ID, $prevFileId, $locId, $scheduleType);
			self::refreshConsole($locId,self::i18n("Erreur pendant l'envoi de la programmation") . ($aRet !== '' ? " : " . $aRet : ""));
			$retValue = false;
		}
		else if ( !$aRet[self::SUCCESS] ) {
			honeyutils::logError("Error while actionRestoreSchedule", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			honeyutils::setParam(self::iCFG_SCHEDULE_ID, $prevFileId, $locId, $scheduleType);
			self::refreshConsole($locId,self::i18n("Erreur pendant l'envoi de la programmation : {0} : {1}", [$aRet["code"], $aRet["message"]]));
			$retValue = false;
		} else {
			/*$fp = fopen($fileInfos['fullPath'], 'r');
			$fileContent = fread($fp,filesize($fileInfos['fullPath']));
			$schedule = honeyutils::jsonDecode($fileContent, 'restoreSchedule2');
			fclose($fp);*/
			// will read immediately the data, which are not necessary uptodate just now :(
			/*$rdarTask = new RefreshDataAfterRestore($fileId, "1".honeyutils::i18n("L'envoi de la programmation s'est correctement effectué"), $schedule);
			$rdarTask->start();*/
			$delay = $aRet["system"] == self::SYSTEM_EVOHOME ? 30 : 5;
			$nb = 1;
			while ( true ) {
				sleep(2);
				set_time_limit(60);	// reset the time_limit (?)
				$msgInfo = "1".self::i18n("Rafraichissement des données, essai {0}...", $nb);
				//self::updateScheduleFileId($schedule, $msgInfo, true);
				//$allInfos = $this->iGetInformations(true, true, $msgInfo, true);
				/*$allInfos =*/ $obj->__iGetInformations($locId,true,true,$msgInfo,true);
				while ( true ) {	// waiting for refresh event triggers the toHtmlConsole..
					$sd = honeyutils::getCacheData(self::CACHE_SCHEDULE_DELTA);
					if ( $sd !== '' ) break;
					usleep(250000);
				}
				if ( $sd == "1" ) {
					$nb += 1;
					self::refreshConsole($locId,"1".self::i18n("Rafraichissement des données : attente {0} sec avant essai {1}", [$delay, $nb]), true);
					sleep($delay-2);
				} else {
					break;
				}
			}
			self::refreshConsole($locId,"1".self::i18n("L'envoi de la programmation s'est correctement effectué"));
		}
		honeyutils::unlockCron();
		return $retValue;
	}

	function actionSetMode($locId,$parameters) {
		$codeMode = $parameters[self::ARG_CODE_MODE];
		if ( $codeMode === null || $codeMode === '' ) {
			honeyutils::logDebug('actionSetMode called without code');
			return;
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSetMode with code=$codeMode");
		$execUnitId = rand(0,10000);
		self::waitingIAZReentrance("SetMode-$execUnitId");
		honeyutils::lockCron();

		// ******************************
		$obj = $this->iGetInstance();
		$aRet = $obj->__iSetMode($execUnitId,$locId,$codeMode);
		// ******************************

		$success = false;
		if ( !is_array($aRet) ) {
			honeyutils::logError("Error while actionSetMode : response was empty or malformed", $aRet);
			$msgInfo = self::i18n("Erreur en changement de mode");
		} else if ( !$aRet[self::SUCCESS] ) {
			honeyutils::logError("Error while actionSetMode", $aRet);
			$msgInfo = self::i18n("Erreur en changement de mode : {0} - {1}", [$aRet["code"], $aRet["message"]]);
		} else {
			if ( $aRet["system"] == self::SYSTEM_EVOHOME ) {
				self::refreshConsole($locId, "1".self::i18n("Rechargement des données en attente..."), true);
				sleep(10);	// wait a bit before loading new values
			}
			$msgInfo = "1".self::i18n("Le mode de présence a été correctement modifié");
			$success = true;
		}
		if ( $success ) $this->iGetInformations(true,true,"1".self::i18n('Rechargement des données en cours...'),true);
		self::refreshConsole($locId,$msgInfo);

		honeyutils::logDebug('<<OUT - actionSetMode');
		honeyutils::unlockCron();
	}

	function actionSetConsigne($locId,$parameters) {
		$params = $parameters[self::ARG_CONSIGNES_DATA];
		if ( $params == null || $params == "" ) {
			honeyutils::logError(self::i18n("Par Scénario : Set Consigne : paramètre reçu invalide (le choix 'Aucun' dans la liste déroulante ne peut pes être évité, mais il est inutile !)"));
			return;
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSetConsigne($params)");
		// 0.4.3bis - separators become '§'
		$params = explode('§', $params);
		// $params[0] = auto / manuel
		// $params[1] = zoneId
		// $params[2] = temp requested (new value) ; 0 means return to FollowSchedule (back to scheduled setpoint)
		// $params[3] = a) = $params[2] if setting else than scheduled value ; b) by JS : scheduled value if $params[2] = 0 ; c) by Scenario : 0
		// $params[4] = null (for permanent) or time as HH:MM:SS
		$zoneId = $params[1];
		$prefixByScenario = $params[0] == 'auto' ? self::i18n("Par Scénario") . " : " : "";

		$infosZones = $this->iGetInformations();
		if ( !is_array($infosZones) ) {
			honeyutils::logError("Consigne - error while getInformations");
			honeyutils::logDebug("<<OUT - actionSetConsigne");
			return;
		}
		$cmdConsigne = $this->getCmd(null,self::CMD_CONSIGNE_ID);
		$cmdConsigneInfos = $this->getCmd(null,self::CMD_CONSIGNE_TYPE_ID);
		if ( is_object($cmdConsigne) && is_object($cmdConsigneInfos) ) {
			$oldConsigne = $cmdConsigne->execCmd();	// btw, this value is against unit chosen
			$oldConsigneInfos = explode(';', $cmdConsigneInfos->execCmd());
			# $oldConsigneInfos[0] = FollowSchedule / PermanentOverride / TemporaryOverride
			# $oldConsigneInfos[1] = 2018-01-28T23:00:00Z / <empty>
			# $oldConsigneInfos[2] = C-elsius/F-ahrenheit
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
					break;
				}
			}
		}

		$newValue = $params[2] == 0 ? 0 : self::revertAdjustByUnit($params[2],$deviceUnit);	// 0.4.1 - convert if needed
		if ( $params[3] == 0 ) {
			$tmp = $this->getCmd(null,self::CMD_SCH_CONSIGNE_ID);
			$params[3] = $tmp->execCmd();	// btw, this value is against unit chosen
		}
		$realValue = self::revertAdjustByUnit($params[3],$deviceUnit);

		// $data = 'manuel/auto # zoneId # value (nn.n or 0=reset) # realValue # until ('null' or 'timevalue'
		$data = array('mode'=>$params[0],
						'zoneId'=>$zoneId,		// string only (since 0.5.0)
						'value'=>$newValue,	// keep in string
						'realValue'=>$realValue,
						'until'=>$params[4]);	// (PermanentOverride when null)

		// ************* EVOHOME Dependant ***************************************************************************************************************
		$obj = $this->iGetInstance();
		if ( $data['until'] == '' || $data['until'] == 'null' ) $data['until'] = null;
		if ( $data['until'] != null ) {
			$data['until'] = $obj->__iTransformUntil($data['until']);
		}
		$FPT = $obj->__iGetFPT();
		$newStatus = $data['value'] == 0 ? $FPT["follow"] : ($data['until'] == null ? $FPT["permanent"] : $FPT["temporary"]);
		$data['status'] = $newStatus;
		// ***********************************************************************************************************************************************

		$newUntil = $data['until'] == null ? 'NA' : $data['until'];
		if ( honeyutils::isDebug() ) honeyutils::logDebug("consigne=$oldConsigne<>$params[3] ; status=$oldStatus<>$newStatus ; until=$oldUntil<>$newUntil");
		if ( $oldConsigne == $params[3] && $oldStatus == $newStatus && $oldUntil == $newUntil ) {
			$msgInfo = $prefixByScenario . self::i18n("Set Consigne zone {0} : valeurs reçues identiques aux valeurs courantes (consigne, durée)", $zoneId);
			honeyutils::logError($msgInfo);
			if ( $prefixByScenario == "" ) {
				$this->setMsgInfo($msgInfo);
				$this->iRefreshComponent($infosZones);
			}
			honeyutils::logDebug("<<OUT - actionSetConsigne");
			return;
		}
		// -----

		if ( $data['mode'] == 'auto' ) {	// triggered by scenario
			if ( self::getStates($locId)[self::STATE_UNREAD] ) {
				honeyutils::logError($prefixByScenario . self::i18n("Set Consigne est indisponible : API off"));
				honeyutils::logDebug("<<OUT - actionSetConsigne");
				return;
			}
			if ( $this->isConsigneUnsettable($locId) ) {
				honeyutils::logError($prefixByScenario . self::i18n("Set Consigne est indisponible : mode de présence incompatible"));
				honeyutils::logDebug("<<OUT - actionSetConsigne");
				return;
			}
		}

		$execUnitId = rand(0,10000);
		$zname = $this->getName();
		//self::waitingIAZReentrance("actionSetConsigne-$execUnitId");
		$taskName = $prefixByScenario . self::i18n("Set consigne {0}° sur {1} ({2})", [$params[3],$zname,$data['until'] == null ? 'permanent' : self::i18n("jusqu'à {0}", $data['until'])]);
		
		// ****** EVOHOME Dependant *****************************************************************
		$infos = $obj->__iSetConsigne($execUnitId,$locId,$zoneId,$data,$params[3],$taskName);
		// ******************************************************************************************

		$updated = false;
		if ( !is_array($infos) ) {
			honeyutils::logError($prefixByScenario . "Error while Set Consigne zone $zoneId : response was empty or malformed");
			$this->setMsgInfo(self::i18n("Erreur en envoi de la consigne"));
		} else if ( !$infos[self::SUCCESS] ) {
			honeyutils::logError($prefixByScenario . "Error while Set Consigne zone $zoneId", $infos);
			$this->setMsgInfo(self::i18n("Erreur en envoi de la consigne  : {0} - {1}", [$infos["code"], $infos["message"]]));
		} else {
			$msgInfo = null;
			// merge requested value into zone data :
			foreach ( $infosZones['zones'] as &$infosZone ) {
				if ( $infosZone['zoneId'] == $zoneId ) {
					$infosZone['setPoint'] = $data['realValue'];
					$infosZone['status'] = $newStatus;
					$infosZone['until'] = $newUntil;
					$msgInfo = "1" . self::i18n("La consigne de {0}° a été correctement envoyée vers : {1}", [$params[3],$zname]);
					honeyutils::setCacheData(self::CACHE_IAZ, $infosZones, self::CACHE_IAZ_DURATION, $locId);
					$updated = true;
					break;
				}
			}

			$states = self::getStates($locId,$infosZones);
			$scheduleCurrent = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID);
			$this->setToHtmlProperties($states,$scheduleCurrent,$msgInfo);
		}
		$this->iRefreshComponent($infosZones,$updated);
		honeyutils::logDebug('<<OUT - actionSetConsigne');
	}


	/*	* ************************* CRON parts ****************************** */

	/* see hnw_install() above ; Called by specific cron via evohome.class.php/main_refresh */
	public static function honeywell_refresh() {
		if ( honeyutils::getParam(self::CONF_HNW_SYSTEM,'') == self::SYSTEM_LYRIC ) {
		    require_once 'lyric.php';
			lyric::lyric_refresh();
		}
	}

}