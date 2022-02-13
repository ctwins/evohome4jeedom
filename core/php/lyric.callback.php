<?php
/**
 * This class appears with version 0.5.0
 * Used as callback of the OAuth2 protocol for Lyric system
 * @author ctwins95
 *
 */
require_once '../class/lyric.php';

// will register the code received
$ret = lyric::callBack();

if ( $ret[lyric::SUCCESS] ) {
	echo "<script>window.close();</script>";
} else {
	echo lyric::i18n("Erreur en récupération du token : code='{0}', message='{1}'", [$ret["code"],$ret["message"]]);
}
