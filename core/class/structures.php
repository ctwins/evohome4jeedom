<?php

/**
 * This class appears with version 0.5.0 and contains some data classes (or structures)
 * @author ctwins95
 *
 */
class HeatMode {
	public /*integer*/ $mode;
	public /*string*/ $label;
	public /*string*/ $img;
	public /*boolean*/ $consigneUnsettable;
	public /*string*/ $scheduleType;	// see const SCHEDULE_TYPE_XX
	public /*string*/ $scheduleModeDuration;
	public /*boolean*/ $modeSettable;
	
	const SCHEDULE_TYPE_TIME = "T";
	const SCHEDULE_TYPE_GEOFENCE = "G";
	const SCHEDULE_TYPE_VACANCY = "V";
	
	// Set Mode Duration code (see getModesArray)
	const SMD_PERM_DURING = "PP";
	const SMD_PERM_UNTIL = "PJ";
	const SMD_NONE = null;
	
	// Example : self::MODE_OFF,self::i18n('Arrêt'),'i_off',false,null,self::SMD_NONE
	public function __construct($mode, $label, $img, $consigneUnsettable, $scheduleType, $scheduleModeDuration, $modeSettable) {
		$this->mode = $mode;
		$this->label = honeywell::i18n($label);
		$this->img = $img . ".svg";
		$this->consigneUnsettable = $consigneUnsettable;
		$this->scheduleType = $scheduleType;
		$this->scheduleModeDuration = $scheduleModeDuration;
		$this->modeSettable = $modeSettable;
	}
	
}

/**
 * This class appears with version 0.5.1
 * @author ctwins95
 *
 */
class ConsigneInfos {
	public /*string*/ $status;			// see __iSetHtmlTH ; FollowSchedule / PermanentOverride / TemporaryOverride (depends on hnw system)
	public /*string*/ $until;			// 2018-01-28T23:00:00Z / <empty>
	public /*string*/ $unit;			// Celsius/Fahrenheit
	public /*float*/ $adjustStep;		// step : 0.5 si °C ; 1 si °F	                  )
	public /*int*/ $minHeat;			// 5 (min)  == self->getConfiguration('minHeat')  ) 0.4.1 - these 3 values are now "adjusted by unit"
	public /*int*/ $maxHeat;			// 25 (max) == self->getConfiguration('maxHeat')  )
	public /*string*/ $timeBattLow;		// timeBattLow / <empty>
	public /*string*/ $timeCnxLost;		// timeCnxLost / <empty>
	// Lyric only :
	public /*float*/ $endHeatSetpoint;	// if TemporaryHold or HoldUntil, the endHeatSetpoint value
	public /*boolean*/ $heating;		// false=undefined or heating off ; true=heating on
	// Additional scheduling infos :
	public /*int*/ $minPerDay;			// 1 (evohome/round)
	public /*int*/ $maxPerDay;			// 6 (evohome/round)
	public /*string*/ $timeInterval;	// '10' (from 00:10:00 for evohome/round, '10' from Lyric)

	private function __construct($aConsigneInfos) {
		$this->status = $aConsigneInfos[0];
		$this->until = $aConsigneInfos[1];
		$this->unit = $aConsigneInfos[2];
		$this->adjustStep = floatVal($aConsigneInfos[3]);
		$this->minHeat = intVal($aConsigneInfos[4]);
		$this->maxHeat = intVal($aConsigneInfos[5]);
		$this->timeBattLow = $aConsigneInfos[6];
		$this->timeCnxLost = $aConsigneInfos[7];
		// Lyric parts
		$this->endHeatSetpoint = count($aConsigneInfos) > 8 ? $aConsigneInfos[8] : null;
		$this->heating = (count($aConsigneInfos) > 9 && $aConsigneInfos[9] == '1') ? 1 : 0;
		// Additional scheduling infos (defaults values from Evohome/Round)
		$this->minPerDay = count($aConsigneInfos) > 10 ? intVal($aConsigneInfos[10]) : 1;		// default values
		$this->maxPerDay = count($aConsigneInfos) > 10 ? intVal($aConsigneInfos[11]) : 6;		// from Evohome/ROund ; unknown for Lyric
		$this->timeInterval = count($aConsigneInfos) > 10 ? intVal($aConsigneInfos[12]) : 10;	// default from Evohome/Round (00:10:00), and Lyric (10)
	}
	
	public static function buildObjFromStr($str) {
		$aConsigneInfos = explode(';', $str);
		if ( count($aConsigneInfos) < 8 ) {
			return null;
		}
		return new ConsigneInfos($aConsigneInfos);
	}
	
	public static function buildObj($equ) {
		if ( $equ == null ) {
			return null;
		}
		$cmd = $equ->getCmd(null,TH::CMD_CONSIGNE_TYPE_ID);
		if ( !is_object($cmd) ) {
			return null;
		}
		return self::buildObjFromStr($cmd->execCmd());
	}
	
	public static function buildStr($infosZone) {
		$timeInterval = array_key_exists('scheduleCapabilities',$infosZone) ?
						str_replace(':00','',str_replace('00:','',$infosZone['scheduleCapabilities']['timeInterval'])) :
						'10';
		return $infosZone['status']
		. ";" . $infosZone['until']
		. ";" . $infosZone['units']
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['resolution'],$infosZone['units'],true)
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['minHeat'],$infosZone['units'])
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['maxHeat'],$infosZone['units'])
		. ";" . (array_key_exists('battLow',$infosZone) ? $infosZone['battLow'] : '')
		. ";" . (array_key_exists('cnxLost',$infosZone) ? $infosZone['cnxLost'] : '')
		. ";" . (array_key_exists('endHeatSetpoint',$infosZone) ? $infosZone['endHeatSetpoint'] : '')
		. ";" . (array_key_exists('heating',$infosZone) ? $infosZone['heating'] : '0')
		. ";" . (array_key_exists('scheduleCapabilities',$infosZone) ? $infosZone['scheduleCapabilities']['minPerDay'] : '1')
		. ";" . (array_key_exists('scheduleCapabilities',$infosZone) ? $infosZone['scheduleCapabilities']['maxPerDay'] : '6')
		. ";" . $timeInterval;
	}
	
}

