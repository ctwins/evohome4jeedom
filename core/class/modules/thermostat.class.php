<?php

/**
 * This class appears with version 0.5.3, and content is extract from honeywell.class.php
 * and its content is dedicated parts about Thermostat functionalities.
 * 
 * @author ctwins95
 *
 */
class TH {
	// TH *************************************************
	const CMD_TEMPERATURE_ID = 'temperature';
	const CMD_CONSIGNE_ID = 'consigne';
	const CMD_SCH_CONSIGNE_ID = 'progConsigne';
	const CMD_CONSIGNE_TYPE_ID = 'consigneType';
	const CMD_SET_CONSIGNE_ID = 'setConsigne';
	// -- detailed infos
	const CMD_CT_STATUS = 'thStatus';
	const CMD_CT_UNTIL = 'thUntil';
	const CMD_CT_UNIT = 'thUnit';
	const CMD_CT_ADJUST_STEP = 'thAdjustStep';
	const CMD_CT_MIN_HEAT = 'thMinHeat';
	const CMD_CT_MAX_HEAT = 'thMaxHeat';
	const CMD_CT_PREVIOUS = 'thPRevious';
    const CMD_CT_DIFF_PREV = 'thDiffPrevious';
	// ---- Evohome/(Round ?) Only
	const CMD_CT_BATTLOW = 'thBattLowTime';
	const CMD_CT_CNXLOST = 'thCnxLostTime';
	// ---- Lyric only
	//const CMD_CT_END_HEAT_SP = 'thEndHeatSetPoint';
	const CMD_CT_HEATING = 'thHeating';

	// Common
	const SET_TH_MODE_PERMANENT = 'STM_1';
	const SET_TH_MODE_UNTIL_CURRENT_SCH = 'STM_2';
	// -- Evohome/Round
	const SET_TH_MODE_UNTIL_HHMM = 'STM_3';
	const SET_TH_MODE_UNTIL_END_OF_DAY = 'STM_4';
	const SET_TH_MODE_UNTIL_END_OF_PERIOD = 'STM_5';	// Lyric+Geofence case

	static function postSave($equ) {
		honeyutils::logDebug('postSave - create TH widget');
		$i = 0;
		//$equ->deleteCmd([honeywell::CMD_STATE, honeywell::CMD_SET_MODE, honeywell::CMD_SAVE, honeywell::CMD_RESTORE, honeywell::CMD_DELETE, honeywell::CMD_STATISTICS_ID]);
		$equ->createOrUpdateCmd($i++, self::CMD_TEMPERATURE_ID, 'Température', 'info', 'numeric', 1, 1);
		$equ->createOrUpdateCmd($i++, self::CMD_CONSIGNE_ID, 'Consigne', 'info', 'numeric', 1, 1);
		$equ->createOrUpdateCmd($i++, self::CMD_SCH_CONSIGNE_ID, 'Consigne programmée', 'info', 'numeric', 0, 1);
		$equ->createOrUpdateCmd($i++, self::CMD_CONSIGNE_TYPE_ID, 'Type Consigne', 'info', 'string', 0, 0);	// 0.4.1 - no display usage
		$equ->createOrUpdateCmd($i++, self::CMD_SET_CONSIGNE_ID, 'Set Consigne', 'action', 'select', 1, 0);
		// -- Commandes INFO détaillées
		$equ->createOrUpdateCmd($i++, self::CMD_CT_STATUS,   'Status', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_UNTIL,    'Jusqu\'à', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_UNIT,     'Unité', 'info', 'string', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_ADJUST_STEP, 'Intervalle', 'info', 'numeric', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_MIN_HEAT, 'Temp. min', 'info', 'numeric', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_MAX_HEAT, 'Temp. max', 'info', 'numeric', 0, 0);
		$equ->createOrUpdateCmd($i++, self::CMD_CT_PREVIOUS, 'Temp. précédente', 'info', 'numeric', 0, 0);
        $equ->createOrUpdateCmd($i++, self::CMD_CT_DIFF_PREV, 'Ecart temp. précédente', 'info', 'numeric', 0, 0);
		if ( !$equ->isLyric() ) {
			$equ->createOrUpdateCmd($i++, self::CMD_CT_BATTLOW,  'Date/Heure Batt.faible', 'info', 'string', 0, 0);
			$equ->createOrUpdateCmd($i, self::CMD_CT_CNXLOST,  'Date/Heure Cnx.perdue', 'info', 'string', 0, 0);
		} else {
			//$equ->createOrUpdateCmd($i++, self::CMD_CT_END_HEAT_SP, 'endHeatSpValue', 'info', 'numeric', 0, 1);
			$equ->createOrUpdateCmd($i, self::CMD_CT_HEATING, 'Chauffe en cours', 'info', 'numeric', 0, 1);
		}
	}
	
