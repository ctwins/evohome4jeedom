<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

/**
 * Before 0.5.0, this file was evohome.utils.php and this class was evoutils
 * @author ctwins95
 *
 */
class honeyutils {
	static function i18n($txt, $file=null, $arg=null) {
		if ( $file == null ) {
			$file = "plugins/".honeywell::PLUGIN_NAME."/core/class/evohome.class.php";
		}
		if ( substr($txt,-1) == '}' ) $txt .= '__';
		$txt = __($txt, $file);
		if ( substr($txt,-2) == '__' ) $txt = substr($txt,0,-2);
		if ( $arg == null ) return $txt;
		if ( !is_array($arg) ) return str_replace('{0}', $arg, $txt);
		for ( $i=0 ; $i<count($arg) ; $i++ ) $txt = str_replace("{".$i."}", $arg[$i], $txt);
		return $txt;
	}

	static function isDebug() {
		return log::getLogLevel(honeywell::PLUGIN_NAME) == 100;
	}
	static function logDebug($msg, $addInfos="") {
		if ($addInfos !== "" ) {
			 if ( is_array($addInfos) ) $addInfos = json_encode($addInfos);
			 $msg .= " : <$addInfos>";
		}
		log::add(honeywell::PLUGIN_NAME, 'debug', $msg);
	}
	static function logWarn($msg, $addInfos="") {
		if ($addInfos !== "" ) {
			 if ( is_array($addInfos) ) $addInfos = json_encode($addInfos);
			 $msg .= " : <$addInfos>";
		}
		log::add(honeywell::PLUGIN_NAME, 'warn', $msg);
	}
	static function logError($msg, $addInfos="") {
		if ($addInfos !== "" && !self::isDebug() ) {
			 if ( is_array($addInfos) ) $addInfos = json_encode($addInfos);
			 $msg .= " : <$addInfos>";
		}
		log::add(honeywell::PLUGIN_NAME, 'error', $msg);
	}

	static function logException($e,$fn) {
		self::logError("Exception while $fn : " . $e->getMessage() . " (code=" . $e->getCode() . ")");
	}

	static function jsonDecode($text, $fnName) {
		if ( $text == null || $text == '' ) {
			if ( self::isDebug() ) self::logDebug("jsonDecode null for $fnName");
			return null;
		}
		// 2018-02-24 - fix for compatibility with PHP 7.xx (useless for PHP 5.xx)
		$text = str_replace('True', 'true', str_replace('False', 'false', $text));
		$aValue = json_decode($text, true);
		if ( json_last_error() != JSON_ERROR_NONE ) {
			$aValue = null;
			if ( self::isDebug() ) self::logError("Error while $fnName : json error=" . json_last_error() . ", input was <" . $text . ">");
		} else if ( self::isDebug() ) {
			self::logDebug("jsonDecode OK for $fnName");
		}
		return $aValue;
	}

	static function setParam($paramName, $value, $locId='', $suffix='') {
	    config::save($paramName.$suffix.$locId, $value, honeywell::PLUGIN_NAME);
	}

	static function getParam($paramName, $defValue=null, $locId='', $suffix='') {
	    $cfgValue = config::byKey($paramName.$suffix.$locId, honeywell::PLUGIN_NAME);
		$ret = $cfgValue == null ? $defValue : $cfgValue;
		return $ret;
	}

	static function getDateTime($p1=null) {
	    return $p1 == null ? new DateTime() : new DateTime($p1);
	}

	static function tsToLocalDateTime($ts) {
		$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
		$date->setTimestamp($ts);
		return $date->format('Y-m-d H:i:s');
	}
	static function tsToLocalTime($ts) {
		$date = new DateTime('now', new DateTimeZone(config::byKey('timezone')));
		$date->setTimestamp($ts);
		return $date->format('H:i:s');
	}
	static function tsToAbsoluteHM($ts) {
		$date = self::getDateTime();
		$date->setTimestamp($ts);
		$date->setTimezone(new DateTimeZone('UTC'));
		return str_replace(":","h",$date->format('H:i'));
	}
	static function tsToLocalMS($ts) {
	    $date = self::getDateTime();
		$date->setTimestamp($ts);
		return $date->format('i:s');
	}
	static function tsToLocalHMS($ts) {
	    $date = self::getDateTime();
		$date->setTimestamp($ts);
		//$date->setTimezone(new DateTimeZone('UTC'));
		return $date->format('H:i:s');
	}
	static function _gmtToLocalDateTime($gmtDateTime) {
		$dt = new DateTime($gmtDateTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone(config::byKey('timezone')));
		return $dt;
	}
	// example : "2018-01-28T23:00:00Z"
	static function gmtToLocalHM($gmtDateTime) {
		$dt = self::_gmtToLocalDateTime($gmtDateTime)->getTimestamp();
		return /*self::isAmPmMode()*/false ? date("g:i a", $dt) : date("G:i", $dt);
	}
	static function gmtToLocalDateHMS($gmtDateTime) {
		$dt = self::_gmtToLocalDateTime($gmtDateTime)->getTimestamp();
		return date("Y-m-d G:i:s", $dt);
	}
	static function gmtToLocalDate($gmtDateTime) {
		return self::_gmtToLocalDateTime($gmtDateTime)->format('d/m/y');
	}
	static function localDateTimeToGmtZ($dateTime) {
		$dateTime->setTimezone(new DateTimeZone('UTC'));
		// %Y-%m-%dT%H:%M:%SZ
		return $dateTime->format('Y-m-d') . 'T' . $dateTime->format('H:i:s') . 'Z';
		}
	
