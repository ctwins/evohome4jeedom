<?php
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
