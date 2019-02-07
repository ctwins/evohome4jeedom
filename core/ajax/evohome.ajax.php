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
	if ( !isConnect('admin') ) {
		throw new Exception(evohome::i18n('401 - Accès non autorisé'));
	}

	ajax::init();

	if (init('action') == 'getCommentary') {
		$fileId = init('fileId');
		if ( $fileId == '') {
			throw new Exception(evohome::i18n("Aucun identifiant de sauvegarde spécifié"));
		}
		if ( evohome::isDebug() ) evohome::logDebug("IN>> - ajax.getCommentary($fileId)");
		$schedule = evohome::getSchedule($fileId);
		if ( $schedule == null ) {
			throw new Exception(evohome::i18n("Impossible de lire la sauvegarde d'identifiant {0}", $fileId));
		}
		$comm = $schedule['comment'];
		if ( evohome::isDebug() ) evohome::logDebug("<<OUT - ajax.getCommentary : " . json_encode($comm));
		ajax::success(array('comment'=>$comm));
	}
	else if (init('action') == 'setStatScope') {
		$statScope = init('statScope');
		if ( $statScope == '') {
			throw new Exception("statScope unknown");
		}
		if ( evohome::isDebug() ) evohome::logDebug("IN>> - ajax.setStatScope($statScope)");
		evohome::ajaxChangeStatScope($statScope);
		ajax::success();
	}
	else if (init('action') == 'synchronizeTH') {
		$locationId = init('locationId');
		$sZones = init('zones');
		$resizeWhenSynchronize = init('resizeWhenSynchronize') == '1';
		if ( evohome::isDebug() ) evohome::logDebug("IN>> - ajax.synchronize($locationId)");
		$added = evohome::ajaxSynchronizeTH($locationId,$sZones,$resizeWhenSynchronize);
		ajax::success(array('added'=>$added));
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());

}
