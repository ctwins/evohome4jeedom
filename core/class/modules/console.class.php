<?php

/**
 * This class appears with version 0.5.3, and content is extract from honeywell.class.php
 * and its content is dedicated parts about Console functionalities.
 * 
 * @author ctwins95
 *
 */
class Console {
	const CMD_STATE = 'etat';
	const CMD_SET_MODE = 'setmode';
	const CMD_SAVE = 'save';
	const CMD_RESTORE = 'restore';
	const CMD_DELETE = 'delete';
	const CMD_STATISTICS_ID = 'statistiquesInfos';
	// -- detailed infos
	const CMD_STATE_CURRENT = 'coCurrentMode';
	const CMD_STATE_PREVIOUS = 'coPreviousMode';
	const CMD_STATE_PERMANENT = 'coModePermanent';
	const CMD_STATE_UNTIL = 'coModeUntil';
	const CMD_CURRENT_SCHEDULE = 'coCurrentSchedule';
	// ---- Lyric only
	const CMD_STATE_PRESENCE = 'coPresence';
	
	const SM_PREVIOUS_MODE_CODE = 'PR';

	static function preUpdate($equ) {
		$cmd = $equ->getCmd('info', self::CMD_STATISTICS_ID);
		if ( is_object($cmd) ) {
			$v = $cmd->getIsVisible() ? '1' : '0';
			if ( honeyutils::isDebug() ) honeyutils::logDebug("preUpdate : visible STAT=$v");
			honeyutils::setCacheData(honeywell::CACHE_STAT_PREV_VISIBLE, $v);
		}
	}

	static function postSave($equ) {
		honeyutils::logDebug('postSave - create Console widget XXX');
		$i = 0;
		//$this->deleteCmd([honeywell::CMD_TEMPERATURE_ID, honeywell::CMD_CONSIGNE_ID, honeywell::CMD_SCH_CONSIGNE_ID, honeywell::CMD_CONSIGNE_TYPE_ID, honeywell::CMD_SET_CONSIGNE_ID]);
		$equ->createOrUpdateCmd($i++, self::CMD_STATE, 'Etat', 'info', 'string', 1, 0);
		// 0.6.1 - Scenario or external management helper
		$equ->createOrUpdateCmd($i++, honeywell::CMD_BOILER_REQUEST, 'Demande de chauffage', 'info', 'numeric', 0, 1);
		$equ->createOrUpdateCmd($i++, self::CMD_SET_MODE, 'Réglage mode', 'action', 'select', 1, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_SAVE, 'Sauvegarder', 'action', 'other', 1, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_RESTORE, 'Restaure', 'action', 'select', 1, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_DELETE, 'Supprimer', 'action', 'other', 1, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_STATISTICS_ID, 'Statistiques', 'info', 'numeric', 1, 0);
		// -- Commandes INFO détaillées (usage tiers)
		// ---- mode
		$equ->createOrUpdateCmd($i++, self::CMD_STATE_CURRENT, 'Mode courant', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_STATE_PREVIOUS, 'Mode précédent', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_STATE_PERMANENT, 'Permanent', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_STATE_UNTIL, 'Mode actif jusqu\'à', 'info', 'string', 0, 0);
		// ---- schedule
		$equ->createOrUpdateCmd($i++, self::CMD_CURRENT_SCHEDULE, 'Programme actif', 'info', 'string', 0, 0);
		if ( $equ->isLyric() ) {
			$equ->createOrUpdateCmd($i, self::CMD_STATE_PRESENCE, 'Présence', 'info', 'string', 0, 0);
		}

		self::consoleUpdateActionsList($equ);
	}
	
