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
require_once dirname(__FILE__) . '/../core/class/honeywell.class.php';

function evohome_install() {
	honeywell::hnw_install();
}

function evohome_update() {
    message::add(honeywell::PLUGIN_NAME, "System Honeywell updated", null, null);
	honeywell::hnw_update();
}

function evohome_remove() {
    message::add(honeywell::PLUGIN_NAME, "System Honeywell says Good Bye !", null, null);
	honeywell::hnw_remove();
}

?>