class State {
	public /*string*/ $currentMode;
	public /*boolean*/ $permanent;		// true=Permanent_On ; false=Permanent_Off
	public /*string*/ $until;			// DT, can be "2018-01-28T23:00:00Z" or XX
	// Lyric only :
	public /*int*/ $presence;			// self::PRESENCE_xxx
	
	const MODE_PERMANENT_ON = '1';
	const MODE_PERMANENT_OFF = '0';
	const PRESENCE_UNDEFINED = '0';
	const PRESENCE_OUTSIDE = '1';
	const PRESENCE_INSIDE = '2';
	
	private function __construct($aState) {
		$this->currentMode = $aState[0];
		$this->permanent = $aState[1];
		$this->until = $aState[2];
		$this->presence = count($aState) > 3 ? $aState[3] : self::PRESENCE_UNDEFINED;
	}
	
	public static function buildStr($infosZone) {
		return $infosZone['currentMode']
		. ";" . ($infosZone['permanentMode'] ? self::MODE_PERMANENT_ON : self::MODE_PERMANENT_OFF)
		. ";" . $infosZone['untilMode']
		. ";" . (array_key_exists('inside',$infosZone) ? ($infosZone['inside'] ? self::PRESENCE_INSIDE : self::PRESENCE_OUTSIDE) : self::PRESENCE_UNDEFINED);
	}
	
	public static function buildObjFromStr($state) {
		$aState = explode(';',$state);
		if ( count($aState) < 3 ) {
			return null;
		}
		return new State($aState);
	}
	
	public static function buildObj($equ) {
		if ( $equ == null ) {
			return null;
		}
		$cmd = $equ->getCmd(null,Console::CMD_STATE);
		if ( !is_object($cmd) ) {
			return null;
		}
		return self::buildObjFromStr($cmd->execCmd());
	}
	
}

class SetConsigneData {
	public /*string*/ $mode;	// auto (by scenario) / manuel (by JS)
	public /*string*/ $zoneId;
	public /*float*/ $t1;		// temp requested (new value) ; 0 means return to FollowSchedule (back to scheduled setpoint)
	public /*float*/ $t2;		// a) = $params[2] if setting else than scheduled value
	// b) by JS : scheduled value if t1 = 0
	// c) by Scenario : 0
	public /*string*/ $until;	// null or "null" (for permanent) or time as HH:MM:SS
	
	const AUTO = "auto";
	// 0.4.3bis - separators become '§' as int values lower than 15 were converted with the '#' (#13 => 19) when transmitted between JS>PHP
	//		   WARNING ! Launch a Sync to re-generate these values inside each component/"Set Consigne"
	const SEP = "§";
	
	function __construct($mode, $zoneId, $t1, $t2, $until) {
		$this->mode = $mode;
		$this->zoneId = $zoneId;
		$this->t1 = $t1;
		$this->t2 = $t2;
		$this->until = $until;
	}
	
	public static function buildStrForSelect($label) {
		return $this->mode
		. self::SEP . $this->zoneId
		. self::SEP . $this->t1
		. self::SEP . $this->t2
		. self::SEP . ($this->until == null ? "null" : $this->until)
		. "|" . $label;
	}
	
	public static function buildFromStr($data) {
		$aData = explode(self::SEP,$data);
		if ( count($aData) != 5 ) {
			return null;
		}
		return new SetConsigneData($aData[0],$aData[1],$aData[2],$aData[3],$aData[4]);
	}
	
}

class ReadStates {
	const STATE_UNREAD = 'unread';
	const STATE_CRON_ACTIVE = 'cronActive';
	const STATE_IS_RUNNING = 'isRunning';
	const STATE_LAST_READ = 'lastRead';
	const STATE_IS_ACCURATE = 'isAccurate';
	const STATE_CNX_LOST = 'cnxLost';

	public static function getStates($locId,$infosZones=null) {
		$states = array();
		$states[self::STATE_UNREAD] = (honeywell::CACHE_IAZ_DURATION - honeyutils::getCacheRemaining(honeywell::CACHE_IAZ,$locId)) > honeywell::getLoadingInterval()*60;
		$states[self::STATE_CRON_ACTIVE] = honeywell::isCronActive();
		$states[self::STATE_IS_RUNNING] = honeywell::isIAZrunning();
		$states[self::STATE_LAST_READ] = !is_array($infosZones) || !array_key_exists(honeywell::IZ_TIMESTAMP,$infosZones) ? 0 : honeyutils::tsToLocalDateTime($infosZones[honeywell::IZ_TIMESTAMP]);
		// apiV1 available == accurate values available
		$states[self::STATE_IS_ACCURATE] = !is_array($infosZones) || !array_key_exists(honeywell::IZ_API_V1,$infosZones) ? false : $infosZones[honeywell::IZ_API_V1];
		$states[self::STATE_CNX_LOST] = !is_array($infosZones) || !array_key_exists(honeywell::IZ_GATEWAY_CNX_LOST,$infosZones) ? '' : honeyutils::gmtToLocalDateHMS($infosZones[honeywell::IZ_GATEWAY_CNX_LOST]);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("getStates : " . json_encode($states));
		return $states;
	}

}