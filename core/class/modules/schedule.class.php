<?php

/**
 * This class appears with version 0.5.3, and content is extract from honeywell.class.php
 * and its content is dedicated parts about Schedule functionalities.
 * 
 * @author ctwins95
 *
 */
class Schedule {
	const CURRENT_SCHEDULE_ID = 0;

	/*************** Statics about Scheduling ********************/

	static function getScheduleType($scheduleContentOrInfosZones) {
		return is_array($scheduleContentOrInfosZones) && array_key_exists('scheduleType',$scheduleContentOrInfosZones) ? $scheduleContentOrInfosZones["scheduleType"] : HeatMode::SCHEDULE_TYPE_TIME;
	}

	static function getCfgScheduleFileId($locId,$scheduleContentOrInfosZones) {
		$scheduleType = self::getScheduleType($scheduleContentOrInfosZones);
		$fileId = honeyutils::getParam(honeywell::iCFG_SCHEDULE_ID, "none", $locId, $scheduleType);
		if ( $fileId == "none" ) {
			$fileId = honeyutils::getParam(honeywell::iCFG_SCHEDULE_ID, "none", $locId);
			if ( $fileId == "none" ) return 0;
			honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
		}
		return $fileId;
	}

	static function getActiveScheduleName($locId,$infosZones) {
		$fileId = self::getCfgScheduleFileId($locId,$infosZones);
		$fileInfos = self::getFileInfosById($locId,$fileId);
		return $fileInfos != null ? $fileInfos['name'] : "NA";
	}

	static function getSchedule($locId,$fileId=self::CURRENT_SCHEDULE_ID,$dateTime=0,$doRefresh=false) {
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - getSchedule($locId,$fileId)");
		if ( $fileId == self::CURRENT_SCHEDULE_ID ) {
			$schedule = null;//honeyutils::getCacheData(honeywell::CACHE_CURRENT_SCHEDULE,$locId);
			//if ( !is_array($schedule) || $doRefresh ) {
				$infosZones = honeywell::getInformationsForLoc($locId,$doRefresh);
				if ( !is_array($infosZones) ) {
					if ( honeyutils::isDebug() ) honeyutils::logDebug('<<OUT - getSchedule(current) - error while getInformationsAllZonesE2 (see above)');
					//// avoid request again when we know requesting does not work
					//honeyutils::setCacheData(honeywell::CACHE_CURRENT_SCHEDULE, array('dummy','1'), honeywell::CACHE_STATES_DURATION, $locId);
					return null;
				}
				$schedule = array('datetime'=>$dateTime);
				$schedule['scheduleType'] = array_key_exists('scheduleType',$infosZones) ? $infosZones['scheduleType'] : HeatMode::SCHEDULE_TYPE_TIME;
				$schedule['units'] = array_key_exists('units',$infosZones['zones'][0]) ? $infosZones['zones'][0]['units'] : honeywell::CFG_UNIT_CELSIUS;
				$schedule['zones'] = array();
				foreach ( $infosZones['zones'] as $zone ) {
					$schedule['zones'][] = array(
						'zoneId' => $zone['zoneId'],
						'name' => $zone['name'],
						'schedule' => $zone['schedule']);
				}
				//honeyutils::setCacheData(honeywell::CACHE_CURRENT_SCHEDULE, $schedule, honeywell::CACHE_STATES_DURATION, $locId);
			//} else {
			//	honeyutils::logDebug('got getSchedule(0) from cache');
			//}
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
			if ( Console::getPresence($locId) == State::PRESENCE_OUTSIDE ) {
				$temp = $schedule['GeofenceSchedule']['awayPeriod']['heatSetPoint'];
				$ret = array('TH'=>$temp, 'UNTIL'=>'');
			} else {	// assume INSIDE
			    $currentTime = honeyutils::getDateTime()->format('H:i:s');
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
			$ret['TH'] = honeywell::adjustByUnit2($ret['TH'], honeywell::CFG_UNIT_FAHRENHEIT, $units);	
			return $ret;
		}
		return null;
	}

	public static function getScheduleSubTitle($id,$locId,$fileId,$scheduleType,$scheduleCurrent,$scheduleToShow,$targetOrientation,$zoneId,$scheduleSource,$isEdit=false) {
		$infoDiff = '';
		if ( $fileId == 0) {
			$subTitle = honeywell::i18n("Programmation courante");
		} else {
		    $dt = honeyutils::getDateTime();
			$dt->setTimestamp($scheduleToShow['datetime']);
			$subTitle = honeywell::i18n("Programmation de '{0}' créée le {1} à {2}", [self::getFileInfosById($locId,$fileId)['name'], $dt->format('Y-m-d'), $dt->format('H:i:s')]);
			if ( !$isEdit ) {
				$isDiff = false;
				if ( $zoneId == 0 ) {
					$isDiff = json_encode($scheduleToShow['zones']) != json_encode($scheduleCurrent['zones']);
				} else {
					$isDiff = json_encode(honeyutils::extractZone($scheduleToShow,$zoneId)) != json_encode(honeyutils::extractZone($scheduleCurrent,$zoneId));
				}
				if ( $isDiff ) {
					$infoDiff = honeywell::i18n("différente de la programmation courante") . " *";
				} else {
					$infoDiff = honeywell::i18n("identique à la programmation courante");
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
				$lbl = honeywell::i18n($targetOrientation == honeywell::CFG_SCH_MODE_VERTICAL ? "Vertical" : "Horizontal");
				$subTitle = "<a class='btn btn-success btn-sm tooltips' onclick=\\\"$ssf\\\">$lbl</a>&nbsp;$subTitle";
			} else {
				$infoDiff = honeywell::i18n("Mode édition");
			}
		}
		$subTitle .= $infoDiff == '' ? '' : "<br/><i>$infoDiff</i>";
		return $subTitle;
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

	static function getSchedulePath() {
		$record_dir = dirname(__FILE__) . '/../../../data/';
		if (!file_exists($record_dir)) {
			mkdir($record_dir, 0777, true);
		}
		return $record_dir;
	}

	static public function getScheduleNames($locId) {
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
							'name' => honeywell::i18n('vide'),
							'type' => null,
							'fullPath' => '');
		} else {
			usort($list, "honeywell::cmpName");
		}
		return $list;
	}

	static function actionSaveSchedule($equ,$locId,$parameters) {
		$fileName = $parameters[honeywell::ARG_FILE_NAME];
		$fileId = $parameters[honeywell::ARG_FILE_ID];
		$commentary = $parameters[honeywell::ARG_FILE_REM];
		$newSchedule = array_key_exists(honeywell::ARG_FILE_NEW_SCHEDULE,$parameters) ? $parameters[honeywell::ARG_FILE_NEW_SCHEDULE] : null;
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - actionSaveSchedule($fileName, $fileId, " . ($newSchedule == null ? '<currentSchedule>' : '<newSchedule>') . ')');
		//honeywell::waitingIAZReentrance('SaveSChedule-' . rand(0,10000));
		//honeyutils::lockCron();
		$dateTime = time();
		if ( $newSchedule == null ) {
			// force refresh inside __getInformations
			$rbs = honeyutils::getParam(honeywell::CFG_REFRESH_BEFORE_SAVE,0);
			$schedule = self::getSchedule($locId,self::CURRENT_SCHEDULE_ID,$dateTime,$rbs==1);
		} else {
			// a received schedule (by content) is necessary of type SCHEDULE_TYPE_TIME at this time : 2020-03-03 (else, think to add parameter or include into newSchedule)
			$schedule = array('datetime'=>$dateTime, 'scheduleType'=>HeatMode::SCHEDULE_TYPE_TIME, 'zones'=>json_decode($newSchedule,true));
		}
		if ( $schedule == null ) {
			honeyutils::logDebug('<<OUT - actionSaveSchedule - error while getSchedule (see above)');
			// this call used to remove the loading mask on the screen
			Console::refreshConsole($locId);
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
				honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $fileId, $locId, $schedule['scheduleType']);
				//self::updateScheduleFileId();
			}/* else {*/
				honeywell::refreshAllForLoc($locId,$equ->getInformations());
			/*}*/
			$console = Console::getConsole($locId);
			Console::consoleUpdateActionsList($console);
			honeyutils::logDebug('<<OUT - actionSaveSchedule');
		}
		//honeyutils::unlockCron();
	}

