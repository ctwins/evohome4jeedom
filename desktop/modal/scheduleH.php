<?php
require_once dirname(__FILE__) . '/../../core/class/honeywell.class.php';

class inner {
	static function i18n($txt, $args=null) {
	    //honeyutils::logDebug("scheduleH.i18($txt,$args)");
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
if ($eqLogic->getEqType_name() != honeywell::PLUGIN_NAME) {
	throw new Exception(inner::i18n("Cet équipement n'est pas du type attendu : {0}", $eqLogic->getEqType_name()));
}
$locId = $eqLogic->getLocationId();

$scheduleToShow = honeywell::getSchedule($locId,$fileId);
if ( !is_array($scheduleToShow) ) {
	echo "Erreur de lecture<br/><br/>";
	return;
}
$currentSchedule = honeywell::getSchedule($locId,honeywell::CURRENT_SCHEDULE_ID);
$zoneId = init(honeywell::ARG_ZONE_ID);
$edit = init('edit') === "1";
$scheduleSource = init("scheduleSource");
$subTitle = honeywell::getScheduleSubTitle($id,$locId,$fileId,'T',$currentSchedule,$scheduleToShow,honeywell::CFG_SCH_MODE_VERTICAL,$zoneId,$scheduleSource,$edit);
$editAvailable = honeyutils::isAdmin() == 'true' && honeyutils::getParam(honeywell::CFG_SCH_EDIT_AVAILABLE,0) == 1;
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
._t3{height:34px;font-size:16px;font-weight:600;color:black;background-color:#C0C0C0;padding-top:8px !important;}
._t31{float:right;margin-right:4px;margin-top:-4px;}
._t4{font-family:'Open Sans', sans-serif;font-size:12px;cursor:default;}
._t4e{font-family:'Open Sans', sans-serif;font-size:12px;cursor:pointer;}
._virtual{background-color:#A0A0A0;cursor:pointer !important;}
._rowTdA{border-bottom:1px solid lightgray;}
._rowTdB{color:black;background-color:lightgreen;}
._slice1{color:black;background-color:lightgreen;}
._slice2{background-color:#F0F0F0;color:gray;}
._edit{float:right;margin-right:4px;margin-top:-5px;font-weight:600;color:black;}
<?php if ( $edit ) { ?>
.myButton{padding-left:11px;padding-right:11px;padding-top:10px;}
.myInput{width:56px;font-size:16px !important;font-weight:600;}
.myAppend{width:80px;}
.myValid{width:80px;background-color:#42b142 !important;}
.mySave{width:120px;background-color:#42b142 !important;}
.myComment{vertical-align:top;padding-left:4px;}
._nocheck1{border-radius:5px;background-color:lightgray;cursor:pointer;}
._nocheck2{height:14px;width:14px;}
.removePoint{cursor:pointer !important;}
.lstZone{background-color:lightgray !important;width:unset;padding-right:32px !important;margin-top:-8px !important;}
<?php } ?>
</style>
<?php if ( $edit ) { ?>
<table width="100%" class="tableCmd">
	<tr style="height:80px">
		<td class="_vtop" style="width:100px;"></td>
		<td style="text-align:-webkit-center;vertical-align:middle !important">
			<table><tr>
				<td>
					<a id="prevSlice" class="btn btn-primary fas fa-chevron-left myButton" onclick="_evs.goSlice(-1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a class="btn btn-primary fas fa-minus myButton" onclick="_evs.adjustHours(-1);" />
				</td>
				<td class="_vtop" >
					<input id="hours" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="01">
				</td>
				<td>
					<a class="btn btn-primary fas fa-plus myButton" onclick="_evs.adjustHours(1);" />
				</td>
				<td style="width:16px;text-align:center;font-size:8px !important;padding-top:4px;">
					<div class="fa fa-circle"></div>
					<div class="fa fa-circle"></div>
				</td>
				<td>
					<a class="btn btn-primary fas fa-minus myButton" onclick="_evs.adjustMinutes(-1);" />
				</td>
				<td class="_vtop">
					<input id="minutes" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="10">
				</td>
				<td>
					<a class="btn btn-primary fas fa-plus myButton" onclick="_evs.adjustMinutes(1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a class="btn btn-primary fas fa-minus myButton" onclick="_evs.adjustSetpoint(-1);" />
				</td>
				<td class="_vtop">
					<input id="setpoint" type="text" onchange="_evs.checkAppendAndValid();" class="form-control text-center myInput" value="18.5">
				</td>
				<td>
					<a class="btn btn-primary fas fa-plus myButton" onclick="_evs.adjustSetpoint(1);" />
				</td>
				<td style="width:20px;"/>
				<td>
					<a id="nextSlice" class="btn btn-primary fas fa-chevron-right myButton" onclick="_evs.goSlice(1);" />
				</td>
				<td style="width:50px;"/>
				<td>
					<a id="btnAppend" class="btn btn-primary btn-sm myAppend" style="background-color:orangered !important;" onclick="_evs.append();" disabled><?php echo inner::i18n("Ajouter")?></a>
					&nbsp;&nbsp;
					<a id="btnValid" class="btn btn-success btn-sm myValid" onclick="_evs.validate();"><?php echo inner::i18n("Valider")?></a>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width=100% colspan=2>
			<table width=100% class="tableCmd" style="padding-left:8px;padding-right:8px;">
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
<?php }
echo '<script type="text/javascript" src="plugins/'.honeywell::PLUGIN_NAME.'/desktop/js/scheduleH-min.js?' . filemtime('plugins/'.honeywell::PLUGIN_NAME.'/desktop/js/scheduleH-min.js') . '"></script>';
 ?>
<script>
$('.ui-widget-overlay.ui-front').hide();	// 0.4.3
<?php
echo "var _evs = new EvoSchedule('".honeywell::PLUGIN_NAME."', typeof hnwConsole =='undefined' || typeof hnwConsole.genConsoleId == 'undefined' ? 0 : hnwConsole.genConsoleId[$locId]);\n";
echo "var ts='$scheduleSource';\n";
if ( $edit && $scheduleSource == 'S' ) echo "$('#saveName')[0].value = _evs.scheduleSelectedName;\n";
$iZone = 0;
$equNamesById = honeywell::getEquNamesAndId($locId);
if ( is_array($scheduleToShow) ) {
	foreach ( $scheduleToShow['zones'] as $mydata ) {
		$myZoneId = $mydata['zoneId'];
		if ( !$edit && $zoneId != '0' && $zoneId != $myZoneId ) continue;
		if ( $equNamesById[$myZoneId] == null ) continue;
		echo '_evs.zones[' . $iZone++ . '] = new _evs.Zone("' . $myZoneId . '","' . $mydata['name'] . '","' . $equNamesById[$myZoneId];
		if ( !$edit && $fileId != honeywell::CURRENT_SCHEDULE_ID && json_encode($mydata) != json_encode(honeyutils::extractZone($currentSchedule,$myZoneId)) ) {
			echo ' *';
		}
		echo "\",[\n";
		$iSchedule = 0;
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
			}
		}
		echo "]);\n";
	}
}
for ( $i=0; $i<7 ;$i++ ) {
    echo "_evs.days[$i] = '" . inner::i18n(["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"][$i]) . "';";
}
echo "_evs.scheduleSource = '$scheduleSource';";
echo "_evs.zoneId = '$zoneId';";
echo "_evs.editAvailable = " . ($editAvailable /*&& $fileId != 0*/ ? "true" : "false") . ";";
echo "_evs.edit = " . ($edit ? "true" : "false") . ";\n";
$lblEdit = inner::i18n("Editer");
echo "var subTitle = \"$subTitle\";\n";
$displayMode = init("displayMode");	// empty ('') by default
$nDay = init("nDay");
if ( $zoneId == '0' && ($nDay == '' || $nDay == -1) ) {
	$lblNewDisplayMode = inner::i18n($displayMode == 'D' ? "Zone" : "Jour");
	echo "subTitle += \"<div style='position:absolute;top:0px;right:40px;padding:5px;'><a id='btnDisplayMode' class='btn btn-success btn-sm'";
	$modeH = honeywell::CFG_SCH_MODE_HORIZONTAL;
	echo " onclick='showScheduleCO($id,\\\"T\\\",\\\"$scheduleSource\\\",$fileId,\\\"$modeH\\\",\\\"$displayMode\\\" === _evs.DM_BY_DAY ? _evs.DM_BY_ZONE : _evs.DM_BY_DAY);'";
	echo ">/$lblNewDisplayMode</a></div>\";\n";
} else {
	echo "if ( _evs.isEditAvailable() ) subTitle += \"<div style='position:absolute;top:0px;right:40px;;padding:5px;'><a id='btnEdit' class='btn btn-success btn-sm' onclick='_evs.openEdit(\\\"$zoneId\\\",-1,1,_evs.DM_BY_ZONE);'>$lblEdit</a></div>\";\n";
}
echo "$('#md_modal')[0].previousSibling.firstChild.innerHTML = subTitle;\n";
echo "$('#md_modal').dialog('option', 'width', Math.min(1000,jQuery(window).width()-16));\n";
echo "$('#md_modal').dialog('option', 'height', jQuery(window).height() - 60);\n";
echo "$('#md_modal').dialog('option', 'position', {my:'center top', at:'center top+40', of:window});\n";
echo "_evs.lblEdit = \"$lblEdit\";\n";
echo "_evs.lblValidate = \"" . inner::i18n("Valider") . "\";\n";
echo "_evs.lblCopyTo = \"" . inner::i18n("Copie") . "...\";\n";
echo "_evs.lblCopyToTitle = \"" . inner::i18n("Copie de '{0}' vers") . "\";\n";
echo "_evs.lblOpenAfterCopy = \"" . inner::i18n("Ouvrir la zone cible après la copie ?") . "\";\n";
echo "_evs.lblCopyNoTarget = \"" . inner::i18n("Vous n'avez pas spécifié de cible") . "\";\n";
echo "_evs.lblCopyNoTargetDay = \"" . inner::i18n("Vous n'avez pas spécifié de jour cible") . "\";\n";
echo "_evs.lblCopyTargetDays = \"" . inner::i18n("Jours") . "\";\n";
echo "_evs.lblCopyTargetZones = \"" . inner::i18n("Zones") . "\";\n";
echo "_evs.lblInheritTitle = \"" . inner::i18n("La consigne appliquée est la dernière de la veille") . "\";\n";
echo "_evs.lblCantRemoveLastPoint = \"" . inner::i18n("Vous ne pouvez pas supprimer la dernière tranche horaire") . "\";\n";
echo "_evs.lblFileNameEmpty = \"" . inner::i18n("Vous devez spécifier un nom pour le fichier de sauvegarde") . "\";\n";
echo "_evs.lblRemovePoint = \"" . inner::i18n("Supprimer la consigne {0}° de {1} à {2} ?") . "\";\n";
echo "_evs.lblRemovePoint2 = \"" . inner::i18n("Supprimer la consigne {0}° à {1} ?") . "\";\n";
echo "_evs.lblCopyConfirm = \"" . inner::i18n("Recopier {0} sur {1} ?") . "\";\n";
echo "_evs.lblCreate = \"" . inner::i18n("Confirmez-vous la création de '{0}' ?") . "\";\n";
echo "_evs.lblReplaceSave = \"" . inner::i18n("Confirmez-vous le remplacement de la sauvegarde '{0}' ?") . "\";\n";
echo "_evs.lblConfirmSave = \"" . inner::i18n("Confirmez-vous la sauvegarde vers '{0}' ?") . "\";\n";
echo "_evs.scheduleOpenCmd = 'index.php?v=d" .
    "&plugin=" . honeywell::PLUGIN_NAME .
	"&modal=schedule" . honeywell::CFG_SCH_MODE_HORIZONTAL .
	"&id=$id" .
	"&" . honeywell::ARG_ZONE_ID . "=_zoneId_" .
	"&" . honeywell::ARG_FILE_ID . "=$fileId" .
	"&displayMode=_displayMode_" .
	"&nDay=_nDay_" .
	"&mode=" . honeywell::CFG_SCH_MODE_HORIZONTAL .
	"&edit=_edit_" .
	"&scheduleSource=$scheduleSource'\n";
echo "lblSaveTo = \"" . inner::i18n("Enregistre la programmation vers '{0}'...") . "\";\n";
// 0.4.3 - displayMode and nDay choices :
echo "_evs.displayMode = '$displayMode' == '' ? _evs.DM_BY_ZONE : '$displayMode';";
echo "_evs.nDay = '$nDay' == '' ? -1 : parseInt('$nDay');";
?>
_evs.displayHTable();
function evohomeSave(fileId, fileName, eComm, scheduleToSave) {
	jeedom.cmd.execute({id:"<?php echo honeywell::getActionSaveId($locId);?>", notify:false,
		value:{<?php echo honeywell::ARG_FILE_ID?>:fileId, <?php echo honeywell::ARG_FILE_NAME?>:fileName,
			   <?php echo honeywell::ARG_FILE_REM?>:eComm, <?php echo honeywell::ARG_FILE_NEW_SCHEDULE?>:scheduleToSave}
		});
	waitingMessage(getMsg(lblSaveTo,[fileName]));
}
</script>