	static function consoleUpdateActionsList($equ) {
		$locId = $equ->getLocationId();
		$hns = Schedule::getScheduleNames($locId);
		$listValue = '';
		// -- Restore Schedule
		$idx = 1;
		foreach ( $hns as $hn ) {
			$listValue .= $hn['id'] . '|' . $hn['name'] . ($hn['type'] == null || $hn['type'] == HeatMode::SCHEDULE_TYPE_TIME ? '' : ' (' . $hn['type'] . ')');
			if ( $idx++ < count($hns) ) $listValue .= ';';
		}
		$cmd = $equ->getCmd(null, self::CMD_RESTORE);
		$cmd->setConfiguration('listValue', $listValue);
		$cmd->save();

		// -- Presence modes
		$asmCodes = $equ->getConfiguration(honeywell::CONF_ALLOWED_SYSTEM_MODE);
		$listValue = self::SM_PREVIOUS_MODE_CODE . '|' . honeywell::i18n('Mode précédent');
		if ( $asmCodes ) {
			$modesArray = $equ->getModesArray();
			foreach ($asmCodes as $code) {
				if ( $modesArray[$code]->modeSettable ) {
					if ( $modesArray[$code]->scheduleType == null ) {
						$listValue .= ($listValue != '' ? ';' : '') . $code . '|' . $modesArray[$code]->label;
					} else {
						// Lyric mode : combine with schedule file to extend the list :
						foreach ( $hns as $hn ) {
							if ( $hn['type'] == $modesArray[$code]->scheduleType ) {
								$listValue .= ($listValue != '' ? ';' : '') . $code . '§' . $hn['id'] . '|' . $modesArray[$code]->label . ' (' . $hn['name'] . ')';
							}
						}
					}
				}
			}
		}
		if ( $listValue == '' ) $listValue = '0|Unavailable';
		$cmd = $equ->getCmd(null, self::CMD_SET_MODE);
		$cmd->setConfiguration('listValue', $listValue);
		$cmd->save();
	}

	static function injectInformations($equ,$infosZones) {
		honeyutils::logDebug("** Inject Console...");
		$strState = State::buildStr($infosZones);
		$strPrevState = honeyutils::saveInfo($equ, self::CMD_STATE, $strState);
		// Detailed infos
		$oState = State::buildObjFromStr($strState);
		honeyutils::saveInfo($equ, self::CMD_STATE_CURRENT, $oState->currentMode);
		$oPrevState = State::buildObjFromStr($strPrevState);
		if ( $oPrevState->currentMode != $oState->currentMode ) {
			honeyutils::saveInfo($equ, self::CMD_STATE_PREVIOUS, $oPrevState->currentMode);
		}
		honeyutils::saveInfo($equ, self::CMD_STATE_PERMANENT, $oState->permanent);
		honeyutils::saveInfo($equ ,self::CMD_STATE_UNTIL, $oState->until);
		if ( $equ->isLyric() ) {
			// Lyric only
			honeyutils::saveInfo($equ, self::CMD_STATE_PRESENCE, $oState->presence);
		}
		// Scheduling
		$schName = Schedule::getActiveScheduleName($equ->getLocationId(),$infosZones);
		honeyutils::saveInfo($equ, self::CMD_CURRENT_SCHEDULE, $schName);
	}
	
	static function setBoilerRequest($locId, $boilerRequest) {
		$console = self::getConsole($locId);
		$prevBoilerRequest = honeyutils::readInfo($console, honeywell::CMD_BOILER_REQUEST);
		honeyutils::logDebug("Console $locId setBoilerRequest($boilerRequest) / previous=$prevBoilerRequest");
		if ($prevBoilerRequest != $boilerRequest) {
			self::saveBoilerRequest($console,$boilerRequest,false);
		}
	}
  
    static function adjustBoilerRequest($locId, $adjust) {
    	if ($adjust === 0) {
    		return;
    	}
    	$console = self::getConsole($locId);
		$prevBoilerRequest = honeyutils::readInfo($console, honeywell::CMD_BOILER_REQUEST);
		$boilerRequest = $prevBoilerRequest + $adjust;
		honeyutils::logDebug("Console $locId adjustBoilerRequest($boilerRequest) / previous=$prevBoilerRequest");
		self::saveBoilerRequest($console,$boilerRequest,true);
    }
    
    static function saveBoilerRequest($console, $boilerRequest,$doRefresh) {
    	honeyutils::saveInfo($console, honeywell::CMD_BOILER_REQUEST, $boilerRequest);
    	if ($doRefresh) {
    		$console->refreshWidget();
    	}
    }

	static function getConsole($locId) {
		return honeywell::getComponent($locId);
	}
	
	static function getActionSaveId($locId) {
	    $cmd = self::getConsole($locId)->getCmd(null,self::CMD_SAVE);
		return $cmd->getId();
	}

	static function getCurrentMode($locId) {
		$equ = self::getConsole($locId);
		$state = State::buildObj($equ);
		if ( $state != null ) {
			// self::CODE_MODE_AUTO and so on...
			return $equ->getModeFromHName($state->currentMode);
		}
		return null;
	}

