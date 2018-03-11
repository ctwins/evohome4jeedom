<?php
class inner {
	static function i18n($txt, $arg=null) {
		if ( substr($txt,-1) == '}' ) $txt .= '__';
		$txt = __($txt, 'schedule');
		if ( substr($txt,-2) == '__' ) $txt = substr($txt,0,-2);
		if ( $arg == null ) return $txt;
		if ( !is_array($arg) ) return str_replace('{0}', $arg, $txt);
		for ( $i=0 ; $i<count($arg) ; $i++ ) $txt = str_replace("{".$i."}", $arg[$i], $txt);
		return $txt;
	}
}
if (!isConnect()) {
	throw new Exception(inner::i18n('401 - Accès non autorisé'));
}
if (init('id') == '') {
	throw new Exception(inner::i18n("L'id ne peut être vide"));
}
$fileId = init(evohome::ARG_FILE_ID);
if ($fileId == '') {
	throw new Exception(inner::i18n("L'id du fichier programme ne peut être vide"));
}
$evohome = evohome::byId(init('id'));
if (!is_object($evohome)) {
	throw new Exception(inner::i18n("L'équipement Evohome est introuvable sur l'ID {0}", init('id')));
}
if ($evohome->getEqType_name() != 'evohome') {
	throw new Exception(inner::i18n("Cet équipement n'est pas du type attendu : {0}", $evohome->getEqType_name()));
}

$scheduleToShow = evohome::getSchedule($fileId);
$zoneId = init(evohome::ARG_ZONE_ID);
$subTitle = evohome::getScheduleSubTitle($fileId,$scheduleToShow,evohome::CFG_SCH_MODE_VERTICAL,$zoneId);
echo "<script>";
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = \"$subTitle\";";
// so the background is really white (and not transparent) when printing
echo "if ( $('#md_modal')[0].style.cssText.search('background') == -1 ) $('#md_modal')[0].style.cssText += 'background-color:white !important'";
echo "</script>";

if ( array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td style='vertical-align:top;'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}

$days = array();
for ( $i=1; $i<=7 ;$i++ ) {
	$ts = mktime(0, 0, 0, 2, 10+$i, 2018);
	$days[strftime('%u', $ts)-1] = inner::i18n(strftime('%A', $ts));
}
$currentDay = strftime('%u', time())-1;
$currentTime = strftime('%H:%M', time());

$zoneNum = 0;
foreach ( $scheduleToShow['zones'] as $mydata ) {
	echo "<table border=0>";
	if ( $zoneId != 0 && $zoneId != $mydata['zoneId'] ) continue;
	echo "<tr><td colspan=7 style='font-size:16px;font-weight:800;color:black;background-color:#C0C0C0;'>&nbsp;&nbsp;" . $mydata['name'] . '</td></tr>';
	$dsSunday = $mydata['schedule']['DailySchedules'][6];
	$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
	$lastTemp = $spSundayLast['TargetTemperature'];
	foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
		echo "<tr>";
		echo "<td";
		if ( $ds['DayOfWeek'] == $currentDay ) echo " style='color:black;background-color:lightgreen;'";
		echo ">&nbsp;" . $days[$ds['DayOfWeek']] . "</td>";
		echo "<td width=100%>";
		echo "<table border=1 width=100% style=\"font-family:'Open Sans', sans-serif;\">";
		echo "<tr style='font-size:11px;'>";
		$mark = 0;
		$midnightAdded = $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00';
		if ( $midnightAdded ) {
			array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'TargetTemperature'=>$lastTemp));
		}
		for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
			$sp = $ds['Switchpoints'][$i-1];
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
			echo "<td";
			if ( $mark == 1 ) {
				echo " style='color:black;background-color:lightgreen;'";
				$mark++;
			}
			echo ">" . substr($sp['TimeOfDay'],0,5) . "</td>";
		}
		echo "</tr>";
		echo "<tr>";
		for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
			$sp = $ds['Switchpoints'][$i-1];
			$hm = substr($sp['TimeOfDay'],0,5);
			$te = 2400;
			if ( $i < sizeof($ds['Switchpoints']) ) {
				$hmNext = substr($ds['Switchpoints'][$i]['TimeOfDay'],0,5);
				$te = intval(substr($hmNext,0,2) . substr($hmNext,3,2));
			}
			$td = intval(substr($hm,0,2) . substr($hm,3,2));
			$w = ($te - $td) / 2400.0 * 100.0;
			$bgc = evohome::getBackColorForTemp($sp['TargetTemperature']);
			$sTemp = ($midnightAdded ? '...' : '' ) . number_format($sp['TargetTemperature'],1);
			$midnightAdded = false;
			echo "<td align=center style='width:".$w."%;color:white;background-color:".$bgc.";'>" . $sTemp . "</td>";
			$lastTemp = $sp['TargetTemperature'];
		}
		echo "</tr></table></td></tr>";
	}
	echo "</table>";
	if ( $zoneId == 0 && ++$zoneNum < count($scheduleToShow['zones']) ) echo "<br/>";
}
?>
