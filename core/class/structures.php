<?php
/**
 * This class appears with version 0.5.0
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
	
	// Set Mode Duration code (see iGetModesArray)
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
	public /*float*/ $delta;			// delta previous measure (0/-1:+1)
	public /*string*/ $timeBattLow;		// timeBattLow / <empty>
	public /*string*/ $timeCnxLost;		// timeCnxLost / <empty>
	// Lyric only :
	public /*float*/ $endHeatSetpoint;	// if TemporaryHold or HoldUntil, the endHeatSetpoint value
	public /*boolean*/ $heating;		// false=undefined or heating off ; true=heating on
	
	private function __construct($aConsigneInfos) {
		$this->status = $aConsigneInfos[0];
		$this->until = $aConsigneInfos[1];
		$this->unit = $aConsigneInfos[2];
		$this->adjustStep = floatVal($aConsigneInfos[3]);
		$this->minHeat = intVal($aConsigneInfos[4]);
		$this->maxHeat = intVal($aConsigneInfos[5]);
		$this->delta = honeywell::applyRounding($aConsigneInfos[6]);
		$this->timeBattLow = $aConsigneInfos[7];
		$this->timeCnxLost = $aConsigneInfos[8];
		// Lyric parts
		$this->endHeatSetpoint = count($aConsigneInfos) > 9 ? $aConsigneInfos[9] : null;
		$this->heating = count($aConsigneInfos) > 10 ? $aConsigneInfos[10] == '1' : false;
	}
	
	public static function buildObjFromStr($str) {
		$aConsigneInfos = explode(';', $str);
		if ( count($aConsigneInfos) < 9 ) {
			return null;
		}
		return new ConsigneInfos($aConsigneInfos);
	}
	
	public static function buildObj(eqLogic $equ) {
		if ( $equ == null ) {
			return null;
		}
		$cmdConsigneInfos = $equ->getCmd(null,honeywell::CMD_CONSIGNE_TYPE_ID);
		if ( !is_object($cmdConsigneInfos) ) {
			return null;
		}
		return self::buildObjFromStr($cmdConsigneInfos->execCmd());
	}
	
	public static function buildStr($infosZone,$prev) {
		return $infosZone['status']
		. ";" . $infosZone['until']
		. ";" . $infosZone['units']
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['resolution'],$infosZone['units'],true)
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['minHeat'],$infosZone['units'])
		. ";" . honeywell::adjustByUnit($infosZone['setPointCapabilities']['maxHeat'],$infosZone['units'])
		. ";" . $prev
		. ";" . (array_key_exists('battLow',$infosZone) ? $infosZone['battLow'] : '')
		. ";" . (array_key_exists('cnxLost',$infosZone) ? $infosZone['cnxLost'] : '')
		. ";" . (array_key_exists('endHeatSetpoint',$infosZone) ? $infosZone['endHeatSetpoint'] : '')
		. ";" . (array_key_exists('heating',$infosZone) ? $infosZone['heating'] : '0');
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
	
	public static function buildObj(eqLogic $equ) {
		if ( $equ == null ) {
			return null;
		}
		$cmdEtat = $equ->getCmd(null,honeywell::CMD_STATE);
		if ( !is_object($cmdEtat) ) {
			return null;
		}
		return self::buildObjFromStr($cmdEtat->execCmd());
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
	
	public function buildStrForSelect($label) {
		return $this->mode
		. self::SEP . $this->zoneId
		. self::SEP . $this->t1
		. self::SEP . $this->t2
		. self::SEP . ($this->until == null ? "null" : $this->until)
		. "|" . $label;
	}
	
	public function buildFromStr($data) {
		$aData = explode(self::SEP,$data);
		if ( count($aData) != 5 ) {
			return null;
		}
		return new SetConsigneData($aData[0],$aData[1],$aData[2],$aData[3],$aData[4]);
	}
	
}