	static function getPresence($locId) {
		$state = State::buildObj(self::getConsole($locId));
		return $state == null ? State::PRESENCE_UNDEFINED : $state->presence;
	}
	
	static function getTimeWindow($locId,$cmdTempId) {
		$cmd = self::getConsole($locId)->getCmd('info',self::CMD_STATISTICS_ID);
		return !is_object($cmd) || !$cmd->getIsVisible() || $cmdTempId == '' ? 0 : max(0, $cmd->execCmd());
	}

	static function toHtml($equ, $pVersion, $version, &$replace, $scheduleCurrent) {
		//if ( honeyutils::isDebug() ) honeyutils::logDebug("-- toHtmlConsole msgInfo=$msgInfo");
		self::toHtml2($equ, $pVersion, $version, $replace, $scheduleCurrent);
		
		$prevStatVisible = honeyutils::getCacheData(honeywell::CACHE_STAT_PREV_VISIBLE);
		// got a STAT_PREV_VISIBLE, during console refresh
		honeyutils::doCacheRemove(honeywell::CACHE_STAT_PREV_VISIBLE);
		if ( $prevStatVisible != '' ) {
			$cmd = $equ->getCmd('info',self::CMD_STATISTICS_ID);
			if ( is_object($cmd) && ($cmd->getIsVisible() ? '1' : '0') != $prevStatVisible ) {
				honeyutils::logDebug("** during console refresh, detect change stat visible state, launch a full refesh...");
				honeywell::refreshAllForLoc($equ->getLocationId(),$equ->getInformations());
			}
		}
	}	

