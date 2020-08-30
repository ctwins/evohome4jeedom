<?php
require_once dirname(__FILE__) . '/../../core/class/honeywell.class.php';

class inner {
	static function i18n($txt, $args=null) {
		return honeyutils::i18n($txt, "plugins/".honeywell::PLUGIN_NAME."/desktop/modal/schedule", $args);
	}
}
if (!isConnect()) {
	throw new Exception(inner::i18n('401 - Accès non autorisé'));
}
$id = init('id');
if ($id == '') {
	throw new Exception(inner::i18n("L'id ne peut être vide"));
}
$fileId = init(honeywell::ARG_FILE_ID);
if ($fileId == '') {
	throw new Exception(inner::i18n("L'id du fichier programme ne peut être vide"));
}
$eqLogic = honeywell::byId($id);
if (!is_object($eqLogic)) {
	throw new Exception(inner::i18n("L'équipement Honeywell est introuvable sur l'ID {0}", $id));
}
$locId = $eqLogic->getLocationId();
if ($eqLogic->getEqType_name() != honeywell::PLUGIN_NAME) {
	throw new Exception(inner::i18n("Cet équipement n'est pas du type attendu : {0}", $eqLogic->getEqType_name()));
}
$scheduleToShow = honeywell::getSchedule($locId,$fileId);
if ( !is_array($scheduleToShow) ) {
	echo "Erreur de lecture<br/><br/>";
	return;
}
$currentSchedule = $fileId == honeywell::CURRENT_SCHEDULE_ID ? $scheduleToShow : honeywell::getSchedule($locId,honeywell::CURRENT_SCHEDULE_ID);
$zoneId = init(honeywell::ARG_ZONE_ID);
$scheduleType = init("scheduleType");
$scheduleSource = init("scheduleSource");
$subTitle = honeywell::getScheduleSubTitle($id,$locId,$fileId,$scheduleType,$currentSchedule,$scheduleToShow,honeywell::CFG_SCH_MODE_HORIZONTAL,$zoneId,$scheduleSource);
echo "<script>$('.ui-widget-overlay.ui-front').hide();";
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = \"$subTitle\";";
// so the background is really white (and not transparent) when printing
echo "$('#md_modal').dialog('option', 'width', 700);\n";
//if ( $zoneId != '0' ) {
	echo "$('#md_modal').dialog('option', 'height', 'auto');\n";
//} else {
//	echo "$('#md_modal').dialog('option', 'height', jQuery(window).height() - 60);\n";
//}
echo "$('#md_modal').dialog('option', 'position', {my:'center top', at:'center top+40', of:window});\n";
echo "</script>";

?>

<style>
	._t0 { text-align:center;height:32px;font-size:12px;font-style:italic;color:gray;background-color:#F0F0F0; }
	._t1 { padding-left:8px;height:40px;width:280px;font-size:16px;font-weight:600;color:black;background-color:#C0C0C0; }
	._t1b { background-color:#C0C0C0;width:60px;text-align:center; }
	._t2 { padding-left:16px;background-color:#F0F0F0;color:gray;height:32px; }
	._t2b { padding-left:32px;background-color:#F0F0F0;color:gray;padding-bottom:4px; }
	._t2c { padding-left:16px;background-color:#F0F0F0;color:gray;height:32px;width:200px; }
	._t3 { background-color:#F0F0F0;color:black;text-align:center; }
	._t3b { background-color:#F0F0F0;color:black;width:180px; }
</style>

<?php
if ( array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td style='vertical-align:top;'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}

if ( $scheduleType == 'G' ) {
	$data = $scheduleToShow["zones"][0]["schedule"]["GeofenceSchedule"];
	
	echo "<table align=center>";
	echo "<tr><td class=_t0 colspan=2>" . inner::i18n('Programmation GeoFence') . "</td></tr>";

	echo "<tr><td class=_t1>" . inner::i18n('QUAND JE SUIS A LA MAISON') . "</td>";
	echo "<td class=_t1b><img src='plugins/".honeywell::PLUGIN_NAME."/img/inside.png'/></td></tr>";

	// Home
	echo "<tr style='height:32px;'>";
	echo "<td class=_t2>" . inner::i18n('Utiliser les réglages du mode Maison') . "</td>";
	echo "<td class=_t3>" . honeywell::adjustByUnit($data["homePeriod"]["heatSetPoint"],honeywell::CFG_UNIT_FAHRENHEIT) . "°</td>";
	echo "</tr>";

	// Sleep
	echo "<tr>";
	echo "<td class=_t2>" . inner::i18n('Utiliser les réglages du mode Nuit') . "</td>";
	echo "<td class=_t3 rowspan=2>" . honeywell::adjustByUnit($data["sleepMode"]["heatSetPoint"],honeywell::CFG_UNIT_FAHRENHEIT) . "°</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class=_t2b>de " . $data["sleepMode"]["startTime"] . " à " . $data["sleepMode"]["endTime"] . "</td>";
	echo "</tr>";

	// Away
	echo "<tr><td class=_t1>" . inner::i18n('QUAND JE SUIS ABSENT') . "</td>";
	echo "<td class=_t1b><img src='plugins/".honeywell::PLUGIN_NAME."/img/outside.png'/></td></tr>";
	echo "<tr>";
	echo "<td class=_t2>" . inner::i18n('Utiliser les réglages du mode Absent') . "</td>";
	echo "<td class=_t3>" . honeywell::adjustByUnit($data["awayPeriod"]["heatSetPoint"],honeywell::CFG_UNIT_FAHRENHEIT) . "°</td>";
	echo "</tr>";

	echo "</table>";

} else if ( $scheduleType == 'V' ) {
	$data = $scheduleToShow["zones"][0]["schedule"]["VacationSchedule"];
	
	echo "<table align=center>";
	echo "<tr><td class=_t0 colspan=2>" . inner::i18n('Programmation Vacances') . "</td></tr>";
	echo "<tr>";
	echo "<td class=_t2c>" . inner::i18n('Date départ') . "</td>";
	echo "<td class=_t3b>" . honeyutils::gmtToLocalDateHMS($data["start"]) . "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=_t2c>" . inner::i18n('Date retour') . "</td>";
	echo "<td class=_t3b>" . honeyutils::gmtToLocalDateHMS($data["end"]) . "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=_t2c>" . inner::i18n('Consigne fixée') . "</td>";
	echo "<td class=_t3b>" . honeywell::adjustByUnit($data["heatSetpoint"],honeywell::CFG_UNIT_FAHRENHEIT) . "°</td>";
	echo "</tr>";

} else {
	echo json_encode($scheduleToShow);

}

?>
