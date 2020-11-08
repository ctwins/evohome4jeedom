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
	throw new Exception(inner::i18n("L'équipement Evohome est introuvable sur l'ID {0}", $id));
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
$scheduleSource = init("scheduleSource");
$subTitle = honeywell::getScheduleSubTitle($id,$locId,$fileId,'T',$currentSchedule,$scheduleToShow,honeywell::CFG_SCH_MODE_HORIZONTAL,$zoneId,$scheduleSource);
echo "<script>$('.ui-widget-overlay.ui-front').hide();";
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = \"$subTitle\";";
// so the background is really white (and not transparent) when printing
//echo "if ( $('#md_modal')[0].style.cssText.search('background') == -1 ) $('#md_modal')[0].style.cssText += 'background-color:white !important'";
echo "$('#md_modal').dialog('option', 'width', 700);\n";
if ( $zoneId != '0' ) {
	echo "$('#md_modal').dialog('option', 'height', 'auto');\n";
} else {
	echo "$('#md_modal').dialog('option', 'height', jQuery(window).height() - 60);\n";
}
echo "$('#md_modal').dialog('option', 'position', {my:'center top', at:'center top+40', of:window});\n";
echo "</script>";

if ( array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td style='vertical-align:top;'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}

$currentDay = strftime('%u', time())-1;
$currentTime = strftime('%H:%M', time());

$zoneNum = 0;
$equNamesById = honeywell::getEquNamesAndId($locId);
foreach ( $scheduleToShow['zones'] as $mydata ) {
	if ( $zoneId != '0' && $zoneId != $mydata['zoneId'] ) continue;
	if ( $equNamesById[$mydata['zoneId']] == null ) continue;
	echo "<table border=0 width=100%>";
	echo "<tr><td colspan=7 style='font-size:14px;font-weight:800;color:black;background-color:#C0C0C0;'>&nbsp;&nbsp;";
	echo $equNamesById[$mydata['zoneId']];
	if ( $fileId != honeywell::CURRENT_SCHEDULE_ID && json_encode($mydata) != json_encode(honeyutils::extractZone($currentSchedule,$mydata['zoneId'])) ) {
		echo "&nbsp;*";
	}
	/*if ( count($mydata['schedule']['DailySchedules']) == 0 ) {
		echo "  <span style='font-weight:400;'>- aucune programmation définie</span>";
	}*/
	echo '</td></tr><tr>';
	if ( count($mydata['schedule']['DailySchedules']) == 0 ) {
		for ($d=0 ; $d<=6 ; $d++) {
			echo "<td valign='top'>";
			echo "<table border=1 style=\"font-family:'Open Sans', sans-serif;\">";
			echo "<tr><td align=center colspan=2 class=ui-widget-header";
			if ( $d == $currentDay ) echo " style='color:black!important;background-color:lightgreen!important;'";
			echo ">" . honeyutils::getDayName($d) . "</td></tr>";
			echo "<tr><td align=center width=7.15%";
			if ( $d == $currentDay ) echo " style='color:black;background-color:lightgreen;'";
			echo ">00:00</td>";
			$bgc = honeywell::getBackColorForTemp(10);
			echo "<td align=center width=7.15% style='color:white;background-color:$bgc;'>...</td></tr>";
			echo "</table></td>";
		}
	} else {
		$dsSunday = $mydata['schedule']['DailySchedules'][6];
		$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
		$lastTemp = $spSundayLast['heatSetpoint'];
		foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
			echo "<td valign='top'>";
			echo "<table border=1 style=\"font-family:'Open Sans', sans-serif;\">";
			echo "<tr><td align=center colspan=2 class=ui-widget-header";
			if ( $ds['DayOfWeek'] == $currentDay ) echo " style='color:black!important;background-color:lightgreen!important;'";
			echo ">" . honeyutils::getDayName($ds['DayOfWeek']) . "</td></tr>";
			$mark = 0;
			$midnightAdded = $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00';
			if ( $midnightAdded ) {
				array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'heatSetpoint'=>$lastTemp));
			}
			for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
				$sp = $ds['Switchpoints'][$i-1];
				$hm = substr($sp['TimeOfDay'],0,5);
				if ( $ds['DayOfWeek'] == $currentDay ) {
					if ( $i == sizeof($ds['Switchpoints']) ) {
						$mark++;
					} else {
						$spNext = $ds['Switchpoints'][$i];
						$hmNext = substr($spNext['TimeOfDay'],0,5);
						if ( $hmNext > $currentTime ) {
							$mark++;
						}
					}
				}
				echo "<tr><td align=center width=7.15%";
				if ( $mark == 1 ) echo " style='color:black;background-color:lightgreen;'";
				echo ">$hm</td>";
				$bgc = honeywell::getBackColorForTemp($sp['heatSetpoint']);
				$sTemp = ($midnightAdded ? '...' : '' ) . number_format($sp['heatSetpoint'],1);
				$midnightAdded = false;
				echo "<td align=center width=7.15% style='color:white;background-color:$bgc;'>$sTemp</td></tr>";
				$lastTemp = $sp['heatSetpoint'];
			}
			echo "</table></td>";
		}
	}
	echo "</tr></table>";
	if ( $zoneId == '0' && ++$zoneNum < count($scheduleToShow['zones']) ) echo "<br/>";
}
?>
