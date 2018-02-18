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
$fileId = init('fileId');
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
$subTitle = "<a class=\"btn btn-success btn-sm tooltips\" onclick=\"showSchedule($fileId,\'H\');\">" . inner::i18n("_HforHorizontal") . "</a>&nbsp;" . $subTitle;
// not a good way to localize the title ; I'm too bad in jquery selectors :(
echo "<script>document.getElementById('ui-id-3').innerHTML = '" . $subTitle. "';</script>";

$days = array();
for ( $i=1; $i<=7 ;$i++ ) {
	$ts = mktime(0, 0, 0, 2, 10+$i, 2018);
	$days[strftime('%u', $ts)-1] = inner::i18n(strftime('%A', $ts));
}
$currentDay = strftime('%u', time())-1;
$currentTime = strftime('%H:%M', time());

//echo "<div style='display: visible;'>" . $scheduleToShow . "</div>";
foreach ( $scheduleToShow['zones'] as $mydata ) {
	echo "<table border=0>";
	echo "<tr><td colspan=7 style='font-size:14px;font-weight:800;background-color:#C0C0C0;'>&nbsp;&nbsp;" . $mydata['name'] . '</td></tr>';
	echo "<tr>";
	$dsSunday = $mydata['schedule']['DailySchedules'][6];
	$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
	$lastTemp = $spSundayLast['TargetTemperature'];
	foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
		echo "<td valign='top'>";
		echo "<table border=1 style=\"font-family:'Open Sans', sans-serif;\">";
		echo "<tr><td align=center colspan=2 class=ui-widget-header";
		if ( $ds['DayOfWeek'] == $currentDay ) echo " style='background-color:lightgreen;'";
		echo ">" . $days[$ds['DayOfWeek']] . "</td></tr>";
		$mark = 0;
		$midnightAdded = $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00';
		if ( $midnightAdded ) {
			array_unshift($ds['Switchpoints'], array('TimeOfDay'=>'00:00:00', 'TargetTemperature'=>$lastTemp));
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
			echo "<tr><td";
			if ( $mark == 1 ) echo " style='background-color:lightgreen;'";
			echo ">&nbsp;$hm&nbsp;</td>";
			$bgc = evohome::getBackColorForConsigne($sp['TargetTemperature']);
			$sTemp = ($midnightAdded ? '...' : '' ) . number_format($sp['TargetTemperature'],1);
			$midnightAdded = false;
			echo "<td align=center width=40 style='color:white;background-color:".$bgc.";'>" . $sTemp . "</td></tr>";
			$lastTemp = $sp['TargetTemperature'];
		}
		echo "</table></td>";
	}
	echo "</tr></table><br/>";
}
?>
