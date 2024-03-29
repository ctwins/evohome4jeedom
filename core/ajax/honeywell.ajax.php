<?php
try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');
	require_once '../class/evohome.class.php';
	require_once '../class/lyric.php';

	if ( !isConnect('admin') ) {
	    throw new Exception(honeyutils::i18n('401 - Accès non autorisé'));
	}

	ajax::init();
	$action = init('action');

	if ($action == 'getCommentary') {
	    $locId = init(honeywell::ARG_LOC_ID);
		$fileId = init(honeywell::ARG_FILE_ID);
		if ( $locId == '' || $fileId == '') {
		    throw new Exception(honeyutils::i18n("Aucun identifiant de sauvegarde spécifié"));
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - ajax.getCommentary($fileId)");
		$schedule = Schedule::getSchedule($locId,$fileId);
		if ( $schedule == null ) {
		    throw new Exception(honeyutils::i18n("Impossible de lire la sauvegarde d'identifiant {0}", $fileId));
		}
		$comm = $schedule['comment'];
		if ( honeyutils::isDebug() ) honeyutils::logDebug("<<OUT - ajax.getCommentary : " . json_encode($comm));
		ajax::success(array('comment'=>$comm));
	}
	else if ($action == 'setStatScope') {
	    $locId = init(honeywell::ARG_LOC_ID);
		$statScope = init('statScope');
		if ( $statScope == '') {
			throw new Exception("statScope unknown");
		}
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - ajax.setStatScope($locId,$statScope)");
		honeywell::ajaxChangeStatScope($locId,$statScope);
		honeyutils::logDebug("<<OUT - ajax.setStatScope");
		ajax::success();
	}
	else if ($action == 'ajaxListLocations') {
		honeyutils::logDebug("IN>> - ajax.ajaxListLocations()");
		if ( init('system') == honeywell::SYSTEM_LYRIC ) {
			$locList = lyric::apiListLocations();
		} else {
			$locList = evohome::apiListLocations();
			foreach ($locList as &$loc ) {
				if ( $loc['zones'] != null ) usort($loc['zones'], "honeywell::cmpName");
			}
		}		
		honeyutils::logDebug("<<OUT - ajax.ajaxListLocations");
		ajax::success(array('loc'=>$locList));
	}
	else if ($action == 'ajaxSynchronizeTH') {
		$prefix = init('prefix');
		$resizeWhenSynchronize = init('resizeWhenSynchronize') == '1' ? 1 : 0;
		if ( init('system') == honeywell::SYSTEM_LYRIC ) {
			$result = lyric::ajaxSynchronizeTH($prefix,$resizeWhenSynchronize);
		} else {
			$result = evohome::ajaxSynchronizeTH($prefix,$resizeWhenSynchronize);
		}
		if ( $result["success"] == false ) {
			ajax::error($result["message"]);
		} else {
		    ajax::success($result);
		}
	}
	else if ($action == 'refresh') {
	    $locId = init(honeywell::ARG_LOC_ID);
		if ( honeyutils::isDebug() ) honeyutils::logDebug("IN>> - ajax.refresh($locId)");
		honeywell::ajaxRefresh($locId);
		ajax::success();
		honeyutils::logDebug("<<OUT - ajax.refresh");
	}
	// specific Lyric cases :
	else if ($action == 'ajaxInitCallback') {
		honeyutils::logDebug("IN>> ajaxInitCallback");
		honeyutils::setParam(lyric::CFG_CALLBACK_URL,init('callbackUrl'));
		honeyutils::setParam(lyric::CFG_CONS_KEY,init('consumerKey'));
		honeyutils::setParam(lyric::CFG_SECRET_KEY,init('secretKey'));
		honeyutils::setCacheData(lyric::CALLBACK_STATE, init('state'), lyric::CALLBACK_LIMIT_TIME);
		honeyutils::logDebug("<<OUT ajaxInitCallback");
		ajax::success();
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . $action);
} catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