	static function fillSetConsigneData($cmd,$zoneId,$minHeat,$maxHeat,$doSave=false) {
		// 0.4.1 - 1st choice to go back to the scheduled value
		$list = (new SetConsigneData(SetConsigneData::AUTO, $zoneId, 0, 0, null))->buildStrForSelect(honeywell::i18n("Annulation (retour à la valeur programmée)"));
		// 0.9 is the supposed value for the °F... (0.5 * 9/5)
		$unit = honeyutils::getParam(honeywell::CFG_TEMP_UNIT,honeywell::CFG_UNIT_CELSIUS);
		$step = $unit == honeywell::CFG_UNIT_CELSIUS ? 0.5 : 0.9;
		if ( honeyutils::isDebug() ) honeyutils::logDebug("adjust min=$minHeat/max=$maxHeat/step=$step of the SET_CONSIGNE command on zone=$zoneId");
		for( $t=$minHeat ; $t<=$maxHeat ; $t+=$step ) {
			// auto means the callback function must check availability of service (presence mode / api available)
			$list .= ";" . (new SetConsigneData(SetConsigneData::AUTO, $zoneId, $t, $t, null))->buildStrForSelect($t."°".$unit);
		}
		$cmd->setConfiguration('listValue', $list);
		$cmd->setConfiguration('minHeat', $minHeat);
		$cmd->setConfiguration('maxHeat', $maxHeat);
		if ( $doSave ) {
			$cmd->save();
		}
	}

	static function injectInformations($equ, $infosZones, $zoneId) {
		honeyutils::logDebug("** Inject TH...");
		$infosZone = honeyutils::extractZone($infosZones,$zoneId);
		if ( $infosZone == null ) {
			honeyutils::logError("<<OUT - injectInformationsFromZone - no data found on zone $zoneId");
			return;
		}
		$temp = honeywell::adjustByUnit($infosZone['temperature'],$infosZone['units']);
		$previousTemp = honeyutils::saveInfo($equ, self::CMD_TEMPERATURE_ID, $temp, 0);
		honeyutils::saveInfo($equ, self::CMD_CONSIGNE_ID, honeywell::adjustByUnit($infosZone['setPoint'], $infosZone['units']));
		$locId = $equ->getLocationId();
		$consigneScheduled = Schedule::getConsigneScheduledForZone($locId,$infosZone,$infosZone['units'],Schedule::getScheduleType($infosZones))['TH'];
		honeyutils::saveInfo($equ ,self::CMD_SCH_CONSIGNE_ID, honeywell::adjustByUnit($consigneScheduled, $infosZone['units']));
		
		$consigneInfos = ConsigneInfos::buildStr($infosZone);
		honeyutils::saveInfo($equ, self::CMD_CONSIGNE_TYPE_ID, $consigneInfos);

		// 0.4.1 - auto-adjust the list of available values for the SET_CONSIGNE action if min or max has changed :
		$tmp = $equ->getCmd(null,self::CMD_SET_CONSIGNE_ID);
		$oCI = ConsigneInfos::buildObjFromStr($consigneInfos);
		if (is_object($tmp) && (intval($tmp->getConfiguration('minHeat')) != $oCI->minHeat || intval($tmp->getConfiguration('maxHeat')) != $oCI->maxHeat) ) {
			self::fillSetConsigneData($tmp,$zoneId,$oCI->minHeat,$oCI->maxHeat,true);
		}
		// Detailed infos
		honeyutils::saveInfo($equ, self::CMD_CT_STATUS, $oCI->status);
		honeyutils::saveInfo($equ, self::CMD_CT_UNTIL, $oCI->until);
		honeyutils::saveInfo($equ, self::CMD_CT_UNIT, $oCI->unit);
		honeyutils::saveInfo($equ, self::CMD_CT_ADJUST_STEP, $oCI->adjustStep);
		honeyutils::saveInfo($equ, self::CMD_CT_MIN_HEAT, $oCI->minHeat);
		honeyutils::saveInfo($equ, self::CMD_CT_MAX_HEAT, $oCI->maxHeat);
		honeyutils::saveInfo($equ, self::CMD_CT_PREVIOUS, $previousTemp);
        honeyutils::saveInfo($equ, self::CMD_CT_DIFF_PREV, $temp-$previousTemp);
		if ( !$equ->isLyric() ) {
			// ---- Evohome/(Round ?) Only
			honeyutils::saveInfo($equ, self::CMD_CT_BATTLOW, $oCI->timeBattLow);
			honeyutils::saveInfo($equ, self::CMD_CT_CNXLOST, $oCI->timeCnxLost);
		} else {
			// ---- Lyric only
			//honeyutils::saveInfo($equ, self::CMD_CT_END_HEAT_SP, $oCI->endHeatSetpoint);	// if TemporaryHold or HoldUntil, the endHeatSetpoint value
			honeyutils::saveInfo($equ, self::CMD_CT_HEATING, $oCI->heating);
		}

		if ( honeyutils::isDebug() ) {
			honeyutils::logDebug("zone$zoneId=" . $infosZone['name'] . " : temp = " . $infosZone['temperature'] . ", consigne = " . $infosZone['setPoint'] . ", consigneInfos = $consigneInfos");
		}
	}

