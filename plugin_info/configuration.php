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
			<label class="col-lg-3 control-label">{{Nom d'utilisateur}}</label>
			<div class="col-lg-2">
				<input type="text" class="configKey form-control" data-l1key="<?php evohome::CFG_USER_NAME?>" />
			</div>
		<!--</div>
		<div class="form-group">-->
			<label class="col-lg-1 control-label">{{Mot de passe}}</label>
			<div class="col-lg-2">
				<input type="password" class="configKey form-control" data-l1key="<?php evohome::CFG_PASSWORD?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Emplacement}}</label>
			<div class="col-lg-2">
				<select class="configKey form-control configuration" data-l1key="evoLocationId">
					<?php
					echo '<option value="' . evohome::CFG_LOCATION_DEFAULT_ID . '">{{Défaut}}</option>';
					$locations = evohome::listLocations();
					if ( $locations != null ) {
						foreach ($locations as $location) {
							echo '<option value="' . $location['locationId'] . '">' . $location['name'] . '</option>';
						}
					}
					?>
				</select>
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
			<label class="col-lg-4 control-label">{{Type d'affichage par défaut des programmes}}</label>
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
			</div>
			<span class="col-lg-4 control-label" style="text-align:left;"><i>{{Ajuste la finesse et la charge mémoire de l'historique}}</i></span>
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
	?>
	var evoTempUnit = $('#evoTempUnit').val();
	if ( evoTempUnit !== unitCelsius && evoTempUnit !== unitFahrenheit ) evoTempUnit = unitCelsius;
	document.getElementById('etu'+evoTempUnit).checked = true;

	var evoDefaultShowingScheduleMode = $('#evoDefaultShowingScheduleMode').val();
	if ( evoDefaultShowingScheduleMode !== showHorizontal && evoDefaultShowingScheduleMode !== showVertical ) evoDefaultShowingScheduleMode = showHorizontal;
	document.getElementById('eshm'+evoDefaultShowingScheduleMode).checked = true;

	var evoShowingModes = $('#evoShowingModes').val();
	if ( evoShowingModes !== modeConsole && evoShowingModes !== modePopup ) evoShowingModes = modeConsole;
	document.getElementById('esm'+evoShowingModes).checked = true;
}, 100);
$('input[name=etu]').on('click', function (event) { $('#evoTempUnit').val($('input[name=etu]:checked').val()); });
$('input[name=eshm]').on('click', function (event) { $('#evoDefaultShowingScheduleMode').val($('input[name=eshm]:checked').val()); });
$('input[name=esm]').on('click', function (event) { $('#evoShowingModes').val($('input[name=esm]:checked').val()); });
</script>
