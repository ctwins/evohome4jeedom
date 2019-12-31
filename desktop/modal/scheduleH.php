<?php
require_once dirname(__FILE__) . '/../../core/class/evohome.utils.php';

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
$locId = evohome::getLocationId($evohome);

$scheduleToShow = evohome::getSchedule($locId,$fileId);
if ( !is_array($scheduleToShow) ) {
	echo "Erreur de lecture<br/><br/>";
	return;
}
$currentSchedule = evohome::getSchedule($locId,evohome::CURRENT_SCHEDULE_ID);
$zoneId = init(evohome::ARG_ZONE_ID);
$edit = init('edit') === "1";
$typeSchedule = init("typeSchedule");
$subTitle = evohome::getScheduleSubTitle($id,$locId,$fileId,$currentSchedule,$scheduleToShow,evohome::CFG_SCH_MODE_VERTICAL,$zoneId,$typeSchedule,$edit);
$editAvailable = isAdmin() == 'true' && evoGetParam(evohome::CFG_SCH_EDIT_AVAILABLE,0) == 1;
if ( !$edit && array_key_exists('comment', $scheduleToShow) && $scheduleToShow['comment'] != '') {
	echo "<table width=100% style='background-color:white;color:black;'><tr>";
	echo "<td class='_vtop'>" . inner::i18n('Commentaire') . "&nbsp;:&nbsp;</td>";
	echo "<td width=90%>" . urldecode(str_replace('%0A','<br/>',$scheduleToShow['comment'])) . "</td>";
	echo "</tr><tr style='height:6px;'><td colspan=2></td></tr></table>";
}
echo "<div id='scheduleTable'></div>";
?>
<style>
._vtop{vertical-align:top;}
._t1{border-left:1px solid lightgray;}
._t2{border:1px solid lightgray;}
._t3{height:34px;font-size:16px;font-weight:600;color:black;background-color:#C0C0C0 !important;}
._t4{font-family:'Open Sans', sans-serif;font-size:12px;cursor:default;}
._t4e{font-family:'Open Sans', sans-serif;font-size:12px;cursor:pointer;}
._virtual{background-color:#A0A0A0;}
._rowTdA{border-bottom:1px solid lightgray;}
._rowTdB{color:black;background-color:lightgreen;}
._slice1{color:black;background-color:lightgreen;}
._slice2{background-color:#F0F0F0;color:gray;}
._edit{position:absolute;right:20px;font-weight:600;color:black;padding-bottom:2px;padding-top:3px;}
<?php if ( $edit ) { ?>
.myButton{padding-left:11px;padding-right:11px;top:0px;}
.myInput{width:42px;font-size:16px;font-weight:600;}
.myAppend{width:80px;background-color:orangered !important;}
.myValid{width:80px;background-color:#42b142 !important;}
.mySave{width:120px;background-color:#42b142 !important;}
.myComment{vertical-align:top;padding-left:4px;}
._nocheck1{border-radius:5px;background-color:lightgray;cursor:pointer;}
._nocheck2{height:14px;width:14px;}
<?php } ?>
</style>
<?php if ( $edit ) { ?>
<table width="100%">
	<tr style="height:80px">
		<td class="_vtop" style="width:100px;">
			<a id="copyBtn" class="btn btn-primary btn-sm" style="width:80px;" onclick="_evs.copyDays();" disabled><?php echo inner::i18n("Copier")?></a>
		</td>
		<td style="text-align:-webkit-center;">
			<table><tr>
				<td>
					<a id="prevSlice" class="btn btn-default glyphicon2 glyphicon2-chevron-left myButton" onclick="_evs.goSlice(-1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-minus myButton" onclick="_evs.adjustHours(-1);" />
				</td>
				<td class="_vtop" >
					<input id="hours" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="01">
				</td>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-plus myButton" onclick="_evs.adjustHours(1);" />
				</td>
				<td style="font-size:8px;width:16px;padding-bottom:2px;">
					<table width=100% style="text-align:center;">
						<tr><td><span class="fa fa-circle"></span></td></tr>
						<tr><td><span class="fa fa-circle"></span></td></tr>
					</table>
				</td>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-minus myButton" onclick="_evs.adjustMinutes(-1);" />
				</td>
				<td class="_vtop">
					<input id="minutes" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="10">
				</td>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-plus myButton" onclick="_evs.adjustMinutes(1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-minus myButton" onclick="_evs.adjustSetpoint(-1);" />
				</td>
				<td class="_vtop">
					<input id="setpoint" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="18.5">
				</td>
				<td>
					<a class="btn btn-default glyphicon2 glyphicon2-plus myButton" onclick="_evs.adjustSetpoint(1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a id="nextSlice" class="btn btn-default glyphicon2 glyphicon2-chevron-right myButton" onclick="_evs.goSlice(1);" />
				</td>
				<td style="width:50px;"/>
				<td>
					<a id="btnAppend" class="btn btn-primary btn-sm myAppend" onclick="_evs.append();" disabled><?php echo inner::i18n("Ajouter")?></a>
					&nbsp;&nbsp;
					<a id="btnValid" class="btn btn-success btn-sm myValid" onclick="_evs.validate();"><?php echo inner::i18n("Valider")?></a>
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
						<a id="btnSave" class="btn btn-success btn-sm mySave" onclick="_evs.saveSchedule();"><?php echo inner::i18n("Sauvegarder")?></a>&nbsp;
					</td>
				</tr>
				<tr>
					<td class="myComment"><?php echo inner::i18n("Commentaire")?></td>
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
				<td colspan=2><input type='radio' id='scd' name='saveChoice' value='D' checked onchange='_evs.showFilledDays();'>&nbsp;<label for="scd">Jour sélectionné</label>
				&nbsp;&nbsp;<input type='radio' id='scw' name='saveChoice' value='W' onchange='_evs.showFilledDays();'>&nbsp;<label for="scw">Semaine complète</label>
				</td></tr>
				<tr><td style='vertical-align:top;'>Zones remplacées</td>
				<td colspan=2 id='selectZonesAndDays'></td></tr>
				-->
			</table>
		</td>
	</tr>
</table>
<?php } ?>
<script type="text/javascript" src="plugins/evohome/desktop/js/scheduleH-min.js"></script>
<script>
<?php
echo "var _evs = new EvoSchedule(typeof genConsoleId == 'undefined' ? 0 : genConsoleId[$locId]);\n";
echo "var ts='$typeSchedule';\n";
if ( $edit ) {
	//echo "$('.showCurrentSchedule$typeSchedule$zoneId').css('background-color', 'rgb(16, 208, 16)');";
	if ( $typeSchedule == 'S' ) echo "$('#saveName')[0].value = _evs.scheduleSelectedName;\n";
}
$iZone = 0;
$equNamesById = evohome::getEquNamesAndId($locId);
if ( is_array($scheduleToShow) ) {
	foreach ( $scheduleToShow['zones'] as $mydata ) {
		$myZoneId = $mydata['zoneId'];
		if ( !$edit && $zoneId != 0 && $zoneId != $myZoneId ) continue;
		if ( $equNamesById[$myZoneId] == null ) continue;
		echo '_evs.zones[' . $iZone++ . '] = new _evs.Zone(' . $myZoneId . ',"' . $mydata['name'] . '","' . $equNamesById[$myZoneId];
		if ( !$edit && $fileId != evohome::CURRENT_SCHEDULE_ID && json_encode($mydata) != json_encode(extractZone($currentSchedule,$myZoneId)) ) {
			echo ' *';
		}
		echo "\",[\n";
		$iSchedule = 0;
		$dsSunday = $mydata['schedule']['DailySchedules'][6];	// ATTENTION, valable si Schedule semaine complète
		$spSundayLast = $dsSunday['Switchpoints'][sizeof($dsSunday['Switchpoints'])-1];
		$lastTemp = $spSundayLast['heatSetpoint'];
		// 0.4.0 - manage now an empty schedule
		if ( count($mydata['schedule']['DailySchedules']) == 0 ) {
			for ($d=0 ; $d<=6 ; $d++) {
				if ( $d > 0 ) echo ",\n";
				echo "_evs._S($d, [_evs._VP()])";
			}
		} else {
			foreach ( $mydata['schedule']['DailySchedules'] as $ds ) {
				if ( $iSchedule++ > 0 ) echo ",\n";
				echo "_evs._S(" . $ds['DayOfWeek'] . ", [";
				$iSetpoint = 0;
				if ( $ds['Switchpoints'][0]['TimeOfDay'] != '00:00:00' ) {
					echo "_evs._VP()";
					$iSetpoint++;
				}
				foreach ( $ds['Switchpoints'] as $sp) {
					if ( $iSetpoint++ > 0 ) echo ",";
					echo "_evs._P(" . $sp['heatSetpoint'] . ",'" . $sp['TimeOfDay'] . "')";
				}
				echo "])";
				$lastTemp = $ds['Switchpoints'][sizeof($ds['Switchpoints'])-1]['heatSetpoint'];
			}
		}
		echo "]);\n";
	}
}
const cDays = ["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"];
for ( $i=0; $i<7 ;$i++ ) echo "_evs.days[$i] = '" . inner::i18n(cDays[$i]) . "';";
echo "_evs.typeSchedule = '$typeSchedule';";
echo "_evs.zoneId = $zoneId;";
echo "_evs.editAvailable = " . ($editAvailable /*&& $fileId != 0*/ ? "true" : "false") . ";";
echo "_evs.edit = " . ($edit ? "true" : "false") . ";\n";
$lblEdit = inner::i18n("Editer");
echo "var subTitle = \"$subTitle\";\n";
if ( $zoneId > 0 ) {
	echo "if ( _evs.isEditAvailable() ) subTitle += \"<a id='btnEdit' style='position:absolute;right:50px;' class='btn btn-success btn-sm' onclick='_evs.openEdit($zoneId);'>$lblEdit</a>\";\n";
}
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = subTitle;\n";
echo "_evs.lblEdit = \"$lblEdit\";\n";
echo "_evs.lblValidate = \"" . inner::i18n("Valider") . "\";\n";
echo "_evs.lblCopyTo = \"" . inner::i18n("Recopier vers") . "\";\n";
echo "_evs.lblCopyToTitle = \"" . inner::i18n("Que souhaitez-vous remplacer dans '{1}'<br/>à partir de '{0}' ?") . "\";\n";
echo "_evs.lblOpenAfterCopy = \"" . inner::i18n("Ouvrir '{0}' après la copie ?") . "\";\n";
echo "_evs.lblInheritTitle = \"" . inner::i18n("La consigne appliquée est la dernière de la veille") . "\";\n";
echo "_evs.lblCantRemoveLastPoint = \"" . inner::i18n("Vous ne pouvez pas supprimer la dernière tranche horaire") . "\";\n";
echo "_evs.lblFileNameEmpty = \"" . inner::i18n("Vous devez spécifier un nom pour le fichier de sauvegarde") . "\";\n";
echo "_evs.lblRemovePoint = \"" . inner::i18n("Supprimer la consigne {0}° de {1} à {2} ?") . "\";\n";
echo "_evs.lblRemovePoint2 = \"" . inner::i18n("Supprimer la consigne {0}° à {1} ?") . "\";\n";
echo "_evs.lblCopyDay = \"" . inner::i18n("Recopier {0} sur {1} ?") . "\";\n";
echo "_evs.lblCreate = \"" . inner::i18n("Confirmez-vous la création de '{0}' ?") . "\";\n";
echo "_evs.lblReplaceSave = \"" . inner::i18n("Confirmez-vous le remplacement de la sauvegarde '{0}' ?") . "\";\n";
echo "_evs.lblConfirmSave = \"" . inner::i18n("Confirmez-vous la sauvegarde vers '{0}' ?") . "\";\n";
echo "_evs.scheduleOpenCmd = 'index.php?v=d&plugin=evohome&modal=schedule" . evohome::CFG_SCH_MODE_HORIZONTAL .
	"&id=$id&" .evohome::ARG_ZONE_ID . "=_zoneId_&" . evohome::ARG_FILE_ID . "=$fileId" .
	"&mode=" . evohome::CFG_SCH_MODE_HORIZONTAL .
	"&edit=1" .
	"&typeSchedule=$typeSchedule'\n";
echo "lblSaveTo = \"" . inner::i18n("Enregistre la programmation vers '{0}'...") . "\";\n";
?>
_evs.displayHTable();
function evohomeSave(fileId, fileName, eComm, scheduleToSave) {
	jeedom.cmd.execute({id:"<?php echo evohome::getActionSaveId($locId);?>", notify:false,
		value:{<?php echo evohome::ARG_FILE_ID?>:fileId, <?php echo evohome::ARG_FILE_NAME?>:fileName,
			   <?php echo evohome::ARG_FILE_REM?>:eComm, <?php echo evohome::ARG_FILE_NEW_SCHEDULE?>:scheduleToSave}
		});
	waitingMessage(getMsg(lblSaveTo,[fileName]));
}
</script>
