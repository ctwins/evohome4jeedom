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
	throw new Exception('{{_noAccess}}');
}
?>

<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{_userName}}</label>
			<div class="col-lg-4">
				<input type="text" class="configKey form-control" data-l1key="evoUserName" />
			</div>
		</div>
		  <div class="form-group">
			<label class="col-lg-3 control-label">{{_password}}</label>
			<div class="col-lg-4">
				<input type="text" class="configKey form-control" data-l1key="evoPassword" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{_place}}</label>
			<div class="col-lg-4">
				<select class="configKey form-control configuration form-control" data-l1key="evoLocationId">
					<option value="-1">{{_default}}</option>
					<?php
					foreach (evohome::listLocations() as $location) {
						echo '<option value="' . $location['locationId'] . '">' . $location['name'] . '</option>';
					}
					?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{_accuracy}}</label>
			<div class="col-lg-4">
				<select class="configKey form-control configuration form-control" data-l1key="evoDecimalsNumber">
					<option value="1">{{_accuracy_1}}</option>
					<option value="2">{{_accuracy_2}}</option>
					<option value="3">{{_accuracy_3}}</option>
					<option value="4">{{_accuracy_4}}</option>
				</select>
			</div>
		</div>
	</fieldset>
</form>