	static function actionRestoreSchedule($equ,$locId,$parameters,$isLoaded=false) {
		$fileId = $parameters[honeywell::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($locId,$fileId);
		if ( $fileInfos == null ) {
			honeyutils::logError("actionRestoreSchedule on unknown ID=$fileId");
			return;
		}
		$scheduleSaved = self::getSchedule($locId,$fileId);
		$scheduleType = self::getScheduleType($scheduleSaved);
		// Optimisation - retain only saved schedule/zone # CurrentSchedule/zone
		$scheduleCurrent = self::getSchedule($locId);
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
			honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
			Console::refreshConsole($locId,"1".honeywell::i18n("Aucun changement envoyé (tous les programmes identiques)"));
			return true;
		}

		$execUnitId = rand(0,10000);
		honeywell::waitingIAZReentrance("RestoreSchedule-$execUnitId");
		honeyutils::lockCron();

		if ( honeyutils::isDebug() ) honeyutils::logDebug("actionRestoreSchedule on saving ID=$fileId, name=" . $fileInfos['name'] . ", nbSchedules=$nbSchedules");
		$prevFileId = self::getCfgScheduleFileId($locId, $scheduleSaved);
		honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $fileId, $locId, $scheduleType);
		$taskName = honeywell::i18n("Restauration depuis '{0}' ({1} zone(s))", [$fileInfos['name'], $nbSchedules]);

		// **********************************************************************
		// Call Python or API function
		$obj = $isLoaded ? $equ : $equ->iGetInstance();
		$aRet = $obj->iRestoreSchedule($execUnitId,$locId,$scheduleToSend,$taskName);
		// **********************************************************************

