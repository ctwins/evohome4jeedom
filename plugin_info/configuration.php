<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<div class="col-lg-3"></div>
			<div class="col-lg-2">
				<label>{{Nom d'utilisateur}}</label>
				<br/>
				<?php echo '<input id="usnm" type="text" style="width:unset;" class="configKey form-control" data-l1key="' . evohome::CFG_USER_NAME . '" />'; ?>
			</div>
			<div class="col-lg-2">
				<label>{{Mot de passe}}</label>
				<br/>
				<?php echo '<input id="pswd" type="password" style="width:unset;" class="configKey form-control" data-l1key="' . evohome::CFG_PASSWORD . '" />'; ?>
			</div>
			<div class="col-lg-2">
				<label>{{Emplacement}}</label>
				<br/>
				<div style="display:flex;">
					<a class="btn btn-warning" id="btnReload"><i class="fa fa-refresh"/></a>
					&nbsp;&nbsp;
					<?php
					$locations = evohome::listLocations();
					echo '<select id="evoLocationId" class="configKey form-control configuration" data-l1key="evoLocationId">';
						echo '<option value="' . evohome::CFG_LOCATION_DEFAULT_ID . '">{{Défaut}}</option>';
						if ( is_array($locations) ) {
							foreach ($locations as $location) {
								echo '<option value="' . $location['locationId'] . '">' . $location['name'] . '</option>';
							}
						}
					echo '</select>';
					echo '<script>var zones=[];';
					if ( is_array($locations) ) {
						foreach ($locations as $location) {
							echo "zones[" . $location['locationId'] . "]='" . str_replace("'", "\'", json_encode($location['zones'])) . "';";
						}
					}
					echo 'var locDefaultId=' . evohome::CFG_LOCATION_DEFAULT_ID . ';</script>';
					?>
				</div>
			</div>
		</div>
		<div class="form-group" style="margin-bottom:30px;">
			<div class="col-lg-3"></div>
			<div class="col-lg-2" style="text-align:right;margin-top:24px;">
				<a class="btn btn-warning" id="btnSync">{{Synchroniser}}</a>
			</div>
			<div class="col-lg-2">
				<label>{{Préfixe de nommage}}</label>
				<br/>
				<input id="prefix" type="text" class="form-control" style="width:80px;" value="TH" />
			</div>
			<div class="col-lg-3" style="margin-top:24px;">
				<label style="font-style:italic;">
					<input type="checkbox" style="height:24px;width:24px;" id="resizeWhenSynchronize" />
					{{Redimensionner les widgets existants}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Unité de température}}</label>
			<input type="hidden" class="configKey" id="evoTempUnit" data-l1key="evoTempUnit" />
			<div class="col-lg-1" style="width:auto;">
				<label>
					<?php echo '<input class="form-control" type="radio" id="etu' . evohome::CFG_UNIT_CELSIUS . '" name="etu"
							value="' . evohome::CFG_UNIT_CELSIUS . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;°C&nbsp;(Celsius)
				</label>
			</div>
			<div class="col-lg-2">
				<label>
					<?php echo '<input class="form-control" type="radio" id="etu' . evohome::CFG_UNIT_FAHRENHEIT . '" name="etu"
							value="' . evohome::CFG_UNIT_FAHRENHEIT . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;°F&nbsp;(Fahrenheit)
				</label>
			</div>
			<span class="col-lg-5 control-label" style="text-align:left;"><i>{{Attention : concerne l'affichage et le stockage historique}}</i></span>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Affichage}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Précision}}</label>
			<div class="col-lg-3">
				<select class="configKey form-control configuration" data-l1key="evoDecimalsNumber">
					<option value="1">{{0.5 par défaut (X.82 > X.5) = Défaut EvoHome}}</option>
					<option value="2">{{0.5 arrondi (X.82 > X+1, X.44 > X.5)}}</option>
					<option value="3">{{0.05 arrondi (X.82 > X.80, X.44 > X.45)}}</option>
					<option value="4">{{0.01 = valeur native}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Programmes}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Type d'affichage par défaut}}</label>
			<input type="hidden" class="configKey" id="evoDefaultShowingScheduleMode" data-l1key="evoDefaultShowingScheduleMode" />
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input class="form-control" type="radio" id="eshm' . evohome::CFG_SCH_MODE_HORIZONTAL . '" name="eshm"
							value="' . evohome::CFG_SCH_MODE_HORIZONTAL . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Horizontal}}
				</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input class="form-control" type="radio" id="eshm' . evohome::CFG_SCH_MODE_VERTICAL . '" name="eshm"
							value="' . evohome::CFG_SCH_MODE_VERTICAL . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Vertical}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-4">&nbsp;</div>
			<div class="col-lg-5">
				<label>
					<input class="configKey" type="checkbox" style="height:24px;width:24px;" data-l1key="evoEditAvailable" />
					{{Mode édition disponible}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Console}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" style="vertical-aglin:middle;">{{Modes de présence}}</label>
			<input type="hidden" class="configKey" id="evoShowingModes" data-l1key="evoShowingModes" />
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input class="form-control" type="radio" id="esm' . evohome::CFG_SHOWING_MODES_CONSOLE . '" name="esm"
							value="' . evohome::CFG_SHOWING_MODES_CONSOLE . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Intégré à la console}}
				</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input class="form-control" type="radio" id="esm' . evohome::CFG_SHOWING_MODES_POPUP . '" name="esm"
							value="' . evohome::CFG_SHOWING_MODES_POPUP . '" style="height:24px;width:24px;display:inline;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Par popup}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-4">&nbsp;</div>
			<div class="col-lg-5">
				<label>
					<input class="configKey" type="checkbox" style="height:24px;width:24px;" data-l1key="evoRefreshBeforeSave" />
					{{Forcer la lecture des données avant de sauvegarder la programmation}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Historique}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Intervalle de mesure}}</label>
			<div class="col-lg-2">
				<select class="configKey form-control configuration" data-l1key="evoLoadingInterval">
					<option value="10">10mn</option>
					<option value="15">15mn</option>
					<option value="20">20mn</option>
					<option value="30">30mn</option>
				</select>
				<span><i>{{Ajuste la finesse et la charge mémoire de l'historique}}</i></span>
			</div>
			<span class="col-lg-4 control-label" style="text-align:left;">
				<label>
					<input class="configKey" type="checkbox" style="height:24px;width:24px;" data-l1key="evoLoadingSync" />
					{{Synchronisation horloge (HH:MM, avec MM=intervalle*n)}}
				</label>
			</span>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Durée de rétention}}</label>
			<div class="col-lg-2">
				<select class="configKey form-control configuration" data-l1key="evoHistoryRetention">
					<option value="">{{Jamais}}</option>
					<option value="-1 day">1 {{jour}}</option>
					<option value="-7 days">7 {{jours}}</option>
					<option value="-1 month">1 {{mois}}</option>
					<option value="-3 month">3 {{mois}}</option>
					<option value="-6 month">6 {{mois}}</option>
					<option value="-1 year">1 {{an}}</option>
				</select>
			</div>
			<span class="col-lg-4 control-label" style="text-align:left;"><i>{{Ajuste tous les équipements}}</i></span>
		</div>
	</fieldset>
</form>
<script>
setTimeout(function() {
	<?php
	echo "var unitCelsius = '" . evohome::CFG_UNIT_CELSIUS . "';";
	echo "var unitFahrenheit = '" . evohome::CFG_UNIT_FAHRENHEIT . "';";
	echo "var showHorizontal = '" . evohome::CFG_SCH_MODE_HORIZONTAL . "';";
	echo "var showVertical = '" . evohome::CFG_SCH_MODE_VERTICAL . "';";
	echo "var modeConsole = '" . evohome::CFG_SHOWING_MODES_CONSOLE . "';";
	echo "var modePopup = '" . evohome::CFG_SHOWING_MODES_POPUP . "';";
	$data = json_decode(file_get_contents(dirname(__FILE__) . "/info.json"), true);
	echo "var version = '" . (is_array($data) ? $data["version"] : null) . "';";
	?>
	var evoTempUnit = $('#evoTempUnit').val();
	if ( evoTempUnit === '' || evoTempUnit == null ) {
		$('#usnm').val('');
		$('#pswd').val('');
	}
	if ( evoTempUnit !== unitCelsius && evoTempUnit !== unitFahrenheit ) {
		evoTempUnit = unitCelsius;
		$('#evoTempUnit').val(unitCelsius);
	}
	document.getElementById('etu'+evoTempUnit).checked = true;
	var evoDefaultShowingScheduleMode = $('#evoDefaultShowingScheduleMode').val();
	if ( evoDefaultShowingScheduleMode !== showHorizontal && evoDefaultShowingScheduleMode !== showVertical ) {
		evoDefaultShowingScheduleMode = showHorizontal;
		$('#evoDefaultShowingScheduleMode').val(showHorizontal);
	}
	document.getElementById('eshm'+evoDefaultShowingScheduleMode).checked = true;
	var evoShowingModes = $('#evoShowingModes').val();
	if ( evoShowingModes !== modeConsole && evoShowingModes !== modePopup ) {
		evoShowingModes = modeConsole;
		$('#evoShowingModes').val(modeConsole);
	}
	document.getElementById('esm'+evoShowingModes).checked = true;
	if ( version  != null ) $('#span_plugin_install_date').html(version);
}, 250);
$('input[name=etu]').on('click', function(event) { $('#evoTempUnit').val($('input[name=etu]:checked').val()); });
$('input[name=eshm]').on('click', function(event) { $('#evoDefaultShowingScheduleMode').val($('input[name=eshm]:checked').val()); });
$('input[name=esm]').on('click', function(event) { $('#evoShowingModes').val($('input[name=esm]:checked').val()); });
$('#btnReload').on('click', function() {
	var _user = $('#usnm').value().trim();
	var _pswd = $('#pswd').value().trim();
	if ( _user != '' && _pswd != '' ) {
		$('#bt_savePluginConfig').click();
		setTimeout(function() {
			$.ajax({
				type:"POST",
				url:"plugins/evohome/core/ajax/evohome.ajax.php",
				data:{action:"reloadLocations"},
				dataType:'json',
				error:function(request, status, error) {
					handleAjaxError(request, status, error);
				},
				success:function(data) {
					if (data.state == 'ok' && is_array(data.result.loc) ) {
						var prevLocId = $('#evoLocationId').value();
						var selLoc = $('#evoLocationId')[0];
						selLoc.options.length = 1;	// keep default only
						selLoc.options[0].selected = true;
						zones = [];
						data.result.loc.forEach(function(loc,idx) {
							selLoc.options[selLoc.options.length] = new Option(loc.name, loc.locationId);
							if ( loc.locationId == prevLocId ) selLoc.options[selLoc.options.length-1].selected = true;
							zones[loc.locationId] = JSON.stringify(loc.zones);
						});
						if ( selLoc.options[0].selected && selLoc.options.length > 1 ) selLoc.options[1].selected = true;
					}
				}
			})
		}, 1000);
	}
});
$('#btnSync').on('click', function() {
	var locId = $('#evoLocationId').value();
	if ( locId == locDefaultId ) {
		bootbox.alert({message:"{{Indisponible sur la localisation par défaut}}", closeButton:false});
		return;
	}
	$('#bt_savePluginConfig').click();
	setTimeout(function() {
		var _prefix = $('#prefix').value().trim();
		if ( _prefix != '' ) _prefix += " ";
		var _rws = $('#resizeWhenSynchronize').value();
		$.ajax({
			type:"POST",
			url:"plugins/evohome/core/ajax/evohome.ajax.php",
			data:{action:"synchronizeTH",locationId:locId,zones:zones[locId],prefix:_prefix,resizeWhenSynchronize:_rws},
			dataType:'json',
			error:function(request, status, error) {
				handleAjaxError(request, status, error);
			},
			success:function(data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message:data.result, level:'danger'});
				} else {
					$('#div_alert').showAlert({message:'{{Synchronisation effectuée}}', level:'success'});
					if ( data.result.added ) {
						// reload the page if some components were added
						document.location.href = "/index.php?v=d&m=evohome&p=evohome";
					}
				}
			}
		})
	}, 1000);
});
</script>
