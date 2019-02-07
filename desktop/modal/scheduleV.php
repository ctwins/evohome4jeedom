<?php
class inner {
	static function i18n($txt, $args=null) {
		if ( substr($txt,-1) == '}' ) $txt .= '__';
		$txt = __($txt, "plugins/evohome/desktop/modal/schedule");
		if ( substr($txt,-2) == '__' ) $txt = substr($txt,0,-2);
		if ( $args == null ) return $txt;
		if ( !is_array($args) ) return str_replace('{0}', $args, $txt);
		for ( $i=0 ; $i<count($args) ; $i++ ) $txt = str_replace("{".$i."}", $args[$i], $txt);
		return $txt;
	}
}
if (!isConnect()) {
	throw new Exception(inner::i18n('401 - Accès non autorisé'));
}
$id = init('id');
if ($id == '') {
	throw new Exception(inner::i18n("L'id ne peut être vide"));
}
$fileId = init(evohome::ARG_FILE_ID);
if ($fileId == '') {
	throw new Exception(inner::i18n("L'id du fichier programme ne peut être vide"));
}
$evohome = evohome::byId($id);
if (!is_object($evohome)) {
	throw new Exception(inner::i18n("L'équipement Evohome est introuvable sur l'ID {0}", $id));
}
if ($evohome->getEqType_name() != 'evohome') {
	throw new Exception(inner::i18n("Cet équipement n'est pas du type attendu : {0}", $evohome->getEqType_name()));
}

$scheduleToShow = evohome::getSchedule($fileId);
if ( !is_array($scheduleToShow) ) {
	echo "Erreur de lecture<br/><br/>";
	return;
}
$currentSchedule = evohome::getSchedule(evohome::CURRENT_SCHEDULE_ID);
$zoneId = init(evohome::ARG_ZONE_ID);
$typeSchedule = init("typeSchedule");
$subTitle = evohome::getScheduleSubTitle($id,$fileId,$currentSchedule,$scheduleToShow,evohome::CFG_SCH_MODE_HORIZONTAL,$zoneId,$typeSchedule);
echo "<script>";
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = \"$subTitle\";";
// so the background is really white (and not transparent) when printing
//echo "if ( $('#md_modal')[0].style.cssText.search('background') == -1 ) $('#md_modal')[0].style.cssText += 'background-color:white !important'";
echo "</script>";

if ( array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td style='vertical-align:top;'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}

const cDays = ["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"];
$days = array();
for ( $i=1; $i<=7 ;$i++ ) {
	$ts = mktime(0, 0, 0, 2, 10+$i, 2018);
	$days[strftime('%u', $ts)-1] = inner::i18n(cDays[date('N', $ts)-1]);
}
$currentDay = strftime('%u', time())-1;
$currentTime = strftime('%H:%M', time());

$zoneNum = 0;
$equNamesById = evohome::getEquNamesAndId();
foreach ( $scheduleToShow['zones'] as $mydata ) {
	if ( $zoneId != 0 && $zoneId != $mydata['zoneId'] ) continue;
	if ( $equNamesById[$mydata['zoneId']] == null ) continue;
	echo "<table border=0 width=100%>";
	echo "<tr><td colspan=7 style='font-size:14px;font-weight:800;color:black;background-color:#C0C0C0;'>&nbsp;&nbsp;";
	echo $equNamesById[$mydata['zoneId']];
	if ( $fileId != evohome::CURRENT_SCHEDULE_ID && json_encode($mydata) != json_encode(evohome::extractZone($currentSchedule,$mydata['zoneId'])) ) {
		echo "&nbsp;*";
	}
	echo '</td></tr><tr>';
	$dsSunday = $mydata['schedule']['DailySchedules'][6];
	$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
	$lastTemp = $spSundayLast['heatSetpoint'];
	foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
		echo "<td valign='top'>";
		echo "<table border=1 style=\"font-family:'Open Sans', sans-serif;\">";
		echo "<tr><td align=center colspan=2 class=ui-widget-header";
		if ( $ds['DayOfWeek'] == $currentDay ) echo " style='color:black!important;background-color:lightgreen!important;'";
		echo ">" . $days[$ds['DayOfWeek']] . "</td></tr>";
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
			$bgc = evohome::getBackColorForTemp($sp['heatSetpoint']);
			$sTemp = ($midnightAdded ? '...' : '' ) . number_format($sp['heatSetpoint'],1);
			$midnightAdded = false;
			echo "<td align=center width=7.15% style='color:white;background-color:$bgc;'>$sTemp</td></tr>";
			$lastTemp = $sp['heatSetpoint'];
		}
		echo "</table></td>";
	}
	echo "</tr></table>";
	if ( $zoneId == 0 && ++$zoneNum < count($scheduleToShow['zones']) ) echo "<br/>";
}
?>