	static function isAdmin() {
		return isConnect('admin') == 1 ? 'true' : 'false';
	}


	static function lockCron() {
		self::setParam('functionality::cron::enable', 0);
	}

	static function unlockCron() {
		self::setParam('functionality::cron::enable', 1);
	}


	static function setCacheData($cacheName, $content, $duration=null, $locId='') {
		cache::set($cacheName.$locId, $content, $duration == null ? 9*60 : $duration);
	}
	static function getCacheData($cacheName, $locId='') {
		return cache::byKey($cacheName.$locId)->getValue();
	}
	static function getCacheRemaining($cacheName, $locId='') {
		$cache = cache::byKey($cacheName.$locId);
		$tsDtCache = DateTime::createFromFormat('Y-m-d H:i:s', $cache->getDatetime())->getTimestamp();
		$cacheDuration = $cache->getLifetime();
		return ($tsDtCache + $cacheDuration) - (new Datetime())->getTimestamp();
	}
	static function doCacheRemove($cacheName, $locId='') {
		$cache = cache::byKey($cacheName.$locId);
		if ( is_object($cache) ) $cache->remove();
	}

	static function extractZone($zonesDatas,$zoneId) {
		if ( is_array($zonesDatas) && array_key_exists('zones',$zonesDatas) ) {
			foreach ( $zonesDatas['zones'] as $tmp ) {
				if ( $tmp['zoneId'] == $zoneId ) {
					return $tmp;
				}
			}
		}
		return null;
	}

	static function C2F($t,$delta=false) {
		return round($t * 9/5, 2) + (!$delta ? 32 : 0);
	}

	static function F2C($t,$delta=false) {
		return round(($t - (!$delta ? 32 :0)) * 5/9, 2);
	}

	/*static function isVirtualAvailable() {
		$sql = "select count(*) as cnt from config c where c.plugin = 'virtual' and c.key = 'active' and c.value = 1";
		$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
		// 0.4.1 - fix of the check ! :(
		return count($results) > 0 && intval($results[0]["cnt"]) == 1;
	}*/

	const CDays = ["Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi","Dimanche"];
	const ECDayToNumDay = array("Monday"=>0,"Tuesday"=>1,"Wednesday"=>2,"Thursday"=>3,"Friday"=>4,"Saturday"=>5,"Sunday"=>6);
	const NumDayToECDay = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
	static function getDayName($numDay) {
		return self::i18n(self::CDays[$numDay]);
	}
	static function getNumDayFromECDay($ecDay) {
		return self::ECDayToNumDay[$ecDay];
	}
	static function getECDayFromNumDay($numDay) {
		return self::NumDayToECDay[$numDay];
	}
	static function getDayNameFromECDayName($ecDay) {
		return self::getDayName(self::ECDayToNumDay[$ecDay]);
	}

	static function encodeURIComponent($str) {
	    $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
	    return strtr(rawurlencode($str), $revert);
	}

	static function saveInfo($equ,$infoId,$data,$defaut=null) {
		$tmp = $equ->getCmd(null,$infoId);
		if (is_object($tmp)) {
			$ret = $tmp->execCmd();
			$tmp->event($data);
			return $ret;
		}
		self::logDebug("saveInfo has failed as no cmd found with id=$infoId");
		return $defaut;
	}

	static function getInfo($equ,$infoId,$def=null) {
		$cmd = $equ->getCmd(null,$infoId);
		return !is_object($cmd) ? $def : $cmd->execCmd();
	}

	static function readInfo($equ,$infoId) {
		$tmp = $equ->getCmd(null,$infoId);
		if (is_object($tmp)) {
			return $tmp->execCmd();
		}
		return null;
	}

	static function startsWith($inString, $begin) {
		return substr($inString, 0, strlen($begin)) === $begin;
	}

	static function endsWith($inString, $end) {
		return substr($inString, -strlen($end)) === $end;
	}

}
