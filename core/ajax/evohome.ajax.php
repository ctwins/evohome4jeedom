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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');
	include ('../class/evohome.utils.php');
	if ( !isConnect('admin') ) {
		throw new Exception(evohome::i18n('401 - Accès non autorisé'));
	}

	ajax::init();

	if (init('action') == 'getCommentary') {
		$locId = init(evohome::ARG_LOC_ID);
		$fileId = init(evohome::ARG_FILE_ID);
		if ( $locId == '' || $fileId == '') {
			throw new Exception(evohome::i18n("Aucun identifiant de sauvegarde spécifié"));
		}
		if ( isDebug() ) logDebug("IN>> - ajax.getCommentary($fileId)");
		$schedule = evohome::getSchedule($locId,$fileId);
		if ( $schedule == null ) {
			throw new Exception(evohome::i18n("Impossible de lire la sauvegarde d'identifiant {0}", $fileId));
		}
		$comm = $schedule['comment'];
		if ( isDebug() ) logDebug("<<OUT - ajax.getCommentary : " . json_encode($comm));
		ajax::success(array('comment'=>$comm));
	}
	else if (init('action') == 'setStatScope') {
		$locId = init(evohome::ARG_LOC_ID);
		$statScope = init('statScope');
		if ( $statScope == '') {
			throw new Exception("statScope unknown");
		}
		if ( isDebug() ) logDebug("IN>> - ajax.setStatScope($locId,$statScope)");
		evohome::ajaxChangeStatScope($locId,$statScope);
		ajax::success();
	}
	else if (init('action') == 'listLocations') {
		if ( isDebug() ) logDebug("IN>> - ajax.listLocations()");
		$locList = evohome::listLocations();
		foreach ($locList as &$loc ) {
			if ( $loc['zones'] != null ) usort($loc['zones'], "evohome::cmpName");
		}
		ajax::success(array('loc'=>$locList));
	}
	/*else if (init('action') == 'reloadLocations') {
		if ( isDebug() ) logDebug("IN>> - ajax.reloadLocations()");
		$loc = evohome::ajaxReloadLocations();
		ajax::success(array('loc'=>$loc));
	}*/
	else if (init('action') == 'getInformationsAllZones') {
		if ( isDebug() ) logDebug("IN>> - ajax.getInformationsAllZones()");
		$locId = init(evohome::ARG_LOC_ID);
		$infosZones = evohome::getInformationsAllZonesE2($locId);
		usort($infosZones['zones'], "evohome::cmpName");
		ajax::success(array('infoZones'=>$infosZones));
	}
	else if (init('action') == 'synchronizeTH') {
		$prefix = init('prefix');
		$resizeWhenSynchronize = init('resizeWhenSynchronize') == '1' ? 1 : 0;
		$result = evohome::ajaxSynchronizeTH($prefix,$resizeWhenSynchronize);
		if ( $result["success"] == false ) {
			ajax::error($result["message"]);
		} else {
			ajax::success(array('added'=>$result["addedd"]));
		}
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());

}
