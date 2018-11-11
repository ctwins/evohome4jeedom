<?php
class inner {
	static function i18n($iText, $args=null) {
		if ( substr($iText,-1) == '}' ) $iText .= '__';
		$txt = __($iText, "plugins/evohome/desktop/modal/schedule");
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
$zoneId = init(evohome::ARG_ZONE_ID);
$edit = init('edit') === "1";
$typeSchedule = init("typeSchedule");
$subTitle = evohome::getScheduleSubTitle($fileId,$scheduleToShow,evohome::CFG_SCH_MODE_VERTICAL,$zoneId,$typeSchedule,$edit);
$editAvailable = evohome::getParam(evohome::CFG_SCH_EDIT_AVAILABLE,0) == 1;
if ( $editAvailable && !$edit && /*$fileId != 0 &&*/ $zoneId > 0 ) {
	$subTitle .= "<a id='btnEdit' style='position:absolute;right:50px;' class='btn btn-success btn-sm' onclick='evoSchedule.openEdit($zoneId);'>" . inner::i18n("Editer") . "</a>";
	//echo "if ( $('#md_modal')[0].style.cssText.search('background') == -1 ) $('#md_modal')[0].style.cssText += 'background-color:white !important';";
}
if ( !$edit && array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td style='vertical-align:top;'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}
echo "<div id='scheduleTable'></div>";
if ( $edit ) { ?>
<style>
.myButton{padding-left:11px;padding-right:11px;top:0px;}
.myInput{width:42px;font-size:16px;font-weight:600;}
</style>
<table width="100%">
	<tr style="height:80px";>
		<td style="vertical-align:top;width:100px;">
			<a id="copyBtn" class="btn btn-primary btn-sm" style="width:80px;" onclick="evoSchedule.copyDays();" disabled><?php echo inner::i18n("Copier")?></a>
		</td>
		<td style="text-align:-webkit-center;">
			<table><tr>
				<td>
					<a id="prevSlice" class="btn btn-default glyphicon glyphicon-chevron-left myButton" onclick="evoSchedule.goSlice(-1);" />
				</td>
				<td style="width:20px"; />
				<td>
					<a class="btn btn-default glyphicon glyphicon-minus myButton" onclick="evoSchedule.adjustHours(-1);" />
				</td>
				<td style="vertical-align:top;">
					<input id="hours" type="text" onchange="evoSchedule.checkAppendAndValid();" class="form-control text-center myInput" value="01">
				</td>
				<td>
					<a class="btn btn-default glyphicon glyphicon-plus myButton" onclick="evoSchedule.adjustHours(1);" />
				</td>
				<td style="font-size:8px;width:16px;padding-bottom:2px;">
					<table width=100% style="text-align:center;">
						<tr><td><span class="fa fa-circle"></span></td></tr>
						<tr><td><span class="fa fa-circle"></span></td></tr>
					</table>
				</td>
				<td>
					<a class="btn btn-default glyphicon glyphicon-minus myButton" onclick="evoSchedule.adjustMinutes(-1);" />
				</td>
				<td style="vertical-align:top;">
					<input id="minutes" type="text" onchange="evoSchedule.checkAppendAndValid();" class="form-control text-center myInput" value="10">
				</td>
				<td>
					<a class="btn btn-default glyphicon glyphicon-plus myButton" onclick="evoSchedule.adjustMinutes(1);" />
				</td>
				<td style="width:20px"; />
				<td>
					<a class="btn btn-default glyphicon glyphicon-minus myButton" onclick="evoSchedule.adjustSetpoint(-1);">
				</td>
				<td style="vertical-align:top;">
					<input id="setpoint" type="text" onchange="evoSchedule.checkAppendAndValid();" class="form-control text-center myInput" value="18.5">
				</td>
				<td>
					<a class="btn btn-default glyphicon glyphicon-plus myButton" onclick="evoSchedule.adjustSetpoint(1);">
				</td>
				<td style="width:20px"; />
				<td>
					<a id="nextSlice" class="btn btn-default glyphicon glyphicon-chevron-right myButton" onclick="evoSchedule.goSlice(1);" />
				</td>
				<td style="width:50px"; />
				<td>
					<a id="btnAppend" class="btn btn-primary btn-sm" style="width:80px;background-color:orangered !important;" onclick="evoSchedule.append();" disabled><?php echo inner::i18n("Ajouter")?></a>
					&nbsp;&nbsp;
					<a id="btnValid" class="btn btn-success btn-sm" style="width:80px;background-color:#42b142 !important;" onclick="evoSchedule.validate();"><?php echo inner::i18n("Valider")?></a>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width=100% colspan=2>
			<table width=100% style="padding-left:8px;padding-right:8px;">
				<tr style="height:38px;">
					<td style="width:160px;padding-left:4px;"><?php echo inner::i18n("Nom du fichier")?></td>
					<td><input type="text" id="saveName" /></td>
					<td rowspan=2 style="text-align:center;">
						<a id="btnSave" class="btn btn-success btn-sm" style="width:120px;background-color:#42b142 !important;" onclick="evoSchedule.saveSchedule();"><?php echo inner::i18n("Sauvegarder")?></a>&nbsp;
					</td>
				</tr>
				<tr>
					<td style='vertical-align:top;padding-left:4px;'><?php echo inner::i18n("Commentaire")?></td>
					<td colspan=2><textarea style="height:80px;width:400px;" id="idComment"><?php
					if ( array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
						echo urldecode(str_replace('%0A',chr(10),$scheduleToShow['comment']));
					} ?></textarea>
					<br/><br/>
					</td>
					<td/>
				</tr>
				<!--
				<tr style="height:38px;"><td style='vertical-align:top;'>Sélection</td>
				<td colspan=2><input type='radio' id='scd' name='saveChoice' value='D' checked onchange='evoSchedule.showFilledDays();'>&nbsp;<label for="scd">Jour sélectionné</label>
				&nbsp;&nbsp;<input type='radio' id='scw' name='saveChoice' value='W' onchange='evoSchedule.showFilledDays();'>&nbsp;<label for="scw">Semaine complète</label>
				</td></tr>
				<tr><td style='vertical-align:top;'>Zones remplacées</td>
				<td colspan=2 id='selectZonesAndDays'></td></tr>
				-->
			</table>
		</td>
	</tr>
</table>
<?php
}
?>
<script type="text/javascript" src="plugins/evohome/desktop/js/scheduleH-min.js"></script>
<script>
var evoPoints = null;
<?php
echo "var ts='$typeSchedule';";
if ( $edit ) {
	//echo "$('.showCurrentSchedule$typeSchedule$zoneId').css('background-color', 'rgb(16, 208, 16)');";
	if ( $typeSchedule == 'S' ) echo "$('#saveName')[0].value = evoSchedule.scheduleSelectedName;";
}
$iZone = 0;
$equNamesById = evohome::getEquNamesAndId();
foreach ( $scheduleToShow['zones'] as $mydata ) {
	$myZoneId = $mydata['zoneId'];
	if ( !$edit && $zoneId != 0 && $zoneId != $myZoneId ) continue;
	if ( $equNamesById[$myZoneId] == null ) continue;
	echo "evoSchedule.zones[" . $iZone++ . "] = new evoSchedule.EvoZone($myZoneId,\"" . $mydata['name'] . "\",\"" . $equNamesById[$myZoneId] . "\",[\n";
	$iSchedule = 0;
	$dsSunday = $mydata['schedule']['DailySchedules'][6];	// ATTENTION, valable si Schedule semaine complète
	$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
	$lastTemp = $spSundayLast['heatSetpoint'];
	foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
		if ( $iSchedule++ > 0 ) echo ",\n";
		echo "new evoSchedule.EvoSchedule(" . $ds['DayOfWeek'] . ", [";
		$iSetpoint = 0;
		if ( $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00' ) {
			//echo "new evoSchedule.EvoPoint(" . $lastTemp . ",'00:00:00',true)";
			echo "evoSchedule.buildVirtualPoint()";
			$iSetpoint++;
		}
		foreach ( $ds['Switchpoints'] as $sp) {
			if ( $iSetpoint++ > 0 ) echo ",\n";
			echo "new evoSchedule.EvoPoint(" . $sp['heatSetpoint'] . ",'" . $sp['TimeOfDay'] . "',false)";
		}
		echo "])";
		$lastTemp = $ds['Switchpoints'][sizeof($ds['Switchpoints'])-1]['heatSetpoint'];
	}
	echo "]);\n";
}
const cDays = ["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"];
for ( $i=1; $i<=7 ;$i++ ) {
	$ts = mktime(0, 0, 0, 2, 10+$i, 2018);
	echo "evoSchedule.days[" . (strftime('%u', $ts)-1) . "] = '" . inner::i18n(cDays[date('N', $ts)-1]) . "';\n";
}
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = \"$subTitle\";\n";
// so the background is really white (and not transparent) when printing
// ....
echo "evoSchedule.typeSchedule = '$typeSchedule';";
echo "evoSchedule.editAvailable = " . ($editAvailable /*&& $fileId != 0*/ ? "true" : "false") . ";\n";
echo "evoSchedule.edit = " . ($edit ? "true" : "false") . ";\n";
echo "evoSchedule.zoneId = $zoneId;\n";
echo "evoSchedule.lblEdit = \"" . inner::i18n("Editer") . "\";\n";
echo "evoSchedule.lblValidate = \"" . inner::i18n("Valider") . "\";\n";
echo "evoSchedule.lblCopyTo = \"" . inner::i18n("Recopier vers") . "\";\n";
echo "evoSchedule.lblCopyToTitle = \"" . inner::i18n("Que souhaitez-vous remplacer dans '{1}'<br/>à partir de '{0}' ?") . "\";\n";
echo "evoSchedule.lblOpenAfterCopy = \"" . inner::i18n("Ouvrir '{0}' après la copie ?") . "\";\n";
echo "evoSchedule.lblInheritTitle = \"" . inner::i18n("La consigne appliquée est la dernière de la veille") . "\";\n";
echo "evoSchedule.lblCantRemoveLastPoint = \"" . inner::i18n("Vous ne pouvez pas supprimer la dernière tranche horaire") . "\";\n";
echo "evoSchedule.lblFileNameEmpty = \"" . inner::i18n("Vous devez spécifier un nom pour le fichier de sauvegarde") . "\";\n";
echo "evoSchedule.lblRemovePoint = \"" . inner::i18n("Supprimer la consigne {0}° de {1} à {2} ?") . "\";\n";
echo "evoSchedule.lblRemovePoint2 = \"" . inner::i18n("Supprimer la consigne {0}° à {1} ?") . "\";\n";
echo "evoSchedule.lblCopyDay = \"" . inner::i18n("Recopier {0} sur {1} ?") . "\";\n";
echo "evoSchedule.lblCreate = \"" . inner::i18n("Confirmez-vous la création de '{0}' ?") . "\";\n";
echo "evoSchedule.lblReplaceSave = \"" . inner::i18n("Confirmez-vous le remplacement de la sauvegarde '{0}' ?") . "\";\n";
echo "evoSchedule.lblConfirmSave = \"" . inner::i18n("Confirmez-vous la sauvegarde vers '{0}' ?") . "\";\n";
echo "evoSchedule.scheduleOpenCmd = 'index.php?v=d&plugin=evohome&modal=schedule" . evohome::CFG_SCH_MODE_HORIZONTAL .
	"&id=$id&" .evohome::ARG_ZONE_ID . "=_zoneId_&" . evohome::ARG_FILE_ID . "=$fileId" .
	"&mode=" . evohome::CFG_SCH_MODE_HORIZONTAL .
	"&edit=1" .
	"&typeSchedule=$typeSchedule'\n";
echo "lblSaveTo = \"" . inner::i18n("Enregistre la programmation vers '{0}'...") . "\";\n";
?>
evoSchedule.displayHTable();
function evohomeSave(fileId, fileName, eComm, scheduleToSave) {
	jeedom.cmd.execute({id:"<?php echo evohome::getActionSaveId();?>", notify:false,
		value:{<?php echo evohome::ARG_FILE_ID?>:fileId, <?php echo evohome::ARG_FILE_NAME?>:fileName,
			   <?php echo evohome::ARG_FILE_REM?>:eComm, <?php echo evohome::ARG_FILE_NEW_SCHEDULE?>:scheduleToSave}
		});
	waitingMessage(evoSchedule.getMsg(lblSaveTo,[fileName]));
}
</script>