	static function toHtml($equ,$pVersion,$version,&$replace,$scheduleCurrent) {
		$zoneId = $equ->getLogicalId();
		$forcedConsigne = $equ->getToHtmlProperty("forcedConsigne");

		$replace_TH = $equ->preToHtml($pVersion);
		$locId = $equ->getLocationId();
		$replace_TH['#locId#'] = $locId;
		$replace_TH['#argLocId#'] = honeywell::ARG_LOC_ID;
		$replace_TH['#zoneId#'] = $zoneId;
		$replace_TH['#fileId#'] = Schedule::getCfgScheduleFileId($locId,$scheduleCurrent);

		// *** TEMPERATURE
		$replace_TH['#etatImg#'] = 'empty.svg';
		$replace_TH['#etatUntilImg#'] = 'empty.svg';

		$cmdTemperature = $equ->getCmd(null,self::CMD_TEMPERATURE_ID);
		$cmdId = is_object($cmdTemperature) ? $cmdTemperature->getId() : '';
		$replace_TH['#temperatureId#'] = $cmdId;
		$replace_TH['#temperatureDisplay#'] = (is_object($cmdTemperature) && $cmdTemperature->getIsVisible()) ? "block" : "none";
		$temperatureNative = is_object($cmdTemperature) ? $cmdTemperature->execCmd() : 0;
		if ( $temperatureNative == null ) {
			$temperature = 0;
			$replace_TH['#temperature#'] = '';
			$replace_TH['#temperatureDisplay2#'] = 'none';
		} else {
			$temperature = honeywell::applyRounding($temperatureNative);
			$replace_TH['#temperature#'] = $temperature . '°';
			$replace_TH['#temperatureDisplay2#'] = 'table-cell';
		}

		// *** CONSIGNE
		$cmdConsigne = $equ->getCmd(null,self::CMD_CONSIGNE_ID);
		$replace_TH['#consigneId#'] = is_object($cmdConsigne) ? $cmdConsigne->getId() : '';
		$replace_TH['#consigneDisplay#'] = (is_object($cmdConsigne) && $cmdConsigne->getIsVisible()) ? "block" : "none";
		$consigne = $forcedConsigne != null ? $forcedConsigne : (is_object($cmdConsigne) ? $cmdConsigne->execCmd() : 0);
		$currentMode = Console::getCurrentMode($locId);

		$consigneInfos = ConsigneInfos::buildObj($equ);

		// forcer l'arrondi en mode HNW pour détection demande chauffe
		$tempHNW = honeywell::applyRounding($temperatureNative,honeywell::CFG_ACC_HNW);
        switch ($temperatureNative == null ? 0 : ($consigneInfos->heating == 1 || $tempHNW < $consigne ? 2 : 1) ) {
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
		$thMode = $equ->getThModes($currentMode,null,$consigneInfos);
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
		$consignePair = Schedule::getConsigneScheduled($locId,$scheduleCurrent,$zoneId);
		//if ( !$isScheduling ) {
			//$replace_TH['#currentConsigne#'] = 0;
			//$replace_TH['#currentConsigneUntil#'] = '';
			//$consigneScheduled = (count($consigneInfos) < 9 or $consigneInfos[9] == null) ? null : honeywell::adjustByUnit( $consigneInfos[9],$consigneInfos[2]);
		//}
		$consigneScheduled = $consignePair == null ? null : honeywell::adjustByUnit($consignePair['TH'],$consigneInfos->unit);	// 0.4.1 - adjust added
		//  additional infos
		$replace_TH['#currentConsigneUntil#'] = $consignePair == null ? '' : $consignePair['UNTIL'];
		$sConsigneScheduled = $consigneScheduled == null ? ("[".honeywell::i18n("non déterminé")."]") : $consigneScheduled;
		$replace_TH['#currentConsigne#'] = $consigneScheduled == null ? 0 : $consigneScheduled;

		if ( $isOff ) $infoConsigne = 'OFF';
		else if ( $consigne == null ) $infoConsigne = '-';
		else $infoConsigne = $consigne . '°';
		$replace_TH['#consigne#'] = $infoConsigne;
		$replace_TH['#consigneNoUnit#'] = $consigne;
		$replace_TH['#consigneBG#'] = honeywell::getBackColorForTemp($consigne,$isOff);

		$adjustAvailable = true;
		$consigneTypeImg = null;
		if ( $consigneInfos != null ) {
			# $consigneInfos->status = for Evohome : FollowSchedule / PermanentOverride / TemporaryOverride
			$consigneTip = '';
			$consigneTypeUntil = '';
			$consigneTypeUntilFull = '';
			if ( $isEco ) {
				$consigneTypeUntilFull = honeywell::i18n("Mode économie (remplace {0}°)", $sConsigneScheduled);
				$consigneTypeImg = 'i_economy_white.png';
				// $adjustAvailable = true;		available when ECO mode
			} else if ( $isDayOff ) {
				$consigneTypeUntilFull = honeywell::i18n("Mode congé");
				$consigneTypeImg = 'i_dayoff_white.png';
				// $adjustAvailable = true;		available when DAY-OFF mode
			} else if ( $isCustom ) {
				$consigneTypeUntilFull = honeywell::i18n("Mode personnalisé");
				$consigneTypeImg = 'i_custom_white.png';
				// $adjustAvailable = true;		available when CUSTOM mode
			}
			if ( $isOff ) {
				$consigneTypeUntilFull = $sConsigneScheduled == null ? honeywell::i18n("Consigne forcée à {0}°", $consigne) : honeywell::i18n("Consigne forcée à {0}° au lieu de {1}°", [$consigne, $sConsigneScheduled]);
				$consigneTypeImg = 'i_off_white.png';
				$adjustAvailable = false;
			} else if ( $isAway ) {
				$consigneTypeUntilFull = honeywell::i18n("Mode inoccupé (remplace {0}°)", $sConsigneScheduled);
				$consigneTypeImg = 'i_away_white.png';
				$adjustAvailable = false;		// unavailable when AWAY mode
			} else if ( !$isEco &&!$isDayOff && !$isCustom && $isFollow ) {
				if ( $consigneScheduled != null && $consigne != null ) {
					// SetPoint was auto-adjusted, let's see :
					if ( $consigne < $consigneScheduled ) {
						$minHeat = $consigneInfos->minHeat;
						if ( $consigne == $minHeat ) {
							$consigneTypeUntilFull = honeywell::i18n("Fenêtre ouverte détectée");
							$consigneTypeImg = 'o-window.png" style="height:15px;';
						} else {
							$consigneTypeUntilFull = honeywell::i18n("Optimisation active : consigne inférieure à suivre active (remplace {0}°)", $consigneScheduled);
							//$consigneTypeImg = 'down green.svg';
							$consigneTypeImg = 'down green.png';
						}
					} else if ( $consigne > $consigneScheduled ) {
						$consigneTypeUntilFull = honeywell::i18n("Optimisation active : consigne supérieure à suivre active (remplace {0}°)", $consigneScheduled);
						$consigneTypeImg = 'up red.svg';
					}
				}
			} else if ( $isTemporary ) {
				$consigneTip = '';
				$consigneTypeImg = 'temp-override.svg';
				// example : $consigneInfos->until = "2018-01-28T23:00:00Z"
				$time = honeyutils::gmtToLocalHM($consigneInfos->until);
				$consigneTypeUntil = $time;
				$consigneTypeUntilFull = $sConsigneScheduled == null ? honeywell::i18n("Forçage de la consigne programmée jusqu'à {0}", $time) : honeywell::i18n("Forçage de la consigne programmée de {0}° jusqu'à {1}", [$sConsigneScheduled, $time]);
			} else if ( $isPermanent ) {
				$consigneTypeImg = 'override-active.png';
				$consigneTypeUntilFull = $sConsigneScheduled == null ? honeywell::i18n("Forçage de la consigne programmée") : honeywell::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			}
			$replace_TH['#consigneTypeUntil#'] = $consigneTypeUntil;
			$replace_TH['#consigneTypeUntilFull#'] = $consigneTypeUntilFull;
			$replace_TH['#consigneTip#'] = $consigneTip;
		}
		
		$cmdSetConsigne = $equ->getCmd(null,self::CMD_SET_CONSIGNE_ID);
		if ( is_object($cmdSetConsigne) && !$cmdSetConsigne->getIsVisible() ) {
			$replace_TH['#setConsigneDisplayV1#'] = "none";
			$replace_TH['#setConsigneDisplayV2#'] = "none";
		} else {
			$typeAdjust = honeyutils::getParam(honeywell::CFG_HP_SETTING_MODES,honeywell::CFG_HP_SETTING_MODE_INTEGRATED) == honeywell::CFG_HP_SETTING_MODE_INTEGRATED ? 1 : 2;
			$replace_TH['#setConsigneDisplayV1#'] = $typeAdjust == 1 ? "table-cell" : "none";
			$replace_TH['#setConsigneDisplayV2#'] = $typeAdjust == 2 ? "table-cell" : "none";
			// adjust temp infos
			$replace_TH['#adjustAvailable#'] = $adjustAvailable ? 'true' : 'false';
			$replace_TH['#msgAdjustConsigneUnavailable#'] = honeywell::i18n("Le mode actif ne permet pas d'ajuster les consignes");
			$replace_TH['#msgEnforceConsigne#'] = honeywell::i18n("Forçage de la consigne programmée de {0}°", $sConsigneScheduled);
			$replace_TH['#adjustStep#'] = $consigneInfos->adjustStep;
			$replace_TH['#canReset#'] = $consigneScheduled == null || $consigneScheduled == $consigne ? 0 : 1;
			$replace_TH['#backScheduleTitle#'] = $consigneScheduled == null ? '' : honeywell::i18n('Retour à la valeur programmée de {0}°', $consigneScheduled);
		}
		$replace_TH['#adjustLow#'] = $consigneInfos->minHeat;
		$replace_TH['#adjustHigh#'] = $consigneInfos->maxHeat;
		$replace_TH['#consigneTypeImg#'] = $consigneTypeImg == null ? 'empty.svg' : $consigneTypeImg;
		$replace_TH['#consigneTypeDisplay#'] = $consigneTypeImg == null ? 'none' : 'inline-block';
		// arguments names
		$replace_TH['#argFileId#'] = honeywell::ARG_FILE_ID;
		$replace_TH['#argZoneId#'] = honeywell::ARG_ZONE_ID;
		// codes
		$replace_TH['#showHorizontal#'] = honeywell::CFG_SCH_MODE_HORIZONTAL;
		// configuration
		$replace_TH['#evoDefaultShowingScheduleMode#'] = honeyutils::getParam(honeywell::CFG_DEF_SHOW_SCHEDULE_MODE,honeywell::CFG_SCH_MODE_HORIZONTAL);

		// Info Batterie (A)
		$tmp = $consigneInfos->timeCnxLost == '' && $temperatureNative != null ? '' : ($temperatureNative == null  ? honeywell::i18n("Connexion perdue (date inconnue), batterie HS") : honeywell::i18n("Connexion perdue depuis {0}, batterie HS", honeyutils::gmtToLocalDateHMS($consigneInfos->timeCnxLost)));
		$replace_TH['#temperatureImgTitle#'] = $tmp == '' ? '' : 'title="' . $tmp . '"';

		// fix 7 - error reported by TLoo - 2019-02-09 - btw, plugin without Console is uncomplete ;)
		$timeWindow = Console::getTimeWindow($locId,$cmdId);
		$replace_TH['#minMaxDisplay#'] = $timeWindow == 0 ? "none" : "block";
		if ( $timeWindow == 0 ) {
			$replace_TH['#statDelta#'] = '&nbsp;';
			$replace_TH['#statDeltaTitle#'] = '';
			$replace_TH['#deltaDisplay#'] = 'none';
			$replace_TH['#deltaImg#'] = 'empty.svg';
		} else {
			$temperature = honeywell::applyRounding($temperatureNative);
			$replace_TH['#statDelta#'] = $temperature == 0 ? '' : ($temperature > $consigne ? '+' : '') . round($temperature - $consigne,2) . '°';
			$replace_TH['#statDeltaTitle#'] = honeywell::i18n("Ecart consigne");
			$delta = $consigneInfos->previousTemp == 0.0 ? 0.0 : round($temperature - $consigneInfos->previousTemp,2);
			$replace_TH['#deltaDisplay#'] = $delta == 0.0 ? "none" : "inline-block";
			$replace_TH['#deltaValue#'] = ($delta > 0.0 ? "+" : "") . honeywell::i18n("{0}° depuis la précédente mesure", $delta);
			$replace_TH['#deltaImg#'] = $delta > 0.0 ? 'green-up-anim.gif' : 'red-down-anim.gif';
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
				$replace_TH['#statTitle#'] = honeywell::i18n($timeWindow == 1 ? "Statistiques du jour" : ($timeWindow == 2 ? "Statistiques de la semaine" : "Statistiques du mois"));
				$replace_TH['#statRazTimeTitle#'] = honeywell::i18n("valeurs réinitialisées");
				$replace_TH['#statRazTime#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS(strtotime($results[0]['datetime'])) : $results[0]['datetime'];

				$replace_TH['#statLastReadTitle#'] = honeywell::i18n("dernière lecture");
				$replace_TH['#statLastRead#'] = honeyutils::tsToLocalHMS(strtotime($results[count($results)-1]['datetime']));

				$replace_TH['#statMaxTitle#'] = honeywell::i18n("max");
				$replace_TH['#statThMax#'] = honeywell::applyRounding($max) . '°';
				$replace_TH['#statWhenMax#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS($dMax) : $sDateMax;
				$replace_TH['#statWhenMinus1#'] = $dMaxMinus1 == 0 ? '(' . honeywell::i18n("pas encore") . ')' : honeyutils::tsToAbsoluteHM($dMaxMinus1);

				$replace_TH['#statAvgTitle#'] = honeywell::i18n("moy");
				$replace_TH['#statThAvg#'] = honeywell::applyRounding($avg) . '°';
				$replace_TH['#statNbPoints#'] = honeywell::i18n("{0} points", count($results));

				$replace_TH['#statMinTitle#'] = honeywell::i18n("min");
				$replace_TH['#statThMin#'] = honeywell::applyRounding($min) . '°';
				$replace_TH['#statWhenMin#'] = $timeWindow == 1 ? honeyutils::tsToLocalHMS($dMin) : $sDateMin;
				$replace_TH['#statWhenPlus1#'] = $dMinPlus1 == 0 ? '(' . honeywell::i18n("pas encore") . ')' : honeyutils::tsToAbsoluteHM($dMinPlus1);
			}
		}

		foreach ($equ->getCmd('action') as $cmd) {
			$replace_TH['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		$replace['#consoleContent#'] = '';
		$replace_TH['#temperature_min_js_size#'] = filemtime(dirname(__FILE__) . '/../../template/dashboard/temperature_min.js');
		$replace['#temperatureContent#'] = template_replace($replace_TH, getTemplate('core', $version, 'temperature_content', honeywell::PLUGIN_NAME));

		// Battery info (B)
		$replace['#batteryImgDisplay#'] = $consigneInfos->timeBattLow . $consigneInfos->timeCnxLost === '' && $temperatureNative != null ? 'none' : 'flex';
		$replace['#batteryImg#'] = $consigneInfos->timeCnxLost != '' || $temperatureNative == null ?
		                  'batt-hs-small.png' :
		                  ($consigneInfos->timeBattLow != '' ? 'batt-low-small.png' : 'empty.svg');
		$replace['#batteryImgTitle#'] = $consigneInfos->timeCnxLost != '' ?
		  honeywell::i18n("Batterie HS depuis {0}", honeyutils::gmtToLocalDateHMS($consigneInfos->timeCnxLost)) :
		  ($temperatureNative == null ? honeywell::i18n("Batterie HS (date inconnue)") :
		                                ($consigneInfos->timeCnxLost != '' ? honeywell::i18n("Batterie faible depuis {0}", honeyutils::gmtToLocalDateHMS($consigneInfos->timeCnxLost)) : ''));
		$replace['#reloadDisplay#'] = 'none';

		// new 0.4.1 - Adjust TH - labels go to i18n
		$replace['#lblAdjTHTitle1#'] = honeywell::i18n("Modification de la consigne sur '{0}'");
		//$replace['#lblAdjTHTitle2#'] = honeywell::i18n("La consigne de {0}° sera maintenue :");
		$replace['#setThModes#'] = json_encode($thMode['setThModes']);

		// new 0.4.1 - adjust background title of the widgets
		$bctMode = honeyutils::getParam(honeywell::CFG_BACKCOLOR_TITLE_MODES,honeywell::CFG_BCT_MODE_NONE);
		$THcolor = 'var(--link-color)';
		//honeyutils::logDebug("bctMode = $bctMode");
		if ( $bctMode == honeywell::CFG_BCT_MODE_NONE ) {
			// nothing to do
			$tA = "rgb(0,0,0,0)";
			$tB = "rgb(0,0,0,0)";
			$tBp = $tB;
		} else if ( $bctMode == honeywell::CFG_BCT_MODE_SYSTEM ) {
			$tA = $replace['#background-color#'];
			$tB = $replace['#background-color#'];
			if ( jeedom::version() < 4 ) $THcolor = '#fff';
			$tBp = $tB;
		} else if ( $bctMode == honeywell::CFG_BCT_MODE_2T ) {
			if ( $temperature >= intval(honeyutils::getParam(honeywell::CFG_BCT_2N_B,28)) ) {
				$tA = /*backgroundTopGradient ? "rgb(255,0,0,0)" :*/ "rgb(255,50,0,1)";
				$tB = "rgb(255,50,0,1)";
				$THcolor = '#fff';
			} else if ( $temperature >= intval(honeyutils::getParam(honeywell::CFG_BCT_2N_A,26)) ) {
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
			foreach ( honeywell::C2BG as $tr=>$bgRef ) {
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
		$presence = Console::getPresence($locId);
		//honeyutils::logDebug("Presence($locId) => $presence");
		$replace['#presenceDisplay#'] = $presence == State::PRESENCE_UNDEFINED ? 'none' : 'inline';
		$replace['#presenceImg#'] = $presence == State::PRESENCE_UNDEFINED ? 'empty.svg' : ($presence == State::PRESENCE_INSIDE ? 'inside.png' : 'outside.png');
	}
	
	static function getAdjustData($locId) {
	    $adjData = array();
		foreach ( honeywell::getEquipmentsForLoc($locId) as $equ) {
			if ( $equ->getLogicalId() != $locId ) {
				$consigneInfos = ConsigneInfos::buildObj($equ);
				$adjData[$equ->getLogicalId()] = array("min"=>$consigneInfos->minHeat,"max"=>$consigneInfos->maxHeat,"step"=>$consigneInfos->adjustStep);
			}
		}
		$ret = json_encode($adjData);
		honeyutils::logDebug("getAdjustData - a : " . $ret);
	    return $ret;
	}

	static function isConsigneUnsettable($equ,$locId) {
		return !$equ->getModesArray()[Console::getCurrentMode($locId)]->consigneUnsettable;
	}

	static function transformUntil($hm) {
		if ( $hm == '99:99' ) $hm = '23:59';
		$hm = explode(":",$hm);
		$date = new DateTime();
		$date->setTime((int)$hm[0],(int)$hm[1],0);
		return honeyutils::localDateTimeToGmtZ($date);
	}

	static function actionSetConsigne($equ,$locId,$parameters) {
		$sParams = $parameters[honeywell::ARG_CONSIGNES_DATA];
		if ( $sParams == null || $sParams == "" ) {
			honeyutils::logError(honeywell::i18n("Par Scénario : Set Consigne : paramètre reçu invalide (le choix 'Aucun' dans la liste déroulante ne peut pes être évité, mais il est inutile !)"));
			return;
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSetConsigne($sParams)");
		// 0.4.3bis - separators become '§'
		$params = SetConsigneData::buildFromStr($sParams);
		$zoneId = $params->zoneId;
		$prefixByScenario = $params->mode == SetConsigneData::AUTO ? honeywell::i18n("Par Scénario") . " : " : "";

		$infosZones = $equ->getInformations();
		if ( !is_array($infosZones) ) {
			honeyutils::logError("Consigne - error while getInformations");
			honeyutils::logDebug("<<OUT - actionSetConsigne");
			return;
		}
		$cmdConsigne = $equ->getCmd(null,TH::CMD_CONSIGNE_ID);
		$oldConsigneInfos = ConsigneInfos::buildObj($equ);
		if ( is_object($cmdConsigne) && is_object($oldConsigneInfos) ) {
			$oldConsigne = $cmdConsigne->execCmd();	// btw, equ value is against unit chosen
			$oldStatus = $oldConsigneInfos->status;
			$oldUntil = $oldConsigneInfos->until;
			$deviceUnit = $oldConsigneInfos->unit;
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

		$newValue = $params->t1 == 0 ? 0 : honeywell::revertAdjustByUnit($params->t1,$deviceUnit);	// 0.4.1 - convert if needed
		if ( $params->t2 == 0 ) {
			$tmp = $equ->getCmd(null,TH::CMD_SCH_CONSIGNE_ID);
			$params->t2 = $tmp->execCmd();	// btw, equ value is against unit chosen
		}
		$realValue = honeywell::revertAdjustByUnit($params->t2,$deviceUnit);

		// $data = 'manuel/auto # zoneId # value (nn.n or 0=reset) # realValue # until ('null' or 'timevalue'
		$data = array('mode'=>$params->mode,
						'zoneId'=>$zoneId,			// string only (since 0.5.0)
						'value'=>$newValue,			// keep in string
						'realValue'=>$realValue,
						'until'=>$params->until);	// (PermanentOverride when null)

		// ************* EVOHOME Dependant ***************************************************************************************************************
		$obj = $equ->iGetInstance();
		if ( $data['until'] == '' || $data['until'] == 'null' ) $data['until'] = null;
		if ( $data['until'] != null ) {
			$data['until'] = self::transformUntil($data['until']);
		}
		$FPT = $obj->iGetFPT();
		$newStatus = $data['value'] == 0 ? $FPT["follow"] : ($data['until'] == null ? $FPT["permanent"] : $FPT["temporary"]);
		$data['status'] = $newStatus;
		// ***********************************************************************************************************************************************

		$newUntil = $data['until'] == null ? 'NA' : $data['until'];
		if ( honeyutils::isDebug() ) honeyutils::logDebug("consigne=$oldConsigne<>$params->t2 ; status=$oldStatus<>$newStatus ; until=$oldUntil<>$newUntil");
		if ( $oldConsigne == $params->t2 && $oldStatus == $newStatus && $oldUntil == $newUntil ) {
			$msgInfo = $prefixByScenario . honeywell::i18n("Set Consigne zone {0} : valeurs reçues identiques aux valeurs courantes (consigne, durée)", $zoneId);
			honeyutils::logError($msgInfo);
			if ( $prefixByScenario == "" ) {
				$equ->setMsgInfo($msgInfo);
				$equ->iRefreshComponent($infosZones);
			}
			honeyutils::logDebug("<<OUT - actionSetConsigne");
			return;
		}
		// -----

		if ( $data['mode'] == SetConsigneData::AUTO ) {	// triggered by scenario
			if ( ReadStates::getStates($locId)[ReadStates::STATE_UNREAD] ) {
				honeyutils::logError($prefixByScenario . honeywell::i18n("Set Consigne est indisponible : API off"));
				honeyutils::logDebug("<<OUT - actionSetConsigne");
				return;
			}
			if ( self::isConsigneUnsettable($equ,$locId) ) {
				honeyutils::logError($prefixByScenario . honeywell::i18n("Set Consigne est indisponible : mode de présence incompatible"));
				honeyutils::logDebug("<<OUT - actionSetConsigne");
				return;
			}
		}

		$execUnitId = rand(0,10000);
		$zname = $equ->getName();
		//honeywell::waitingIAZReentrance("actionSetConsigne-$execUnitId");
		$taskName = $prefixByScenario . honeywell::i18n("Set consigne {0}° sur {1} ({2})", [$params->t2,$zname,$data['until'] == null ? 'permanent' : honeywell::i18n("jusqu'à {0}", $data['until'])]);
		
		// ****** EVOHOME Dependant *****************************************************************
		$infos = $obj->iSetConsigne($execUnitId,$locId,$zoneId,$data,$params->t2,$taskName);
		// ******************************************************************************************

		$updated = false;
		if ( !is_array($infos) ) {
			honeyutils::logError($prefixByScenario . "Error while Set Consigne zone $zoneId : response was empty or malformed");
			$equ->setMsgInfo(honeywell::i18n("Erreur en envoi de la consigne"));
		} else if ( !$infos[honeywell::SUCCESS] ) {
			honeyutils::logError($prefixByScenario . "Error while Set Consigne zone $zoneId", $infos);
			$equ->setMsgInfo(honeywell::i18n("Erreur en envoi de la consigne  : {0} - {1}", [$infos["code"], $infos["message"]]));
		} else {
			$msgInfo = null;
			// merge requested value into zone data :
			foreach ( $infosZones['zones'] as &$infosZone ) {
				if ( $infosZone['zoneId'] == $zoneId ) {
					$infosZone['setPoint'] = $data['realValue'];
					$infosZone['status'] = $newStatus;
					$infosZone['until'] = $newUntil;
					$msgInfo = "1" . honeywell::i18n("La consigne de {0}° a été correctement envoyée vers : {1}", [$params->t2,$zname]);
					honeyutils::setCacheData(honeywell::CACHE_IAZ, $infosZones, honeywell::CACHE_IAZ_DURATION, $locId);
					$updated = true;
					break;
				}
			}
			$states = ReadStates::getStates($locId,$infosZones);
			$scheduleCurrent = Schedule::getSchedule($locId);
			$equ->setToHtmlProperties($states,$scheduleCurrent,$msgInfo);
		}
		$equ->iRefreshComponent($infosZones,$updated);
		honeyutils::logDebug('<<OUT - actionSetConsigne');
	}

	static function doAction($equ,$locId,$paramAction,$parameters) {
		switch ($paramAction) {
			case self::CMD_SET_CONSIGNE_ID:
				self::actionSetConsigne($equ,$locId,$parameters);
				return true;
		}
		return false;
	}

}