		$retValue = true;
		if ( !is_array($aRet) ) {
			honeyutils::logError("Error while actionRestoreSchedule : response was empty or malformed", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $prevFileId, $locId, $scheduleType);
			Console::refreshConsole($locId,honeywell::i18n("Erreur pendant l'envoi de la programmation") . ($aRet !== '' ? " : " . $aRet : ""));
			$retValue = false;
		}
		else if ( !$aRet[honeywell::SUCCESS] ) {
			honeyutils::logError("Error while actionRestoreSchedule", $aRet);
			// restore the previous file selected and remove the loading mask on the screen
			honeyutils::setParam(honeywell::iCFG_SCHEDULE_ID, $prevFileId, $locId, $scheduleType);
			Console::refreshConsole($locId,honeywell::i18n("Erreur pendant l'envoi de la programmation : {0} : {1}", [$aRet["code"], $aRet["message"]]));
			$retValue = false;
		} else {
			$delay = $aRet["system"] == honeywell::SYSTEM_EVOHOME ? 30 : 5;
			$nb = 1;
			$startTime = time();
			$error = false;
			sleep(2);
			while ( !$error ) {
				set_time_limit(60);	// reset the time_limit (?)
				$msgInfo = "1".honeywell::i18n("Rafraichissement des données, essai {0}...", $nb);
				honeyutils::doCacheRemove(honeywell::CACHE_SCHEDULE_DELTA,$locId);
				$obj->iGetInformations($locId,true,true,$msgInfo,true);
				// waiting for refresh event triggers the toHtmlConsole..				
				while ( true ) {
					$sd = honeyutils::getCacheData(honeywell::CACHE_SCHEDULE_DELTA,$locId);
					if ( $sd !== '' ) break;
					usleep(250000);
				}
				if ( $sd == honeywell::SCHEDULE_DELTA_ON ) {
					if ( time() - $startTime > 120 ) {
						Console::refreshConsole($locId,honeywell::i18n("Après 2mn, le programme dans le système ne correspond pas au programme envoyé."));
						$error = true;
					} else {
						$nb += 1;
						Console::refreshConsole($locId,"1".honeywell::i18n("Rafraichissement des données : attente {0} sec avant essai {1}", [$delay, $nb]), true);
						sleep($delay);
					}
				} else {
					break;
				}
			}
			if ( !$error ) {
				honeyutils::saveInfo($equ,Console::CMD_CURRENT_SCHEDULE, $fileInfos['name']);
				Console::refreshConsole($locId,"1".honeywell::i18n("L'envoi de la programmation s'est correctement effectué"));
			}
		}
		honeyutils::unlockCron();
		return $retValue;
	}

	static function actionDeleteSchedule($equ,$locId,$parameters) {
		$fileId = $parameters[honeywell::ARG_FILE_ID];
		$fileInfos = self::getFileInfosById($locId,$fileId);
		$msgInfo = '';
		if ( $fileInfos == null ) {
			honeyutils::logError('actionDeleteSchedule on unknown ID=' . $fileId);
			$msgInfo = honeywell::i18n("Fichier introuvable");
		} else {
			$console = Console::getConsole($locId);
			$cmdRestoreId = $console->getCmd(null, Console::CMD_RESTORE)->getId();
			/* scenarioExpression => (id=206 /) scenarioSubElement_id=66
			   scenarioSubElement => id=66 / scenarioElement_id=44
			   scenario => scenarioElement ["44"] => name = "xxxxx" */
			// Lyric : add case for SetMode / fileId
			$cmdSetModeId = $console->getCmd(null, Console::CMD_SET_MODE)->getId();
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
					$list .= ($list != '' ? ', "' : '"') . $record['name'] . '" (' . ($record['cmdId'] == $cmdRestoreId ? honeywell::i18n("Restauration") : honeywell::i18n("SetMode+Restauration")) . ')';
				}
				if ( honeyutils::isDebug() ) honeyutils::logDebug("Schedule $fileId is used by : $list !");
				if ( count($results) == 1 ) {
					$msgInfo = honeywell::i18n("Suppression impossible du fichier '{0}' car il est utilisé dans le Scenario : {1}", [$fileInfos['name'],$list]);
				} else {
					$msgInfo = honeywell::i18n("Suppression impossible du fichier '{0}' car il est utilisé dans les Scenarios : {1}", [$fileInfos['name'],$list]);
				}
			} else {
				if ( honeyutils::isDebug() ) honeyutils::logDebug("actionDeleteSchedule on ID=$fileId");
				unlink($fileInfos['fullPath']);
				Console::consoleUpdateActionsList($console);
				$msgInfo = "1" . honeywell::i18n("Fichier '{0}' supprimé", $fileInfos['name']);
			}
		}
		Console::refreshConsole($locId,$msgInfo);
	}

	static function doAction($equ,$locId,$paramAction,$parameters) {
		switch ($paramAction) {
			// -- Common functions
			case Console::CMD_SAVE:
				self::actionSaveSchedule($equ,$locId,$parameters);
				return true;

			// -- Object dependent functions
			case Console::CMD_RESTORE:
				self::actionRestoreSchedule($equ,$locId,$parameters);
				return true;

			case Console::CMD_DELETE:
				self::actionDeleteSchedule($equ,$locId,$parameters);
				return true;

		}
		return false;
	}

}