	static function toHtml2($equ, $pVersion, $version, &$replace, $scheduleCurrent) {
		$cmdEtat = $equ->getCmd(null,self::CMD_STATE);
		if ( !is_object($cmdEtat) ) return;

		$replace_console = $equ->preToHtml($pVersion);
		$locId = $equ->getLocationId();
		$replace_console['#locId#'] = $locId;
		$replace_console['#argLocId#'] = honeywell::ARG_LOC_ID;
		$replace_console['#etatId#'] = is_object($cmdEtat) ? $cmdEtat->getId() : '';

		$state = State::buildObj($equ);
		$currentModeCode = $equ->getModeFromHName($state->currentMode);
		$replace_console['#etatImg#'] = $equ->getEtatImg($currentModeCode);
		$replace_console['#etatCode#'] = $currentModeCode;

		// *******************************************************
		$equ->iGetInstance()->iSetHtmlConsole($replace_console,$state,$currentModeCode);
		// *******************************************************

		$scheduleType = Schedule::getScheduleType($scheduleCurrent);
		$replace_console['#scheduleType#'] = $scheduleType;

		// **********************************************************************
		$thMode = $equ->getThModes($currentModeCode,$scheduleType);
		// **********************************************************************

		$selectStyle = ' selected style="background-color:green !important;color:white !important;"';
		$unselectStyle = ' style="background-color:#efefef !important;color:black !important;"';
		$statCmd = $equ->getCmd('info',self::CMD_STATISTICS_ID);
		$replace_console['#statDisplay#'] = (is_object($statCmd) && $statCmd->getIsVisible()) ? "block" : "none";
		if ( $replace_console['#statDisplay#'] == 'block') {
			$statScope = !is_object($statCmd) ? 1 : $statCmd->execCmd();
			if ( $statScope === '' ) $statScope = 0;
			$replace_console['#statTitle#'] = honeywell::i18n('Statistiques');
			$replace_console['#statScope0#'] = $statScope == 0 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle0#'] = honeywell::i18n('Désactivé');
			$replace_console['#statScope1#'] = $statScope == 1 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle1#'] = honeywell::i18n('Jour');
			$replace_console['#statScope2#'] = $statScope == 2 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle2#'] = honeywell::i18n('Semaine');
			$replace_console['#statScope3#'] = $statScope == 3 ? $selectStyle : $unselectStyle;
			$replace_console['#statScopeTitle3#'] = honeywell::i18n('Mois');
		}

		$options = '';
		$scheduleFileId = Schedule::getCfgScheduleFileId($locId,$scheduleCurrent);
		$jsScheduleFileId = 0;
		$currentScheduleName = '';
		$schedulesList = array();
		foreach ( Schedule::getScheduleNames($locId) as $hn) {
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
					$currentScheduleName = $hn['name'];
				}
				//$options .= '>' . ($hn['type'] != 'T' ? '(' . $hn['type'] . ') ' : '') . $hn['name'] . '</option>';
				$options .= '>' . $hn['name'] . '</option>';
				$empty = $hn['id'] == 0;
			}
		}
		$replace_console['#scheduleFileId#'] = $jsScheduleFileId;
		$replace_console['#scheduleOptions#'] = $options;
		$replace_console['#currentScheduleName#'] = $currentScheduleName;

		// indicateur schedule modifié
		$saveColor = 'white';
		$canRestoreCurrent = 0;
		$saveTitle = honeywell::i18n("Sauvegarde la programmation courante");
		$scheduleDelta = honeywell::SCHEDULE_DELTA_OFF;
		if ( !$empty && $scheduleFileId != null ) {
			$scheduleSaved = Schedule::getSchedule($locId,$scheduleFileId);
			if ( $scheduleSaved != null && $scheduleCurrent != null ) {
				$_scheduleSaved = json_encode($scheduleSaved['zones']);
				$_scheduleCurrent = json_encode($scheduleCurrent['zones']);
				if ( $_scheduleSaved != $_scheduleCurrent ) {
					$saveColor = 'orange';
					$canRestoreCurrent = 1;
					$scheduleDelta = honeywell::SCHEDULE_DELTA_ON;
					$saveTitle .= ' (' . honeywell::i18n("différente de la dernière programmation restaurée ou éditée") . ')';
				}
			}
		}
		honeyutils::setCacheData(honeywell::CACHE_SCHEDULE_DELTA, $scheduleDelta, null, $locId);
		$replace_console['#title.save#'] = $saveTitle;
		$replace_console['#canRestoreCurrent#'] = $canRestoreCurrent;
		$replace_console['#isAdmin#'] = honeyutils::isAdmin();
		$replace_console['#evoSaveColor#'] = $saveColor;

		foreach ($equ->getCmd('action') as $cmd) {
			$replace_console['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		// arguments names
		$replace_console['#argCodeMode#'] = honeywell::ARG_CODE_MODE;
		$replace_console['#argFileName#'] = honeywell::ARG_FILE_NAME;
		$replace_console['#argFileId#'] = honeywell::ARG_FILE_ID;
		$replace_console['#argZoneId#'] = honeywell::ARG_ZONE_ID;
		$replace_console['#argFileRem#'] = honeywell::ARG_FILE_REM;
		// codes mode allowed
		$replace_console['#codesAllowed#'] = json_encode($equ->getConfiguration(honeywell::CONF_ALLOWED_SYSTEM_MODE));
		// modes array - 28-03-2020 - Lyric needs : only modes available, ie: schefule file of type must exists, but for current mode, of course
		$replace_console['#modesArray#'] = json_encode($equ->getJSModesArray($currentModeCode,$schedulesList));
		// Lyric needs : the popup for the modes should show the list of schedule files for the mode selected (and only then, accept the mode)
		$replace_console['#schedulesList#'] = $equ->isEvohome() ? 'null' : json_encode($schedulesList);
		$replace_console['#showHorizontal#'] = honeywell::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_console['#displaySetModePopup#'] = honeyutils::getParam(honeywell::CFG_SHOWING_MODES,honeywell::CFG_SHOWING_MODE_CONSOLE) == honeywell::CFG_SHOWING_MODE_POPUP ? "visible" : "none";
		$replace_console['#displaySetModeConsole#'] = honeyutils::getParam(honeywell::CFG_SHOWING_MODES,honeywell::CFG_SHOWING_MODE_CONSOLE) == honeywell::CFG_SHOWING_MODE_CONSOLE ? "1" : "0";
		$replace_console['#evoDefaultShowingScheduleMode#'] = honeyutils::getParam(honeywell::CFG_DEF_SHOW_SCHEDULE_MODE,honeywell::CFG_SCH_MODE_HORIZONTAL);

		// i18n
		$rbs = honeyutils::getParam(honeywell::CFG_REFRESH_BEFORE_SAVE,0);
		$msg = array('scheduleTitle'=>$thMode['lblSchedule'],
					'mScheduleTitle'=>"Programme actif",
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
		foreach ( $msg as $code=>$txt ) $replace_console["#$code#"] = honeywell::i18n($txt);

		$replace_console['#console_min_js_size#'] = filemtime(dirname(__FILE__) . '/../../template/dashboard/console_min.js');
		$replace['#consoleContent#'] = template_replace($replace_console, getTemplate('core', $version, 'console_content', honeywell::PLUGIN_NAME));
		$replace['#temperatureContent#'] = '';

		$replace['#batteryImgDisplay#'] = 'none';
		$replace['#batteryImg#'] = 'empty.svg';
		$replace['#batteryImgTitle#'] = '';
		
		$replace['#reloadDisplay#'] = honeywell::RELOAD == true ? 'flex' : 'none';
		$replace['#lblSelectedProgram#'] = honeyutils::i18n("Programme sélectionné");

		// Mobile
		$replace['#mLblPresence#'] = honeywell::i18n("Mode de présence");
		$replace['#mLblValidate#'] = honeywell::i18n("Valider");
		
		$cmdBoilerCall = $equ->getCmd('info',honeywell::CMD_BOILER_REQUEST);
		$nbRequesters = honeyutils::readInfo($equ, honeywell::CMD_BOILER_REQUEST);
		$replace['#boilerRequestDisplay#'] = $cmdBoilerCall->getIsVisible() && $nbRequesters > 0 ? 'display' : 'none';
		$replace['#boilerRequestLabel#'] = honeywell::i18n("Système en demande");
		$replace['#boilerRequestId#'] = is_object($cmdBoilerCall) ? $cmdBoilerCall->getId() : '';
		$replace['#boilerRequestTitle#'] = honeywell::i18n("{0} thermostat(s) en demande", $nbRequesters);
	}

	static function refreshConsole($locId, $msgInfo='', $taskIsRunning=false) {
		honeywell::refreshComponent(array("zoneId"=>$locId, "taskIsRunning"=>$taskIsRunning), $msgInfo);
	}

	static function actionSetMode($equ,$locId,$parameters) {
		$inCodeMode = $parameters[honeywell::ARG_CODE_MODE];
		if ( $inCodeMode === null || $inCodeMode === '' ) {
			honeyutils::logDebug("actionSetMode called without code");
			return;
		}
		if ( $inCodeMode == self::SM_PREVIOUS_MODE_CODE ) {
			$lblCodeMode = honeyutils::readInfo($equ, self::CMD_STATE_PREVIOUS);
			if ( $lblCodeMode == null ) {
				honeyutils::logDebug("actionSetMode : can't request previous mode as it does not exist yet");
				return;
			}
			$codeMode = $equ->getModeFromHName($lblCodeMode);
		} else {
			$codeMode = $inCodeMode;
		}
		$currentMode = $equ->getModeFromHName(honeyutils::readInfo($equ, self::CMD_STATE_CURRENT));
		if ( $currentMode == $codeMode ) {
			honeyutils::logDebug("actionSetMode : requested mode same as current : no action");	
			return;
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSetMode with inCode=$inCodeMode, code=$codeMode");
		$execUnitId = rand(0,10000);
		honeywell::waitingIAZReentrance("SetMode-$execUnitId");
		honeyutils::lockCron();

		// ******************************
		$obj = $equ->iGetInstance();
		$aRet = $obj->iSetMode($execUnitId,$locId,$codeMode);
		// ******************************

		$success = false;
		if ( !is_array($aRet) ) {
			honeyutils::logError("Error while actionSetMode : response was empty or malformed", $aRet);
			$msgInfo = honeywell::i18n("Erreur en changement de mode");
		} else if ( !$aRet[honeywell::SUCCESS] ) {
			honeyutils::logError("Error while actionSetMode", $aRet);
			$msgInfo = honeywell::i18n("Erreur en changement de mode : {0} - {1}", [$aRet["code"], $aRet["message"]]);
		} else {
			if ( $aRet["system"] == honeywell::SYSTEM_EVOHOME ) {
				self::refreshConsole($locId, "1".honeywell::i18n("Rechargement des données en attente..."), true);
				sleep(10);	// wait a bit before loading new values
			}
			$msgInfo = "1".honeywell::i18n("Le mode de présence a été correctement modifié");
			$success = true;
		}
		if ( $success ) $equ->getInformations(true,true,"1".honeywell::i18n('Rechargement des données en cours...'),true);
		self::refreshConsole($locId,$msgInfo);

		honeyutils::logDebug("<<OUT - actionSetMode");
		honeyutils::unlockCron();
	}

	static function doAction($equ,$locId,$paramAction,$parameters) {
		switch ($paramAction) {
			case self::CMD_SET_MODE:
				self::actionSetMode($equ,$locId,$parameters);
				return true;
		}
		return false;
	}

}