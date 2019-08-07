<?php

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

function isDebug() {
	return log::getLogLevel('evohome') == 100;
}
function logDebug($msg, $addInfos="") {
	if ($addInfos !== "" ) {
		 if ( is_array($addInfos) ) $addInfos = json_encode($addInfos);
		 $msg .= " : <$addInfos>";
	}
	log::add('evohome', 'debug', $msg);
}
function logError($msg, $addInfos="") {
	if ($addInfos !== "" && !isDebug() ) {
		 if ( is_array($addInfos) ) $addInfos = json_encode($addInfos);
		 $msg .= " : <$addInfos>";
	}
	log::add('evohome', 'error', $msg);
}

function evoJsonDecode($text, $fnName) {
	if ( $text == null || $text == '' ) {
		if ( isDebug() ) logDebug("jsonDecode null for $fnName");
		return null;
	}
	// 2018-02-24 - fix for compatibility with PHP 7.xx (useless for PHP 5.xx)
	$text = str_replace('True', 'true', str_replace('False', 'false', $text));
	$aValue = json_decode($text, true);
	if ( json_last_error() != JSON_ERROR_NONE ) {
		$aValue = null;
		if ( isDebug() ) logError("Error while $fnName : json error=" . json_last_error() . ", input was <" . $text . ">");
	} else if ( isDebug() ) {
		logDebug("jsonDecode OK for $fnName");
	}
	return $aValue;
}

function evoSetParam($paramName, $value, $locId='') {
	config::save($paramName.$locId, $value, 'evohome');
}

function evoGetParam($paramName, $defValue=null, $locId='') {
	$cfgValue = config::byKey($paramName.$locId, 'evohome');
	return $cfgValue == null ? $defValue : $cfgValue;
}

function tsToLocalDateTime($ts) {
	$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
	$date->setTimestamp($ts);
	return $date->format('Y-m-d H:i:s');
}
function tsToLocalTime($ts) {
	$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
	$date->setTimestamp($ts);
	return $date->format('H:i:s');
}
function tsToAbsoluteHM($ts) {
	$date = new DateTime();
	$date->setTimestamp($ts);
	$date->setTimezone(new DateTimeZone('UTC'));
	return $date->format('H:i');
}
function tsToLocalMS($ts) {
	$date = new DateTime();
	$date->setTimestamp($ts);
	return $date->format('i:s');
}
function tsToLocalHMS($ts) {
	$date = new DateTime();
	$date->setTimestamp($ts);
	//$date->setTimezone(new DateTimeZone('UTC'));
	return $date->format('H:i:s');
}
function _gmtToLocalDateTime($gmtDateTime) {
	$dt = new DateTime($gmtDateTime, new DateTimeZone('UTC'));
	$dt->setTimezone(new DateTimeZone(config::byKey('timezone')));
	return $dt;
}
// example : "2018-01-28T23:00:00Z"
function gmtToLocalHM($gmtDateTime) {
	$dt = _gmtToLocalDateTime($gmtDateTime)->getTimestamp();
	return /*self::isAmPmMode()*/false ? date("g:i a", $dt) : date("G:i", $dt);
}
function gmtToLocalDateHMS($gmtDateTime) {
	$dt = _gmtToLocalDateTime($gmtDateTime)->getTimestamp();
	return date("Y-m-d G:i:s", $dt);
}
function gmtToLocalDate($gmtDateTime) {
	return strftime('%x', _gmtToLocalDateTime($gmtDateTime)->getTimestamp());
}
function isAdmin() {
	return isConnect('admin') == 1 ? 'true' : 'false';
}


function lockCron() {
	evoSetParam('functionality::cron::enable', 0);
}

function unlockCron() {
	evoSetParam('functionality::cron::enable', 1);
}


function setCacheData($cacheName, $content, $duration=null, $locId='') {
	cache::set($cacheName.$locId, $content, $duration == null ? 9*60 : $duration);
}
function getCacheData($cacheName, $locId='') {
	return cache::byKey($cacheName.$locId)->getValue();
}
function getCacheRemaining($cacheName, $locId='') {
	$cache = cache::byKey($cacheName.$locId);
	$tsDtCache = DateTime::createFromFormat('Y-m-d H:i:s', $cache->getDatetime())->getTimestamp();
	$cacheDuration = $cache->getLifetime();
	return ($tsDtCache + $cacheDuration) - (new Datetime())->getTimestamp();
}
function doCacheRemove($cacheName) {
	$cache = cache::byKey($cacheName);
	if ( is_object($cache) ) $cache->remove();
}

function extractZone($zonesDatas,$zoneId) {
	if ( is_array($zonesDatas) && array_key_exists('zones',$zonesDatas) ) {
		foreach ( $zonesDatas['zones'] as $tmp ) {
			if ( $tmp['zoneId'] == $zoneId ) {
				return $tmp;
			}
		}
	}
	return null;
}

function isVirtualAvailable() {
	$sql = "select count(*) as cnt from config c where c.plugin = 'virtual' and c.key = 'active' and c.value = 1";
	$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
	// 0.4.1 - fix of the check ! :(
	return count($results) > 0 && intval($results[0]["cnt"]) == 1;
}
