<?php
class inner {
	static function i18n($txt, $arg=null) {
		if ( $arg == null ) $txt = __($txt,__FILE__);
		else {
			$txt = __($txt, __FILE__, $arg);
 			if ( !is_array($arg) ) $txt = sprintf($txt, $arg);
			else for ( $i=0 ; $i<count($arg) ; $i++ ) $txt = str_replace("{".$i."}", $arg[$i], $txt);
		}
		return $txt;
	}
}
if (!isConnect()) {
	throw new Exception(inner::i18n('_noAccess'));
}
if (init('id') == '') {
	throw new Exception(inner::i18n("_idCantBeEmpty"));
}
$fileId = init(evohome::ARG_FILE_ID);
if ($fileId == '') {
	throw new Exception(inner::i18n("_saveIdCantBeEmpty"));
}
$evohome = evohome::byId(init('id'));
if (!is_object($evohome)) {
	throw new Exception(inner::i18n("_cantFindEvohome", init('id')));
}
if ($evohome->getEqType_name() != 'evohome') {
	throw new Exception(inner::i18n("_unwaitedType", $evohome->getEqType_name()));
}

echo "<div id='div_scheduleAlert' style='display: none;'></div>";

$scheduleToShow = evohome::getSchedule($fileId);

if ( $fileId == 0) {
	$subTitle = inner::i18n("_activeSchedule");
} else {
	$dt = new DateTime();
	$dt->setTimestamp($scheduleToShow['datetime']);
	$subTitle = inner::i18n("_scheduleInfo", [evohome::getFileInfosById($fileId)['name'], $dt->format('Y-m-d'), $dt->format('H:i:s')]);
	$scheduleCurrent = evohome::getSchedule(0);
	if ( json_encode($scheduleToShow['zones']) != json_encode($scheduleCurrent['zones']) ) {
		$infoDiff = inner::i18n("_scheduleIDiffAsCurrent");
	} else {
		$infoDiff = inner::i18n("_scheduleSameAsCurrent");
	}
	$subTitle .= '<br/><i>' . $infoDiff . '</i>';
}
$subTitle = "<a class=\"btn btn-success btn-sm tooltips\" onclick=\"showSchedule($fileId,\'V\');\">" . inner::i18n("_VforVertical") . "</a>&nbsp;" . $subTitle;
// not a good way to localize the title ; I'm too bad in jquery selectors :(
echo "<script>document.getElementById('ui-id-3').innerHTML = '" . $subTitle. "';</script>";

$days = array();
for ( $i=1; $i<=7 ;$i++ ) {
	$ts = mktime(0, 0, 0, 2, 10+$i, 2018);
	$days[strftime('%u', $ts)-1] = inner::i18n(strftime('%A', $ts));
}
$currentDay = strftime('%u', time())-1;
$currentTime = strftime('%H:%M', time());

//echo "<div style='display: visible;'>" . json_encode($scheduleToShow,true) . "</div>";
$z = 1;
foreach ( $scheduleToShow['zones'] as $mydata ) {
	echo "<table border=0>";
	echo "<tr><td colspan=7 style='font-size:16px;font-weight:800;background-color:#C0C0C0;'>&nbsp;&nbsp;" . $mydata['name'] . '</td></tr>';
	$dsSunday = $mydata['schedule']['DailySchedules'][6];
	$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
	$lastTemp = $spSundayLast['TargetTemperature'];
	$d=1;
	foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
		echo "<tr>";
		echo "<td";
		if ( $ds['DayOfWeek'] == $currentDay ) echo " style='background-color:lightgreen;'";
		echo ">&nbsp;" . $days[$ds['DayOfWeek']] . "</td>";
		echo "<td width=100%>";
		echo "<table border=1 width=100% style=\"font-family:'Open Sans', sans-serif;\">";
		echo "<tr style='font-size:11px;'>";
		for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
			$sp = $ds['Switchpoints'][$i-1];
			if ( $i == 1 and $sp['TimeOfDay'] != "00:00:00" ) {
				echo "<td>00:00</td>";
			}
			echo "<td>" . substr($sp['TimeOfDay'],0,5) . "</td>";
		}
		echo "</tr>";
		echo "<tr>";
		$mark = 0;
		for ( $i=1 ; $i <= sizeof($ds['Switchpoints']) ; $i++) {
			$sp = $ds['Switchpoints'][$i-1];
			$hm = substr($sp['TimeOfDay'],0,5);
			if ( $ds['DayOfWeek'] == $currentDay ) {
				if ( $i == sizeof($ds['Switchpoints']) ) {
					//if ( $currentTime > $hm ) {
						$mark++;
					//}
				} else {
					$spNext = $ds['Switchpoints'][$i];
					$hmNext = substr($spNext['TimeOfDay'],0,5);
					if ( $hmNext > $currentTime ) {
						$mark++;
					}
				}
			}
			if ( $i == 1 and $hm != "00:00" ) {
				$te = intval(substr($hm,0,2) . substr($hm,3,2));
				$w = $te / 2400.0 * 100.0;
				$bgc = evohome::getBackColorForConsigne($lastTemp);
				echo "<td align=center style='width:".$w."%;color:white;background-color:".$bgc.";'>..." . number_format($lastTemp,1) . "</td>";
			}
			$te = 2400;
			if ( $i < sizeof($ds['Switchpoints']) ) {
				$hmNext = substr($ds['Switchpoints'][$i]['TimeOfDay'],0,5);
				$te = intval(substr($hmNext,0,2) . substr($hmNext,3,2));
			}
			$td = intval(substr($hm,0,2) . substr($hm,3,2));
			$w = ($te - $td) / 2400.0 * 100.0;
			$bgc = evohome::getBackColorForConsigne($sp['TargetTemperature']);
			echo "<td align=center style='width:".$w."%;color:white;background-color:".$bgc.";'>" . number_format($sp['TargetTemperature'],1) . "</td>";
			$lastTemp = $sp['TargetTemperature'];
		}
		echo "</tr></table></td></tr>";
		$d++;
	}
	$z++;
	echo "</table><br/>";
}
?>
